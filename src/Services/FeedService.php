<?php

namespace Kamples\Services;

/**
 * Servicio de gestión del feed personalizado.
 * 
 * Maneja la lógica de obtención, cálculo y reinicio
 * del feed de publicaciones para cada usuario.
 *
 * @since 1.0.0
 */
class FeedService
{
    private CacheService $cache;
    private \wpdb $wpdb;
    private static ?FeedService $instancia = null;

    /**
     * Constructor del servicio.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->cache = CacheService::obtenerInstancia('feed');
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
     * Obtiene el feed personalizado para un usuario.
     *
     * @param int $idUsuario ID del usuario
     * @param string $identificador Identificador del feed
     * @param int|null $similar ID del post para posts similares
     * @param int $pagina Número de página
     * @param bool $esAdmin Si el usuario es administrador
     * @param int $postsPagina Posts por página
     * @param string|null $tipoUsuario Tipo de usuario (Fan, Artista, etc.)
     * @param array|null $filtrosUsuario Filtros del usuario
     * @return array Array con post_ids y post_not_in
     */
    public function obtenerFeedPersonalizado(
        int $idUsuario,
        string $identificador = '',
        ?int $similar = null,
        int $pagina = 1,
        bool $esAdmin = false,
        int $postsPagina = 12,
        ?string $tipoUsuario = null,
        ?array $filtrosUsuario = null
    ): array {
        try {
            if (!$idUsuario) {
                return ['post_ids' => [], 'post_not_in' => []];
            }

            $filtrosUsuario = get_user_meta($idUsuario, 'filtroPost', true);
            if (!is_array($filtrosUsuario)) {
                $filtrosUsuario = [];
            }

            if ($similar) {
                return $this->obtenerPostsSimilares($idUsuario, $similar);
            }

            $filtrosHash = $filtrosUsuario ? md5(serialize($filtrosUsuario)) : 'sin_filtros';
            $tipoUsuarioCache = $tipoUsuario ? md5($tipoUsuario) : 'sin_tipo';
            $cacheKey = ($idUsuario == 44)
                ? "feed_personalizado_user_44_{$identificador}_{$filtrosHash}_{$tipoUsuarioCache}"
                : "feed_personalizado_user_{$idUsuario}_{$identificador}_{$filtrosHash}_{$tipoUsuarioCache}";

            $cacheTiempo = $esAdmin ? 7200 : 43200;
            $cacheData = $this->cache->obtener($cacheKey);

            if ($cacheData && isset($cacheData['posts'])) {
                $posts = $cacheData['posts'];
            } else {
                $posts = $this->calcularFeed($idUsuario, $identificador, '', $tipoUsuario, $filtrosUsuario);

                if (!$posts) {
                    return ['post_ids' => [], 'post_not_in' => []];
                }

                $cacheContenido = ['posts' => $posts, 'timestamp' => time()];
                $this->cache->guardar($cacheKey, $cacheContenido, $cacheTiempo);
            }

            $postIds = is_array($posts) ? array_keys($posts) : [];

            if (defined('POSTINLIMIT') && count($postIds) > POSTINLIMIT) {
                $postIds = array_slice($postIds, 0, POSTINLIMIT);
            }

            return [
                'post_ids' => $postIds,
                'post_not_in' => [],
            ];
        } catch (\Exception $e) {
            $this->log('error', "Error en obtenerFeedPersonalizado: " . $e->getMessage());
            return ['post_ids' => [], 'post_not_in' => []];
        }
    }

    /**
     * Obtiene posts similares a un post específico.
     *
     * @param int $userId ID del usuario
     * @param int $similarTo ID del post de referencia
     * @return array
     */
    public function obtenerPostsSimilares(int $userId, int $similarTo): array
    {
        $postNotIn = [$similarTo];
        $cacheKey = "similar_to_{$similarTo}";

        $cachedData = $this->cache->obtener($cacheKey);

        if ($cachedData) {
            return [
                'post_ids' => is_array($cachedData) ? array_keys($cachedData) : [],
                'post_not_in' => $postNotIn,
            ];
        }

        $postsPersonalizados = $this->calcularFeed(44, '', (string)$similarTo);

        if (!$postsPersonalizados) {
            return ['post_ids' => [], 'post_not_in' => $postNotIn];
        }

        $this->cache->guardar($cacheKey, $postsPersonalizados, 15 * DAY_IN_SECONDS);

        return [
            'post_ids' => array_keys($postsPersonalizados),
            'post_not_in' => $postNotIn,
        ];
    }

    /**
     * Reinicia el feed de un usuario.
     *
     * @param int $userId ID del usuario
     * @return int Número de caches eliminadas
     */
    public function reiniciarFeed(int $userId): int
    {
        $tipoUsuario = get_user_meta($userId, 'tipoUsuario', true);
        $esAdmin = current_user_can('administrator');
        $eliminadas = 0;

        $this->log('info', "Iniciando reinicio de feed para usuario ID: {$userId}");

        $cacheKey = ($userId == 44)
            ? "feed_personalizado_user_44_"
            : "feed_personalizado_user_{$userId}_";

        $cacheTiempo = $esAdmin ? 7200 : 43200;

        $patron = ($userId == 44)
            ? "feed_personalizado_anonymous_*"
            : "feed_personalizado_user_{$userId}_*";

        $eliminadas = $this->cache->borrarPorPatron($patron);

        if ($eliminadas > 0) {
            $this->log('info', "Caches eliminadas: {$eliminadas} para usuario ID: {$userId}");

            $postsPersonalizados = $this->calcularFeed($userId, '', '', $tipoUsuario);

            if ($postsPersonalizados) {
                $cacheContenido = ['posts' => $postsPersonalizados, 'timestamp' => time()];
                $this->cache->guardar($cacheKey, $cacheContenido, $cacheTiempo);
            }
        }

        $this->cache->borrar('feed_datos_' . $userId);
        $this->log('info', "Reinicio de feed completado para usuario ID: {$userId}");

        return $eliminadas;
    }

    /**
     * Obtiene datos del feed con cache.
     *
     * @param int $userId ID del usuario
     * @return array Datos del feed
     */
    public function obtenerDatosFeedConCache(int $userId): array
    {
        $cacheKey = 'feed_datos_' . $userId;
        $datos = $this->cache->obtener($cacheKey);

        if ($datos === false) {
            $datos = $this->obtenerDatosFeed($userId);
            $this->cache->guardar($cacheKey, $datos, 43200);
        }

        if (!isset($datos['author_results']) || !is_array($datos['author_results'])) {
            return [];
        }

        return $datos;
    }

    /**
     * Obtiene todos los datos necesarios para calcular el feed.
     *
     * @param int $userId ID del usuario
     * @return array Datos del feed
     */
    public function obtenerDatosFeed(int $userId): array
    {
        $tiempoInicio = microtime(true);

        try {
            if (!$this->comprobarConexionBD() || !$userId) {
                return [];
            }

            $siguiendo = $this->obtenerUsuariosSeguidos($userId);
            $intereses = $this->obtenerInteresesUsuario($userId);
            $vistas = $this->vistasDatos($userId);

            /* Generar/actualizar intereses del usuario */
            $interesService = InteresService::obtenerInstancia();
            $interesService->generarMetaDeIntereses($userId);

            $postsIds = $this->obtenerIdsPostsRecientes();
            if (empty($postsIds)) {
                return [];
            }

            $cacheKey = 'metaData_' . md5(implode('_', $postsIds));
            $metaData = $this->cache->obtener($cacheKey);

            if ($metaData === false) {
                $metaData = $this->obtenerMetadatosPosts($postsIds);
                $this->cache->guardar($cacheKey, $metaData, 14400);
            }

            $metaRoles = $this->procesarMetadatosRoles($metaData);
            $likesPorPost = $this->obtenerLikesPorPost($postsIds);
            $postsResultados = $this->obtenerDatosBasicosPosts($postsIds);
            $postContenido = $this->procesarContenidoPosts($postsResultados);

            $tiempoTotal = microtime(true) - $tiempoInicio;
            $this->log('info', "Feed obtenido para usuario {$userId} en {$tiempoTotal}s");

            return [
                'siguiendo' => $siguiendo,
                'interesesUsuario' => $intereses,
                'posts_ids' => $postsIds,
                'likes_by_post' => $likesPorPost,
                'meta_data' => $metaData,
                'meta_roles' => $metaRoles,
                'author_results' => $postsResultados,
                'post_content' => $postContenido,
            ];
        } catch (\Exception $e) {
            $this->log('error', "Error en obtenerDatosFeed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los usuarios seguidos por un usuario.
     *
     * @param int $userId ID del usuario
     * @return array IDs de usuarios seguidos
     */
    public function obtenerUsuariosSeguidos(int $userId): array
    {
        $siguiendo = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT meta_value 
                 FROM {$this->wpdb->usermeta} 
                 WHERE user_id = %d AND meta_key = 'siguiendo'",
                $userId
            )
        );

        if (empty($siguiendo)) {
            return [];
        }

        $resultado = maybe_unserialize($siguiendo[0]);
        return is_array($resultado) ? $resultado : [];
    }

    /**
     * Obtiene los intereses del usuario.
     *
     * @param int $userId ID del usuario
     * @return array|object Intereses del usuario
     */
    public function obtenerInteresesUsuario(int $userId)
    {
        if (!defined('INTERES_TABLE')) {
            return [];
        }

        $tablaIntereses = INTERES_TABLE;
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT interest, intensity FROM {$tablaIntereses} WHERE user_id = %d",
                $userId
            ),
            OBJECT_K
        );
    }

    /**
     * Obtiene las vistas del usuario.
     *
     * @param int $userId ID del usuario
     * @return mixed Datos de vistas
     */
    public function vistasDatos(int $userId): mixed
    {
        return get_user_meta($userId, 'vistas_posts', true);
    }

    /**
     * Obtiene IDs de posts recientes.
     *
     * @return array IDs de posts
     */
    public function obtenerIdsPostsRecientes(): array
    {
        $args = [
            'post_type' => 'social_post',
            'posts_per_page' => 50000,
            'date_query' => [
                'after' => date('Y-m-d', strtotime('-365 days'))
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        return get_posts($args);
    }

    /**
     * Obtiene metadatos de los posts.
     *
     * @param array $postsIds IDs de posts
     * @return array Metadatos indexados por post_id
     */
    public function obtenerMetadatosPosts(array $postsIds): array
    {
        if (empty($postsIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));
        $metaKeys = ['datosAlgoritmo', 'Verificado', 'postAut', 'artista', 'fan', 'nombreOriginal'];
        $metaKeysPlaceholders = implode(',', array_fill(0, count($metaKeys), '%s'));

        $sql = "
            SELECT post_id, meta_key, meta_value
            FROM {$this->wpdb->postmeta}
            WHERE meta_key IN ({$metaKeysPlaceholders}) AND post_id IN ({$placeholders})
        ";

        $preparedSql = $this->wpdb->prepare($sql, array_merge($metaKeys, $postsIds));
        $resultados = $this->wpdb->get_results($preparedSql);

        $metaData = [];
        foreach ($resultados as $meta) {
            $metaData[$meta->post_id][$meta->meta_key] = $meta->meta_value;
        }

        return $metaData;
    }

    /**
     * Procesa los metadatos de roles.
     *
     * @param array $metaData Metadatos de posts
     * @return array Roles procesados
     */
    public function procesarMetadatosRoles(array $metaData): array
    {
        $metaRoles = [];
        foreach ($metaData as $postId => $meta) {
            $metaRoles[$postId] = [
                'artista' => isset($meta['artista']) ? filter_var($meta['artista'], FILTER_VALIDATE_BOOLEAN) : false,
                'fan' => isset($meta['fan']) ? filter_var($meta['fan'], FILTER_VALIDATE_BOOLEAN) : false,
            ];
        }
        return $metaRoles;
    }

    /**
     * Obtiene likes por post.
     *
     * @param array $postsIds IDs de posts
     * @return array Likes indexados por post_id
     */
    public function obtenerLikesPorPost(array $postsIds): array
    {
        if (empty($postsIds)) {
            return [];
        }

        $tablaLikes = "{$this->wpdb->prefix}post_likes";
        $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));

        $sql = "
            SELECT post_id, like_type, COUNT(*) as cantidad
            FROM {$tablaLikes}
            WHERE post_id IN ({$placeholders})
            GROUP BY post_id, like_type
        ";

        $preparedSql = $this->wpdb->prepare($sql, $postsIds);
        $resultados = $this->wpdb->get_results($preparedSql);

        $likesPorPost = [];
        foreach ($resultados as $like) {
            if (!isset($likesPorPost[$like->post_id])) {
                $likesPorPost[$like->post_id] = [
                    'like' => 0,
                    'favorito' => 0,
                    'no_me_gusta' => 0
                ];
            }
            $likesPorPost[$like->post_id][$like->like_type] = (int)$like->cantidad;
        }

        return $likesPorPost;
    }

    /**
     * Obtiene datos básicos de posts.
     *
     * @param array $postsIds IDs de posts
     * @return array|object Datos de posts
     */
    public function obtenerDatosBasicosPosts(array $postsIds)
    {
        if (empty($postsIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));

        $sql = "
            SELECT ID, post_author, post_date, post_content
            FROM {$this->wpdb->posts}
            WHERE ID IN ({$placeholders})
        ";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $postsIds),
            OBJECT_K
        );
    }

    /**
     * Procesa el contenido de los posts.
     *
     * @param array|object $postsResultados Resultados de posts
     * @return array Contenido indexado por ID
     */
    public function procesarContenidoPosts($postsResultados): array
    {
        $postContenido = [];
        foreach ($postsResultados as $post) {
            $postContenido[$post->ID] = $post->post_content;
        }
        return $postContenido;
    }

    /**
     * Comprueba la conexión a la base de datos.
     *
     * @return bool
     */
    private function comprobarConexionBD(): bool
    {
        return $this->wpdb !== null;
    }

    /**
     * Calcula el feed personalizado usando el AlgoritmoService.
     * 
     * @param int $userId ID del usuario
     * @param string $identificador Identificador
     * @param string $similar Similar
     * @param string|null $tipoUsuario Tipo de usuario
     * @param array|null $filtrosUsuario Filtros
     * @return array
     */
    public function calcularFeed(
        int $userId,
        string $identificador = '',
        string $similar = '',
        ?string $tipoUsuario = null,
        ?array $filtrosUsuario = null
    ): array {
        $algoritmoService = AlgoritmoService::obtenerInstancia();
        $similarTo = !empty($similar) ? (int)$similar : null;

        return $algoritmoService->calcularFeedPersonalizado(
            $userId,
            $identificador,
            $similarTo,
            $tipoUsuario
        );
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
        $logger->{$nivel}('algoritmo', $mensaje);
    }
}
