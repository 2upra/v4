<?php

namespace Kamples\Services\Core;

use Kamples\Services\Feed\FeedService;

/**
 * Servicio de registro de vistas.
 * 
 * Maneja el tracking de vistas de posts por usuario.
 *
 * @since 1.0.0
 */
class VistaService
{
    private \Logger $logger;
    private FeedService $feedService;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->feedService = FeedService::obtenerInstancia();
    }

    /**
     * Registra una vista de un post para un usuario.
     *
     * @param int $postId ID del post.
     * @param int $userId ID del usuario (0 para anónimos).
     * @return array Datos de vistas actualizados.
     */
    public function registrarVista(int $postId, int $userId = 0): array
    {
        $vistasUsuario = [];
        $vistasTotalesUsuario = 0;

        if ($userId > 0) {
            $vistasUsuario = get_user_meta($userId, 'vistas_posts', true) ?: [];
            $vistasTotalesUsuario = (int) get_user_meta($userId, 'vistas_totales_usuario', true);

            $fechaActual = time();

            if (isset($vistasUsuario[$postId])) {
                $vistasUsuario[$postId]['count']++;
                $vistasUsuario[$postId]['last_view'] = $fechaActual;
            } else {
                $vistasUsuario[$postId] = [
                    'count' => 1,
                    'last_view' => $fechaActual,
                ];
            }

            $vistasTotalesUsuario++;

            update_user_meta($userId, 'vistas_posts', $vistasUsuario);
            update_user_meta($userId, 'vistas_totales_usuario', $vistasTotalesUsuario);

            /* Reiniciar feed cada 6 vistas */
            if ($vistasTotalesUsuario % 6 === 0) {
                $this->feedService->reiniciarFeed($userId);
            }
        }

        /* Actualizar vistas totales del post */
        $vistasTotalesPost = (int) get_post_meta($postId, 'vistas_totales', true);
        $vistasTotalesPost++;
        update_post_meta($postId, 'vistas_totales', $vistasTotalesPost);

        return [
            'vistas_usuario' => $vistasUsuario[$postId]['count'] ?? 0,
            'vistas_totales' => $vistasTotalesPost
        ];
    }

    /**
     * Obtiene las vistas de posts de un usuario.
     *
     * @param int $userId ID del usuario.
     * @return array Array de vistas por post ID.
     */
    public function obtenerVistasPosts(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $vistasPosts = get_user_meta($userId, 'vistas_posts', true);
        return empty($vistasPosts) ? [] : $vistasPosts;
    }

    /**
     * Limpia vistas antiguas de un usuario.
     *
     * @param array $vistas Array de vistas.
     * @param int $dias Número de días de antigüedad para eliminar.
     * @return array Vistas filtradas.
     */
    public function limpiarVistasAntiguas(array $vistas, int $dias): array
    {
        $fechaLimite = time() - (86400 * $dias);

        foreach ($vistas as $postId => $infoVista) {
            if (isset($infoVista['last_view']) && $infoVista['last_view'] < $fechaLimite) {
                unset($vistas[$postId]);
            }
        }

        return $vistas;
    }

    /**
     * Obtiene el total de vistas de un usuario.
     *
     * @param int $userId ID del usuario.
     * @return int Total de vistas.
     */
    public function obtenerVistasTotalesUsuario(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        return (int) get_user_meta($userId, 'vistas_totales_usuario', true);
    }

    /**
     * Obtiene las vistas totales de un post.
     *
     * @param int $postId ID del post.
     * @return int Total de vistas del post.
     */
    public function obtenerVistasTotalesPost(int $postId): int
    {
        return (int) get_post_meta($postId, 'vistas_totales', true);
    }
}
