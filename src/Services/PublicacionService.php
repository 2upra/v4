<?php

namespace Kamples\Services;

/**
 * Servicio central de gestión de publicaciones.
 * 
 * Maneja la construcción de queries, ordenamiento y filtrado
 * de publicaciones para el feed y perfiles.
 *
 * @since 1.0.0
 */
class PublicacionService
{
    private const FALLBACK_USER_ID = 44;
    private const POST_IN_LIMIT = 500;

    private FeedService $feedService;
    private IdeaService $ideaService;
    private CacheService $cacheService;
    private static ?PublicacionService $instancia = null;

    /**
     * Constructor del servicio.
     */
    public function __construct()
    {
        $this->feedService = FeedService::obtenerInstancia();
        $this->ideaService = IdeaService::obtenerInstancia();
        $this->cacheService = CacheService::obtenerInstancia('feed');
    }

    /**
     * Obtiene la instancia singleton del servicio.
     *
     * @return self
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene publicaciones con los argumentos proporcionados.
     *
     * @param array $args Argumentos de búsqueda
     * @param bool $isAjax Si es petición AJAX
     * @param int $paged Página actual
     * @return string|false HTML o false si falla
     */
    public function obtener(array $args = [], bool $isAjax = false, int $paged = 1): string|false
    {
        try {
            $usuarioActual = get_current_user_id();
            $defaults = $this->obtenerDefaults();

            if (!$isAjax && isset($_GET['busqueda'])) {
                $args['identifier'] = sanitize_text_field($_GET['busqueda']);
            }

            $userId = $args['user_id'] ?? '';
            $tipoUsuario = $this->obtenerTipoUsuario($args, $usuarioActual);
            $args = array_merge($defaults, $args);

            $queryArgs = $this->construirQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario);

            if (!$queryArgs) {
                return false;
            }

            $colecciones = $this->obtenerColeccionesParaMomento($args, $usuarioActual);
            $output = $this->procesarPublicaciones($queryArgs, $args, $isAjax);

            if ($args['filtro'] === 'momento') {
                $output = $colecciones . $output;
            }

            if ($isAjax) {
                echo $output;
                wp_die();
            }

            return $output;
        } catch (\Exception $e) {
            $this->log('error', "Error en obtener publicaciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construye los argumentos de la query según el contexto.
     *
     * @param array $args Argumentos
     * @param int $paged Página
     * @param mixed $userId ID usuario del perfil
     * @param int $usuarioActual ID usuario actual
     * @param string $tipoUsuario Tipo de usuario
     * @return array|false Query args
     */
    public function construirQueryArgs(
        array $args,
        int $paged,
        mixed $userId,
        int $usuarioActual,
        string $tipoUsuario
    ): array|false {
        /* Query para post específico por ID */
        if (!empty($args['id'])) {
            return [
                'post_type' => $args['post_type'],
                'p' => intval($args['id']),
            ];
        }

        /* Query para ideas (posts similares a colección) */
        if (filter_var($args['idea'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $this->ideaService->manejar($args, $paged);
        }

        /* Query para colección específica */
        if (!empty($args['colec']) && is_numeric($args['colec'])) {
            /* Usa la función global manejarColeccion del deprecated */
            if (function_exists('manejarColeccion')) {
                return manejarColeccion($args, $paged);
            }
            return false;
        }

        /* Query estándar: aplicar configuración y ordenamiento */
        return $this->configuracionQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario);
    }

    /**
     * Configura los argumentos de la query estándar.
     *
     * @param array $args Argumentos
     * @param int $paged Página
     * @param mixed $userId ID usuario perfil
     * @param int $usuarioActual ID usuario actual
     * @param string $tipoUsuario Tipo de usuario
     * @return array|false Query args
     */
    public function configuracionQueryArgs(
        array $args,
        int $paged,
        mixed $userId,
        int $usuarioActual,
        string $tipoUsuario
    ): array|false {
        try {
            $isAuthenticated = $usuarioActual && $usuarioActual != 0;
            $isAdmin = current_user_can('administrator');
            $identifier = $args['identifier'] ?? '';

            if (!$isAuthenticated) {
                $usuarioActual = self::FALLBACK_USER_ID;
            }

            /* Query para perfil de usuario específico */
            if (!empty($userId)) {
                $queryArgs = [
                    'post_type' => $args['post_type'],
                    'posts_per_page' => $args['posts'],
                    'paged' => $paged,
                    'ignore_sticky_posts' => true,
                    'suppress_filters' => false,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'author' => $userId,
                ];

                return $this->aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId);
            }

            $posts = $args['posts'];
            $similarTo = $args['similar_to'] ?? null;
            $filtroTiempo = (int)get_user_meta($usuarioActual, 'filtroTiempo', true);

            $queryArgs = $this->preOrdenamiento(
                $args,
                $paged,
                $usuarioActual,
                $identifier,
                $isAdmin,
                $posts,
                $filtroTiempo,
                $similarTo,
                $tipoUsuario
            );

            /* Aplicar filtros de usuario para social_post */
            if ($args['post_type'] === 'social_post' && in_array($args['filtro'], ['sampleList', 'sample'])) {
                if ($tipoUsuario !== 'Fan') {
                    $queryArgs = $this->aplicarFiltrosUsuario($queryArgs, $usuarioActual);
                }
            }

            $queryArgs = $this->aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario);

            return $queryArgs;
        } catch (\Exception $e) {
            $this->log('error', "Error en configuracionQueryArgs: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aplica pre-ordenamiento según el tipo de post.
     *
     * @param array $args Argumentos
     * @param int $paged Página
     * @param int $usu Usuario actual
     * @param string $identifier Identificador de búsqueda
     * @param bool $isAdmin Es administrador
     * @param int $posts Posts por página
     * @param int $filtroTiempo Filtro de tiempo
     * @param int|null $similarTo ID de post similar
     * @param string|null $tipoUsuario Tipo de usuario
     * @return array|false Query args
     */
    public function preOrdenamiento(
        array $args,
        int $paged,
        int $usu,
        string $identifier,
        bool $isAdmin,
        int $posts,
        int $filtroTiempo,
        ?int $similarTo,
        ?string $tipoUsuario = null
    ): array|false {
        try {
            global $wpdb;
            if (!$wpdb) {
                return false;
            }

            $queryArgs = [
                'post_type' => $args['post_type'],
                'posts_per_page' => $posts,
                'paged' => $paged,
                'ignore_sticky_posts' => true,
                'suppress_filters' => false,
            ];

            if (!empty($identifier)) {
                $queryArgs = $this->prefiltrarIdentifier($identifier, $queryArgs);
            }

            /* Ordenamiento para social_post */
            if ($args['post_type'] === 'social_post') {
                $filtrosExcluidos = ['rola', 'momento', 'tiendaPerfil', 'rolaListLike'];
                if (!isset($args['filtro']) || !in_array($args['filtro'], $filtrosExcluidos)) {
                    $queryArgs = $this->ordenamiento(
                        $queryArgs,
                        $filtroTiempo,
                        $usu,
                        $identifier,
                        $similarTo,
                        $paged,
                        $isAdmin,
                        $posts,
                        $tipoUsuario
                    );
                }
            }

            /* Ordenamiento para colecciones */
            if ($args['post_type'] === 'colecciones') {
                $queryArgs = $this->ordenamientoColecciones($queryArgs, $filtroTiempo, $usu);
            }

            /* Ordenamiento para tareas */
            if ($args['post_type'] === 'tarea') {
                $prioridad = ($args['filtro'] ?? '') === 'tareaPrioridad';
                $queryArgs = $this->ordenamientoTareas($queryArgs, $usu, $args, $prioridad);
            }

            return $queryArgs;
        } catch (\Exception $e) {
            $this->log('error', "Error en preOrdenamiento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aplica ordenamiento según filtro de tiempo.
     *
     * @param array $queryArgs Query args actuales
     * @param int $filtroTiempo Filtro de tiempo seleccionado
     * @param int $usuarioActual ID usuario actual
     * @param string $identifier Identificador de búsqueda
     * @param int|null $similarTo ID post similar
     * @param int $paged Página
     * @param bool $isAdmin Es admin
     * @param int $posts Posts por página
     * @param string|null $tipoUsuario Tipo usuario
     * @return array Query args modificados
     */
    public function ordenamiento(
        array $queryArgs,
        int $filtroTiempo,
        int $usuarioActual,
        string $identifier,
        ?int $similarTo,
        int $paged,
        bool $isAdmin,
        int $posts,
        ?string $tipoUsuario = null
    ): array {
        global $wpdb;

        /* Usuarios Fan: siempre feed personalizado */
        if ($tipoUsuario === 'Fan') {
            return $this->aplicarFeedPersonalizado($queryArgs, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario);
        }

        $filtrosUsuario = get_user_meta($usuarioActual, 'filtroPost', true);

        try {
            if (!$wpdb) {
                return $queryArgs;
            }

            $likesTable = $wpdb->prefix . 'post_likes';

            switch ($filtroTiempo) {
                case 1:
                    /* Recientes */
                    $queryArgs['orderby'] = 'date';
                    $queryArgs['order'] = 'DESC';
                    break;

                case 2:
                case 3:
                    /* Top semanal / mensual */
                    $interval = ($filtroTiempo === 2) ? '1 WEEK' : '1 MONTH';
                    $queryArgs = $this->ordenamientoPorLikes($queryArgs, $likesTable, $interval);
                    break;

                default:
                    /* Feed personalizado */
                    $queryArgs = $this->aplicarFeedPersonalizado(
                        $queryArgs,
                        $usuarioActual,
                        $identifier,
                        $similarTo,
                        $paged,
                        $isAdmin,
                        $posts,
                        $tipoUsuario,
                        $filtrosUsuario
                    );
                    break;
            }

            if (empty($queryArgs['orderby'])) {
                $queryArgs['orderby'] = 'date';
                $queryArgs['order'] = 'DESC';
            }

            return $queryArgs;
        } catch (\Exception $e) {
            $this->log('error', "Error en ordenamiento: " . $e->getMessage());
            return $queryArgs;
        }
    }

    /**
     * Aplica ordenamiento por likes en intervalo.
     *
     * @param array $queryArgs Query args
     * @param string $likesTable Tabla de likes
     * @param string $interval Intervalo SQL
     * @return array Query args modificados
     */
    private function ordenamientoPorLikes(array $queryArgs, string $likesTable, string $interval): array
    {
        global $wpdb;

        $sql = "
            SELECT p.ID, COUNT(pl.post_id) as like_count 
            FROM {$wpdb->posts} p 
            LEFT JOIN {$likesTable} pl ON p.ID = pl.post_id 
            WHERE p.post_type = 'social_post' 
            AND p.post_status = 'publish'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL {$interval})  
            AND pl.like_date >= DATE_SUB(NOW(), INTERVAL {$interval}) 
            GROUP BY p.ID
            HAVING like_count > 0
            ORDER BY like_count DESC, p.post_date DESC
        ";

        $postsWithLikes = $wpdb->get_results($sql, ARRAY_A);

        if (!empty($postsWithLikes)) {
            $postIds = wp_list_pluck($postsWithLikes, 'ID');
            if (!empty($postIds)) {
                $queryArgs['post__in'] = $postIds;
                $queryArgs['orderby'] = 'post__in';
            }
        } else {
            $queryArgs['orderby'] = 'date';
            $queryArgs['order'] = 'DESC';
        }

        return $queryArgs;
    }

    /**
     * Aplica feed personalizado a la query.
     *
     * @param array $queryArgs Query args
     * @param int $usuarioActual Usuario actual
     * @param string $identifier Identificador
     * @param int|null $similarTo Post similar
     * @param int $paged Página
     * @param bool $isAdmin Es admin
     * @param int $posts Posts por página
     * @param string|null $tipoUsuario Tipo usuario
     * @param mixed $filtrosUsuario Filtros del usuario
     * @return array Query args modificados
     */
    private function aplicarFeedPersonalizado(
        array $queryArgs,
        int $usuarioActual,
        string $identifier,
        ?int $similarTo,
        int $paged,
        bool $isAdmin,
        int $posts,
        ?string $tipoUsuario,
        $filtrosUsuario = null
    ): array {
        $feedResult = $this->feedService->obtenerFeedPersonalizado(
            $usuarioActual,
            $identifier,
            $similarTo,
            $paged,
            $isAdmin,
            $posts,
            $tipoUsuario,
            is_array($filtrosUsuario) ? $filtrosUsuario : null
        );

        if (!empty($feedResult['post_ids'])) {
            $postIds = $feedResult['post_ids'];

            if (count($postIds) > self::POST_IN_LIMIT) {
                $postIds = array_slice($postIds, 0, self::POST_IN_LIMIT);
            }

            $queryArgs['post__in'] = $postIds;
            $queryArgs['orderby'] = 'post__in';

            if (!empty($feedResult['post_not_in'])) {
                $queryArgs['post__not_in'] = $feedResult['post_not_in'];
            }
        } else {
            $queryArgs['orderby'] = 'date';
            $queryArgs['order'] = 'DESC';
        }

        return $queryArgs;
    }

    /**
     * Ordenamiento especial para colecciones.
     *
     * @param array $queryArgs Query args
     * @param int|string $filtroTiempo Filtro tiempo
     * @param int $usuarioActual Usuario actual
     * @return array Query args modificados
     */
    public function ordenamientoColecciones(array $queryArgs, int|string $filtroTiempo, int $usuarioActual): array
    {
        global $wpdb;
        $likesTable = $wpdb->prefix . 'post_likes';

        $cacheKey = 'colecciones_ordenadas_' . $usuarioActual . '_' . $filtroTiempo . '_' . mt_rand();
        $cachedData = $this->cacheService->obtener($cacheKey);

        if ($cachedData) {
            $queryArgs['post__in'] = $cachedData;
            $queryArgs['orderby'] = 'post__in';
            return $queryArgs;
        }

        /* Obtener IDs excluidos (colecciones genéricas) */
        $excludedIds = $this->obtenerColeccionesExcluidas($usuarioActual);

        /* Obtener colecciones populares */
        $popularIds = $this->obtenerColeccionesPopulares($likesTable);

        /* Construir lista final con pesos */
        $orderedIds = $this->ordenarColeccionesPorPeso($excludedIds, $popularIds);

        $this->cacheService->guardar($cacheKey, $orderedIds, 3600);

        $queryArgs['post__in'] = $orderedIds;
        $queryArgs['orderby'] = 'post__in';
        $queryArgs['post__not_in'] = $excludedIds;

        return $queryArgs;
    }

    /**
     * Obtiene IDs de colecciones a excluir.
     *
     * @param int $usuarioActual Usuario actual
     * @return array IDs excluidos
     */
    private function obtenerColeccionesExcluidas(int $usuarioActual): array
    {
        global $wpdb;

        $excludedTitles = ['Usar más tarde', 'Favoritos', 'test'];
        $titleConditions = array_map(function ($title) use ($wpdb) {
            return $wpdb->prepare("post_title LIKE %s", '%' . $wpdb->esc_like($title) . '%');
        }, $excludedTitles);
        $whereTitle = implode(' OR ', $titleConditions);

        if ($usuarioActual) {
            return $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND ({$whereTitle}) AND post_author != %d",
                    $usuarioActual
                )
            );
        }

        return $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND ({$whereTitle})"
        );
    }

    /**
     * Obtiene colecciones populares por likes.
     *
     * @param string $likesTable Tabla de likes
     * @return array Resultados con ID y like_count
     */
    private function obtenerColeccionesPopulares(string $likesTable): array
    {
        global $wpdb;
        $interval = 30;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, COUNT(pl.post_id) as like_count
                FROM {$wpdb->posts} p
                LEFT JOIN {$likesTable} pl ON p.ID = pl.post_id
                WHERE p.post_type = 'colecciones'
                AND p.post_status = 'publish'
                AND pl.like_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY p.ID
                ORDER BY like_count DESC, p.post_date DESC",
                $interval
            ),
            ARRAY_A
        );
    }

    /**
     * Ordena colecciones por peso (likes).
     *
     * @param array $excludedIds IDs excluidos
     * @param array $popularIds Colecciones populares
     * @return array IDs ordenados
     */
    private function ordenarColeccionesPorPeso(array $excludedIds, array $popularIds): array
    {
        global $wpdb;

        $popularIdsList = [];
        $weights = [];

        foreach ($popularIds as $post) {
            $popularIdsList[] = $post['ID'];
            $weights[$post['ID']] = $post['like_count'] * 2;
        }

        $excludedIds = is_array($excludedIds) ? $excludedIds : [];
        $popularIdsList = is_array($popularIdsList) ? $popularIdsList : [];

        if (empty($excludedIds) && empty($popularIdsList)) {
            $allIds = $wpdb->get_col(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish'"
            );
        } else {
            $merged = array_merge($excludedIds, $popularIdsList);
            $placeholder = implode(',', array_fill(0, count($merged), '%d'));
            $allIds = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND ID NOT IN ({$placeholder})",
                    $merged
                )
            );
            $allIds = array_unique(array_merge($popularIdsList, $allIds));
        }

        foreach ($allIds as $id) {
            if (!isset($weights[$id])) {
                $weights[$id] = 1;
            }
        }

        return $this->seleccionRandomPonderada($weights, count($allIds));
    }

    /**
     * Selección aleatoria ponderada.
     *
     * @param array $weights Pesos [id => peso]
     * @param int $count Cantidad a seleccionar
     * @return array IDs seleccionados
     */
    private function seleccionRandomPonderada(array $weights, int $count): array
    {
        $orderedIds = [];
        $tempWeights = $weights;

        while (count($orderedIds) < $count && !empty($tempWeights)) {
            $suma = array_sum($tempWeights);
            if ($suma <= 0) break;

            $rand = mt_rand(1, $suma);
            $acumulado = 0;

            foreach ($tempWeights as $item => $peso) {
                $acumulado += $peso;
                if ($rand <= $acumulado) {
                    $orderedIds[] = $item;
                    unset($tempWeights[$item]);
                    break;
                }
            }
        }

        return $orderedIds;
    }

    /**
     * Ordenamiento especial para tareas.
     *
     * @param array $queryArgs Query args
     * @param int $usu Usuario actual
     * @param array $args Argumentos
     * @param bool $prioridad Si ordenar por prioridad
     * @return array Query args modificados
     */
    public function ordenamientoTareas(array $queryArgs, int $usu, array $args, bool $prioridad = false): array
    {
        /* Implementación delegada a función existente si existe */
        if (function_exists('ordenamientoTareas')) {
            return ordenamientoTareas($queryArgs, $usu, $args, $prioridad);
        }

        return $queryArgs;
    }

    /**
     * Aplica filtros de usuario a la query.
     *
     * @param array $queryArgs Query args
     * @param int $usuarioActual Usuario actual
     * @return array Query args modificados
     */
    public function aplicarFiltrosUsuario(array $queryArgs, int $usuarioActual): array
    {
        $filtrosUsuario = get_user_meta($usuarioActual, 'filtroPost', true);

        if (empty($filtrosUsuario) || !is_array($filtrosUsuario)) {
            return $queryArgs;
        }

        $postNotIn = $queryArgs['post__not_in'] ?? [];
        $postIn = $queryArgs['post__in'] ?? [];

        /* Ocultar posts descargados */
        if (in_array('ocultarDescargados', $filtrosUsuario)) {
            $descargas = get_user_meta($usuarioActual, 'descargas', true) ?: [];
            if (!empty($descargas)) {
                $postNotIn = array_merge($postNotIn, array_keys($descargas));
            }
        }

        /* Ocultar posts en colección */
        if (in_array('ocultarEnColeccion', $filtrosUsuario)) {
            $samplesGuardados = get_user_meta($usuarioActual, 'samplesGuardados', true) ?: [];
            if (!empty($samplesGuardados)) {
                $postNotIn = array_merge($postNotIn, array_keys($samplesGuardados));
            }
        }

        /* Mostrar solo posts con like */
        if (in_array('mostrarMeGustan', $filtrosUsuario)) {
            $userLikedPostIds = $this->obtenerLikesDelUsuario($usuarioActual);
            if (!empty($userLikedPostIds)) {
                $postIn = !empty($postIn) ? array_intersect($postIn, $userLikedPostIds) : $userLikedPostIds;
                if (empty($postIn)) {
                    $queryArgs['posts_per_page'] = 0;
                }
            } else {
                $queryArgs['posts_per_page'] = 0;
            }
        }

        /* Resolver conflictos entre post__in y post__not_in */
        if (!empty($postIn) && !empty($postNotIn)) {
            $postIn = array_diff($postIn, $postNotIn);
            if (empty($postIn)) {
                $queryArgs['posts_per_page'] = 0;
            }
        }

        if (!empty($postIn)) {
            $queryArgs['post__in'] = $postIn;
        } else {
            unset($queryArgs['post__in']);
        }

        if (!empty($postNotIn)) {
            $queryArgs['post__not_in'] = $postNotIn;
        } else {
            unset($queryArgs['post__not_in']);
        }

        return $queryArgs;
    }

    /**
     * Obtiene los IDs de posts con like del usuario.
     *
     * @param int $userId ID del usuario
     * @return array IDs de posts
     */
    private function obtenerLikesDelUsuario(int $userId): array
    {
        $likeService = new LikeService();
        return $likeService->obtenerLikesDelUsuario($userId);
    }

    /**
     * Aplica filtro global a la query.
     *
     * @param array $queryArgs Query args
     * @param array $args Argumentos originales
     * @param int $usuarioActual Usuario actual
     * @param mixed $userId ID usuario perfil
     * @param string|null $tipoUsuario Tipo usuario
     * @return array Query args modificados
     */
    public function aplicarFiltroGlobal(
        array $queryArgs,
        array $args,
        int $usuarioActual,
        mixed $userId,
        ?string $tipoUsuario = null
    ): array {
        /* Convertir userId a int o null */
        $userIdInt = null;
        if (!empty($userId)) {
            $userIdInt = is_numeric($userId) ? (int)$userId : null;
        }

        /* Delegar a FiltroService si existe */
        if (class_exists('Kamples\\Services\\FiltroService')) {
            $filtroService = FiltroService::obtenerInstancia();
            return $filtroService->aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userIdInt, $tipoUsuario);
        }

        /* Fallback a función global */
        if (function_exists('aplicarFiltroGlobal')) {
            return aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userIdInt, $tipoUsuario);
        }

        return $queryArgs;
    }

    /**
     * Pre-filtra por identifier (búsqueda).
     *
     * @param string $identifier Término de búsqueda
     * @param array $queryArgs Query args
     * @return array Query args modificados
     */
    public function prefiltrarIdentifier(string $identifier, array $queryArgs): array
    {
        global $wpdb;

        $identifier = strtolower(trim($identifier));
        $queryArgs['s'] = $identifier;

        /* Separar términos positivos y negativos */
        $parts = explode('-', $identifier);
        $positiveTerms = $this->procesarTerminos(trim($parts[0]));

        $negativeTerms = [];
        for ($i = 1; $i < count($parts); $i++) {
            $negativeTerms = array_merge($negativeTerms, $this->procesarTerminos(trim($parts[$i])));
        }

        $normalizedPositive = $this->normalizarTerminos($positiveTerms);
        $normalizedNegative = $this->normalizarTerminos($negativeTerms);

        /* Agregar filtro de búsqueda personalizado */
        add_filter('posts_search', function ($search, $wp_query) use ($normalizedPositive, $normalizedNegative, $wpdb) {
            if (empty($normalizedPositive) && empty($normalizedNegative)) {
                return $search;
            }

            $searchConditions = [];

            /* Condiciones positivas */
            if (!empty($normalizedPositive)) {
                $termConditions = [];
                foreach ($normalizedPositive as $term) {
                    $likeTerm = '%' . $wpdb->esc_like($term) . '%';
                    $termConditions[] = $wpdb->prepare("
                        (
                            {$wpdb->posts}.post_title LIKE %s OR
                            {$wpdb->posts}.post_content LIKE %s OR
                            EXISTS (
                                SELECT 1 FROM {$wpdb->postmeta}
                                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                                AND {$wpdb->postmeta}.meta_key = 'datosAlgoritmo'
                                AND {$wpdb->postmeta}.meta_value LIKE %s
                            )
                            OR EXISTS (
                                SELECT 1 FROM {$wpdb->postmeta}
                                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                                AND {$wpdb->postmeta}.meta_key = 'nombreOriginal'
                                AND {$wpdb->postmeta}.meta_value LIKE %s
                            )
                        )
                    ", $likeTerm, $likeTerm, $likeTerm, $likeTerm);
                }
                $searchConditions[] = '(' . implode(' OR ', $termConditions) . ')';
            }

            /* Condiciones negativas */
            if (!empty($normalizedNegative)) {
                $termConditions = [];
                foreach ($normalizedNegative as $term) {
                    $likeTerm = '%' . $wpdb->esc_like($term) . '%';
                    $termConditions[] = $wpdb->prepare("
                        (
                            {$wpdb->posts}.post_title NOT LIKE %s AND
                            {$wpdb->posts}.post_content NOT LIKE %s AND
                            NOT EXISTS (
                                SELECT 1 FROM {$wpdb->postmeta}
                                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                                AND {$wpdb->postmeta}.meta_key = 'datosAlgoritmo'
                                AND {$wpdb->postmeta}.meta_value LIKE %s
                            )
                            AND NOT EXISTS (
                                SELECT 1 FROM {$wpdb->postmeta}
                                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                                AND {$wpdb->postmeta}.meta_key = 'nombreOriginal'
                                AND {$wpdb->postmeta}.meta_value LIKE %s
                            )
                        )
                    ", $likeTerm, $likeTerm, $likeTerm, $likeTerm);
                }
                $searchConditions[] = '(' . implode(' AND ', $termConditions) . ')';
            }

            if (!empty($searchConditions)) {
                $search = ' AND ' . implode(' AND ', $searchConditions);
            }

            return $search;
        }, 10, 2);

        return $queryArgs;
    }

    /**
     * Procesa cadena de términos en array.
     *
     * @param string $text Texto a procesar
     * @return array Términos
     */
    private function procesarTerminos(string $text): array
    {
        $terms = explode(' ', $text);
        return array_filter(array_map('trim', $terms));
    }

    /**
     * Normaliza términos (singular/plural).
     *
     * @param array $terms Términos originales
     * @return array Términos normalizados
     */
    private function normalizarTerminos(array $terms): array
    {
        $normalized = [];

        foreach ($terms as $term) {
            $normalized[] = $term;
            if (substr($term, -1) === 's') {
                $normalized[] = substr($term, 0, -1);
            } else {
                $normalized[] = $term . 's';
            }
        }

        return array_unique($normalized);
    }

    /**
     * Obtiene colecciones para el momento.
     *
     * @param array $args Argumentos
     * @param int $usuarioActual Usuario actual
     * @return string HTML de colecciones
     */
    public function obtenerColeccionesParaMomento(array $args, int $usuarioActual): string
    {
        if ($args['filtro'] !== 'momento' || ($args['tipoUsuario'] ?? '') === 'Fan') {
            return '';
        }

        $queryArgsForOrdering = [
            'post_type' => 'colecciones',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        $orderedArgs = $this->ordenamientoColecciones($queryArgsForOrdering, 'momento', $usuarioActual);

        if (empty($orderedArgs['post__in'])) {
            return '';
        }

        $topIds = array_slice($orderedArgs['post__in'], 0, 6);

        if (empty($topIds)) {
            return '';
        }

        $coleccionesQueryArgs = [
            'post_type' => 'colecciones',
            'post__in' => $topIds,
            'orderby' => 'post__in',
            'order' => 'ASC',
            'post_status' => 'publish',
            'posts_per_page' => 6,
        ];

        return $this->procesarPublicaciones($coleccionesQueryArgs, $args, false);
    }

    /**
     * Procesa publicaciones y genera HTML.
     *
     * @param array $queryArgs Query args
     * @param array $args Argumentos originales
     * @param bool $isAjax Si es AJAX
     * @return string HTML generado
     */
    public function procesarPublicaciones(array $queryArgs, array $args, bool $isAjax): string
    {
        ob_start();

        if (empty($queryArgs) || !is_array($queryArgs)) {
            return '';
        }

        try {
            $query = new \WP_Query($queryArgs);
            if (!is_a($query, 'WP_Query') || !method_exists($query, 'have_posts')) {
                return '';
            }
        } catch (\Exception $e) {
            return '';
        }

        $filtro = $args['filtro'] ?? '';
        $tipoPost = $args['post_type'];

        if (!wp_doing_ajax()) {
            $claseExtra = $this->obtenerClaseExtra($filtro);
            echo '<ul class="social-post-list ' . esc_attr($claseExtra) . '" 
                  data-filtro="' . esc_attr($filtro) . '" 
                  data-posttype="' . esc_attr($tipoPost) . '" 
                  data-tab-id="' . esc_attr($args['tab_id'] ?? '') . '">';
        }

        if ($filtro === 'notas' && function_exists('formNotas')) {
            echo formNotas();
        }

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                echo $this->renderizarPost($tipoPost, $filtro);
            }
        } else {
            if ($filtro !== 'notas' && function_exists('nohayPost')) {
                echo nohayPost($filtro, $isAjax);
            }
        }

        if (!wp_doing_ajax()) {
            echo '</ul>';
        }

        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Obtiene clase CSS extra según el filtro.
     *
     * @param string $filtro Filtro activo
     * @return string Clase CSS
     */
    private function obtenerClaseExtra(string $filtro): string
    {
        $claseExtra = 'clase-' . esc_attr($filtro);

        if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
            $claseExtra = 'clase-rolastatus';
        }

        if ($filtro === 'notas') {
            $claseExtra .= ' masonary';
        }

        return $claseExtra;
    }

    /**
     * Renderiza un post según su tipo.
     *
     * @param string $tipoPost Tipo de post
     * @param string $filtro Filtro activo
     * @return string HTML del post
     */
    private function renderizarPost(string $tipoPost, string $filtro): string
    {
        switch ($tipoPost) {
            case 'social_post':
                if ($filtro === 'rola' || $filtro === 'tiendaPerfil') {
                    return function_exists('htmlColec') ? htmlColec($filtro) : '';
                }
                return function_exists('htmlPost') ? htmlPost($filtro) : '';

            case 'colab':
                return function_exists('htmlColab') ? htmlColab($filtro) : '';

            case 'colecciones':
                return function_exists('htmlColec') ? htmlColec($filtro) : '';

            case 'tarea':
                return function_exists('htmlTareas') ? htmlTareas($filtro) : '';

            case 'notas':
                return function_exists('htmlNotas') ? htmlNotas($filtro) : '';

            case 'post':
                return function_exists('htmlArticulo') ? htmlArticulo($filtro) : '';

            default:
                return '<p>Tipo de publicación no reconocido.</p>';
        }
    }

    /**
     * Obtiene el tipo de usuario.
     *
     * @param array $args Argumentos
     * @param int $usuarioActual Usuario actual
     * @return string Tipo de usuario
     */
    private function obtenerTipoUsuario(array $args, int $usuarioActual): string
    {
        if (isset($args['tipoUsuario']) && !empty($args['tipoUsuario'])) {
            return $args['tipoUsuario'];
        }

        return get_user_meta($usuarioActual, 'tipoUsuario', true) ?: '';
    }

    /**
     * Obtiene el userId según contexto.
     *
     * @param bool $isAjax Si es AJAX
     * @return mixed User ID o null
     */
    public function obtenerUserId(bool $isAjax): mixed
    {
        if ($isAjax && isset($_POST['user_id'])) {
            return sanitize_text_field($_POST['user_id']);
        }

        $urlSegments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'));
        $indices = ['perfil', 'music', 'author', 'sello'];

        foreach ($indices as $index) {
            $pos = array_search($index, $urlSegments);
            if ($pos !== false) {
                if ($index === 'sello') {
                    return get_current_user_id();
                } elseif (isset($urlSegments[$pos + 1])) {
                    $usuario = get_user_by('slug', $urlSegments[$pos + 1]);
                    if ($usuario) {
                        return $usuario->ID;
                    }
                }
                break;
            }
        }

        return null;
    }

    /**
     * Obtiene valores por defecto para argumentos.
     *
     * @return array Defaults
     */
    private function obtenerDefaults(): array
    {
        return [
            'filtro' => '',
            'tab_id' => '',
            'posts' => 12,
            'exclude' => [],
            'post_type' => 'social_post',
            'similar_to' => null,
            'colec' => null,
            'idea' => null,
            'user_id' => null,
            'identifier' => '',
            'tipoUsuario' => '',
            'id' => '',
        ];
    }

    /**
     * Registra un mensaje en el log.
     *
     * @param string $nivel Nivel del log
     * @param string $mensaje Mensaje
     * @return void
     */
    private function log(string $nivel, string $mensaje): void
    {
        $logger = \Logger::obtenerInstancia();
        $logger->{$nivel}('ajaxPost', $mensaje);
    }
}
