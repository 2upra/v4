<?php

namespace Kamples\Services;

/**
 * Servicio de gestión de filtros para posts.
 * 
 * Proporciona lógica para aplicar filtros globales, 
 * filtros por autor, filtros de usuario y condiciones meta query.
 *
 * @since 1.0.0
 */
class FiltroService
{
    private static ?FiltroService $instancia = null;

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
     * Aplica el filtro global a los argumentos de query.
     *
     * @param array $queryArgs Argumentos de la query
     * @param array $args Argumentos adicionales
     * @param int $usuarioActual ID del usuario actual
     * @param int|null $userId ID del usuario para filtrar por autor
     * @param string|null $tipoUsuario Tipo de usuario
     * @return array Query args modificados
     */
    public function aplicarFiltroGlobal(
        array $queryArgs,
        array $args,
        int $usuarioActual,
        ?int $userId = null,
        ?string $tipoUsuario = null
    ): array {
        $filtro = $args['filtro'] ?? 'nada';

        if (!empty($userId)) {
            $queryArgs = $this->aplicarFiltroPorAutor($queryArgs, $userId, $filtro);
        } else {
            $queryArgs = $this->aplicarFiltrosDeUsuario($queryArgs, $usuarioActual, $filtro);
            $queryArgs = $this->aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario);
        }

        return $queryArgs;
    }

    /**
     * Aplica filtro por autor.
     *
     * @param array $queryArgs Argumentos de la query
     * @param int $userId ID del autor
     * @param string $filtro Tipo de filtro
     * @return array Query args modificados
     */
    public function aplicarFiltroPorAutor(array $queryArgs, int $userId, string $filtro): array
    {
        $queryArgs['author'] = $userId;
        $metaQuery = $queryArgs['meta_query'] ?? [];

        if ($filtro === 'imagenesPerfil') {
            $metaQuery = array_merge($metaQuery, [
                ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
                ['key' => 'post_audio_lite', 'compare' => 'NOT EXISTS'],
            ]);
        } elseif ($filtro === 'tiendaPerfil') {
            $metaQuery = array_merge($metaQuery, [
                ['key' => 'tienda', 'value' => '1', 'compare' => '='],
                ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
            ]);
        }

        $queryArgs['meta_query'] = $metaQuery;
        return $queryArgs;
    }

    /**
     * Aplica filtros específicos del usuario.
     *
     * @param array $queryArgs Argumentos de la query
     * @param int $usuarioId ID del usuario
     * @param string $filtro Tipo de filtro
     * @return array Query args modificados
     */
    public function aplicarFiltrosDeUsuario(array $queryArgs, int $usuarioId, string $filtro): array
    {
        $filtrosUsuario = get_user_meta($usuarioId, 'filtroPost', true);

        if (in_array($filtro, ['sampleList', 'notas', 'colecciones'])) {
            if ($filtro === 'sampleList' && is_array($filtrosUsuario) && in_array('misPost', $filtrosUsuario)) {
                $queryArgs['author'] = $usuarioId;
            } elseif ($filtro === 'notas') {
                $queryArgs['author'] = $usuarioId;
            } elseif ($filtro === 'colecciones' && is_array($filtrosUsuario) && in_array('misColecciones', $filtrosUsuario)) {
                $queryArgs['author'] = $usuarioId;
            }
        }

        if (($filtro === 'tarea' || $filtro === 'tareaPrioridad') && is_array($filtrosUsuario) && in_array('ocultarCompletadas', $filtrosUsuario)) {
            $queryArgs['author'] = $usuarioId;
            $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                [
                    'key' => 'estado',
                    'value' => 'completada',
                    'compare' => '!=',
                ],
            ]);
        }

        return $queryArgs;
    }

    /**
     * Aplica condiciones de meta query según el filtro.
     *
     * @param array $queryArgs Argumentos de la query
     * @param string $filtro Tipo de filtro
     * @param int $usuarioActual ID del usuario actual
     * @param string|null $tipoUsuario Tipo de usuario
     * @return array Query args modificados
     */
    public function aplicarCondicionesDeMetaQuery(
        array $queryArgs,
        string $filtro,
        int $usuarioActual,
        ?string $tipoUsuario = null
    ): array {
        $condiciones = $this->obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario);

        if (isset($condiciones[$filtro])) {
            $resultado = $condiciones[$filtro];
            if (is_callable($resultado)) {
                $resultado($queryArgs);
            } else {
                $queryArgs['post_status'] = 'publish';
                $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], $resultado);
            }
        }

        return $queryArgs;
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

    /**
     * Obtiene el filtro de tiempo actual del usuario.
     *
     * @param int $userId ID del usuario
     * @return array Datos del filtro
     */
    public function obtenerFiltroActual(int $userId): array
    {
        $filtroTiempo = intval(get_user_meta($userId, 'filtroTiempo', true) ?: 0);
        $nombresFiltros = ['Feed', 'Reciente', 'Semanal', 'Mensual'];

        return [
            'filtroTiempo' => $filtroTiempo,
            'nombreFiltro' => $nombresFiltros[$filtroTiempo] ?? 'Feed'
        ];
    }

    /**
     * Guarda el filtro de tiempo del usuario.
     *
     * @param int $userId ID del usuario
     * @param int $filtroTiempo Valor del filtro
     * @return bool
     */
    public function guardarFiltroTiempo(int $userId, int $filtroTiempo): bool
    {
        return (bool)update_user_meta($userId, 'filtroTiempo', $filtroTiempo);
    }

    /**
     * Guarda los filtros de post del usuario.
     *
     * @param int $userId ID del usuario
     * @param array $filtros Filtros a guardar
     * @return bool
     */
    public function guardarFiltroPost(int $userId, array $filtros): bool
    {
        return (bool)update_user_meta($userId, 'filtroPost', $filtros);
    }

    /**
     * Obtiene los filtros de post del usuario.
     *
     * @param int $userId ID del usuario
     * @return array
     */
    public function obtenerFiltros(int $userId): array
    {
        $filtros = get_user_meta($userId, 'filtroPost', true);
        return is_array($filtros) ? $filtros : [];
    }

    /**
     * Obtiene todos los filtros del usuario.
     *
     * @param int $userId ID del usuario
     * @return array
     */
    public function obtenerFiltrosTotal(int $userId): array
    {
        return [
            'filtroPost' => $this->obtenerFiltros($userId),
            'filtroTiempo' => get_user_meta($userId, 'filtroTiempo', true) ?: 0,
        ];
    }

    /**
     * Restablece los filtros del usuario.
     *
     * @param int $userId ID del usuario
     * @param bool $restablecerPost Si restablecer filtros de post
     * @param bool $restablecerColeccion Si restablecer filtros de colección
     * @return bool
     */
    public function restablecerFiltros(int $userId, bool $restablecerPost = false, bool $restablecerColeccion = false): bool
    {
        $filtroPost = get_user_meta($userId, 'filtroPost', true);

        if (is_string($filtroPost)) {
            $filtroPostArray = @unserialize($filtroPost);
            if ($filtroPostArray === false && $filtroPost !== 'b:0;') {
                $filtroPostArray = [];
            }
        } elseif (is_array($filtroPost)) {
            $filtroPostArray = $filtroPost;
        } else {
            $filtroPostArray = [];
        }

        if ($restablecerPost) {
            $filtrosAEliminar = ['misPost', 'mostrarMeGustan', 'ocultarEnColeccion', 'ocultarDescargados'];
            if (is_array($filtroPostArray)) {
                $filtroPostArray = array_values(array_filter($filtroPostArray, function ($filtro) use ($filtrosAEliminar) {
                    return !in_array($filtro, $filtrosAEliminar);
                }));
            }
        }

        if ($restablecerColeccion) {
            if (is_array($filtroPostArray)) {
                $filtroPostArray = array_values(array_filter($filtroPostArray, function ($filtro) {
                    return $filtro !== 'misColecciones';
                }));
            }
        }

        if (empty($filtroPostArray)) {
            delete_user_meta($userId, 'filtroPost');
        } else {
            update_user_meta($userId, 'filtroPost', $filtroPostArray);
        }

        delete_user_meta($userId, 'filtroTiempo');

        return true;
    }
}
