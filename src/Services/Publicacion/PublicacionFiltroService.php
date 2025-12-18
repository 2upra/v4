<?php

namespace Kamples\Services\Publicacion;

use Kamples\Services\Social\LikeService;
use Kamples\Services\Feed\FiltroService;

/**
 * Servicio de filtrado de publicaciones.
 * 
 * Maneja los filtros de usuario (descargados, en colección, me gusta)
 * y el filtro global de publicaciones.
 *
 * @since 1.0.0
 */
class PublicacionFiltroService
{
    private static ?PublicacionFiltroService $instancia = null;

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
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

        /* Ocultar posts en coleccion */
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

        $filtroService = FiltroService::obtenerInstancia();
        return $filtroService->aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userIdInt, $tipoUsuario);
    }

    /**
     * Pre-filtra por identifier (busqueda).
     *
     * @param string $identifier Termino de busqueda
     * @param array $queryArgs Query args
     * @return array Query args modificados
     */
    public function prefiltrarIdentifier(string $identifier, array $queryArgs): array
    {
        global $wpdb;

        $identifier = strtolower(trim($identifier));
        $queryArgs['s'] = $identifier;

        /* Separar terminos positivos y negativos */
        $parts = explode('-', $identifier);
        $positiveTerms = $this->procesarTerminos(trim($parts[0]));

        $negativeTerms = [];
        for ($i = 1; $i < count($parts); $i++) {
            $negativeTerms = array_merge($negativeTerms, $this->procesarTerminos(trim($parts[$i])));
        }

        $normalizedPositive = $this->normalizarTerminos($positiveTerms);
        $normalizedNegative = $this->normalizarTerminos($negativeTerms);

        /* Agregar filtro de busqueda personalizado */
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
     * Procesa cadena de terminos en array.
     */
    private function procesarTerminos(string $text): array
    {
        $terms = explode(' ', $text);
        return array_filter(array_map('trim', $terms));
    }

    /**
     * Normaliza terminos (singular/plural).
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
}
