<?php

namespace Kamples\Services\Publicacion;

use Kamples\Services\Feed\FeedService;
use Kamples\Services\Core\CacheService;

/**
 * Servicio de ordenamiento de publicaciones.
 * 
 * Maneja toda la lógica de ordenamiento: por fecha, likes,
 * feed personalizado, colecciones y tareas.
 *
 * @since 1.0.0
 */
class PublicacionOrdenamientoService
{
    private FeedService $feedService;
    private CacheService $cacheService;
    private static ?PublicacionOrdenamientoService $instancia = null;

    private const POST_IN_LIMIT = 500;

    public function __construct()
    {
        $this->feedService = FeedService::obtenerInstancia();
        $this->cacheService = CacheService::obtenerInstancia('feed');
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
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
            return $this->aplicarFeedPersonalizado(
                $queryArgs,
                $usuarioActual,
                $identifier,
                $similarTo,
                $paged,
                $isAdmin,
                $posts,
                $tipoUsuario
            );
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
     */
    public function ordenamientoPorLikes(array $queryArgs, string $likesTable, string $interval): array
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
     */
    public function aplicarFeedPersonalizado(
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

        $excludedIds = $this->obtenerColeccionesExcluidas($usuarioActual);
        $popularIds = $this->obtenerColeccionesPopulares($likesTable);
        $orderedIds = $this->ordenarColeccionesPorPeso($excludedIds, $popularIds);

        $this->cacheService->guardar($cacheKey, $orderedIds, 3600);

        $queryArgs['post__in'] = $orderedIds;
        $queryArgs['orderby'] = 'post__in';
        $queryArgs['post__not_in'] = $excludedIds;

        return $queryArgs;
    }

    /**
     * Obtiene IDs de colecciones a excluir.
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
     */
    public function ordenamientoTareas(array $queryArgs, int $usu, array $args, bool $prioridad = false): array
    {
        if (function_exists('ordenamientoTareas')) {
            return ordenamientoTareas($queryArgs, $usu, $args, $prioridad);
        }

        return $queryArgs;
    }

    private function log(string $nivel, string $mensaje): void
    {
        $logger = \Logger::obtenerInstancia();
        $logger->{$nivel}('ajaxPost', $mensaje);
    }
}
