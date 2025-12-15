<?php

namespace Kamples\Services\Feed;

/**
 * Servicio de aplicacion de filtros a queries.
 * 
 * Responsabilidad unica: aplicar filtros a los argumentos de WP_Query.
 *
 * @since 1.0.0
 */
class FiltroAplicacionService
{
    private static ?FiltroAplicacionService $instancia = null;
    private FiltroCondicionService $condicionService;

    public function __construct()
    {
        $this->condicionService = FiltroCondicionService::obtenerInstancia();
    }

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
     * Aplica filtros especificos del usuario.
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
     * Aplica condiciones de meta query segun el filtro.
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
        $condiciones = $this->condicionService->obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario);

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
}
