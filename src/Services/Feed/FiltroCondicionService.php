<?php

namespace Kamples\Services\Feed;

/**
 * Servicio de definición de condiciones de filtro.
 * 
 * Proporciona las configuraciones de meta_query para cada tipo de filtro.
 * Responsabilidad única: definir las condiciones de cada filtro.
 *
 * @since 1.0.0
 */
class FiltroCondicionService
{
    private static ?FiltroCondicionService $instancia = null;

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene las condiciones de meta query para cada filtro.
     *
     * @param int $usuarioActual ID del usuario actual
     * @param string|null $tipoUsuario Tipo de usuario
     * @return array Mapa de filtros a condiciones
     */
    public function obtenerCondicionesMetaQuery(int $usuarioActual, ?string $tipoUsuario = null): array
    {
        return [
            'rolasEliminadas' => function (&$queryArgs) {
                $queryArgs['post_status'] = 'pending_deletion';
            },
            'rolasRechazadas' => function (&$queryArgs) {
                $queryArgs['post_status'] = 'rejected';
            },
            'rolasPendiente' => function (&$queryArgs) {
                $queryArgs['post_status'] = 'pending';
            },
            'likesRolas' => function (&$queryArgs) use ($usuarioActual) {
                if (function_exists('obtenerLikesDelUsuario')) {
                    $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
                    if ($userLikedPostIds) {
                        $queryArgs['post__in'] = $userLikedPostIds;
                    } else {
                        $queryArgs['posts_per_page'] = 0;
                    }
                }
            },
            'nada' => function (&$queryArgs) {
                $queryArgs['post_status'] = 'publish';
            },
            'colabs' => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
            'libres' => [
                ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
                ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
                ['key' => 'rola', 'value' => '1', 'compare' => '!='],
            ],
            'momento' => [
                ['key' => 'momento', 'value' => '1', 'compare' => '='],
                ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
            ],
            'sample' => function (&$queryArgs) use ($tipoUsuario) {
                if ($tipoUsuario === 'Fan') {
                    $queryArgs['post_status'] = 'publish';
                } else {
                    $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                        ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                        ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
                    ]);
                }
            },
            'rolaListLike' => function (&$queryArgs) use ($usuarioActual) {
                if (function_exists('obtenerLikesDelUsuario')) {
                    $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
                    if (!empty($userLikedPostIds)) {
                        $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                            'relation' => 'AND',
                            ['key' => 'rola', 'value' => '1', 'compare' => '='],
                            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
                        ]);
                        $queryArgs['post__in'] = $userLikedPostIds;
                    } else {
                        $queryArgs['posts_per_page'] = 0;
                    }
                }
            },
            'sampleList' => [
                'relation' => 'AND',
                ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
                [
                    'relation' => 'OR',
                    ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                    ['key' => 'tienda', 'value' => '1', 'compare' => '='],
                ],
            ],
            'colab' => function (&$queryArgs) {
                $queryArgs['post_status'] = 'publish';
            },
            'colabPendiente' => function (&$queryArgs) {
                $queryArgs['author'] = get_current_user_id();
                $queryArgs['post_status'] = 'pending';
            },
            'rola' => [
                ['key' => 'rola', 'value' => '1', 'compare' => '='],
                ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
            ],
        ];
    }
}
