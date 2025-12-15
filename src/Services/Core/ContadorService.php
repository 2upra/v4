<?php

namespace Kamples\Services\Core;

use Kamples\Services\Feed\FiltroService;

/**
 * Servicio de conteo de posts filtrados.
 *
 * @since 1.0.0
 */
class ContadorService
{
    private static ?ContadorService $instancia = null;
    private FiltroService $filtroService;

    /**
     * Constructor del servicio.
     */
    public function __construct()
    {
        $this->filtroService = FiltroService::obtenerInstancia();
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
     * Cuenta los posts filtrados para un usuario.
     *
     * @param int $userId ID del usuario
     * @param string $postType Tipo de post
     * @param string $busqueda Texto de búsqueda
     * @param array $filtros Filtros adicionales
     * @return int Total de posts
     */
    public function contarPostsFiltrados(
        int $userId,
        string $postType = 'social_post',
        string $busqueda = '',
        array $filtros = []
    ): int {
        $queryArgs = [
            'post_type' => $postType,
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => -1,
        ];

        if (function_exists('aplicarFiltrosUsuario')) {
            $queryArgs = aplicarFiltrosUsuario($queryArgs, $userId);
        }

        if (!empty($busqueda) && function_exists('prefiltrarIdentifier')) {
            $queryArgs = prefiltrarIdentifier($busqueda, $queryArgs);
        }

        $query = new \WP_Query($queryArgs);
        return $query->found_posts;
    }
}
