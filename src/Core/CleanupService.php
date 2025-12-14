<?php

/**
 * Servicio de limpieza automática
 * 
 * Maneja la eliminación programada de posts y adjuntos
 *
 * @package Kamples\Core
 * @since 1.0.0
 */

namespace Kamples\Core;

class CleanupService
{
    private static bool $inicializado = false;

    /**
     * Inicializa el servicio de limpieza
     */
    public static function inicializar(): void
    {
        if (self::$inicializado) {
            return;
        }

        self::$inicializado = true;

        /* Agregar intervalo semanal */
        add_filter('cron_schedules', [self::class, 'agregarIntervaloSemanal']);

        /* Programar tarea semanal si no existe */
        if (!wp_next_scheduled('eliminacionSemanal')) {
            wp_schedule_event(time(), 'weekly', 'eliminacionSemanal');
        }

        /* Registrar acción de limpieza */
        add_action('eliminacionSemanal', [self::class, 'eliminarPostsPendientes']);

        /* Eliminar adjuntos antes de borrar post */
        add_action('before_delete_post', [self::class, 'eliminarAdjuntosPost']);
    }

    /**
     * Agregar intervalo semanal a los cron schedules
     */
    public static function agregarIntervaloSemanal(array $schedules): array
    {
        $schedules['weekly'] = [
            'interval' => 7 * DAY_IN_SECONDS,
            'display' => __('Una vez por semana', 'kamples'),
        ];
        return $schedules;
    }

    /**
     * Eliminar posts pendientes y en papelera con más de 7 días
     */
    public static function eliminarPostsPendientes(): void
    {
        $args = [
            'post_type' => 'any',
            'post_status' => ['pending', 'trash'],
            'posts_per_page' => -1,
        ];

        $posts = get_posts($args);
        $ahora = current_time('timestamp');

        foreach ($posts as $post) {
            $ultimaModificacion = strtotime($post->post_modified_gmt);
            $diferenciaDias = ($ahora - $ultimaModificacion) / DAY_IN_SECONDS;

            if ($diferenciaDias >= 7) {
                wp_delete_post($post->ID, true);
            }
        }
    }

    /**
     * Eliminar adjuntos de un post antes de eliminarlo
     */
    public static function eliminarAdjuntosPost(int $postId): void
    {
        $adjuntos = get_attached_media('', $postId);

        foreach ($adjuntos as $adjunto) {
            wp_delete_attachment($adjunto->ID, true);

            $hashKeys = ['idHash_archivoId', 'idHash_audioId', 'idHash_imagenId'];
            foreach ($hashKeys as $key) {
                $id = get_post_meta($postId, $key, true);
                if ($id && function_exists('eliminarHash')) {
                    eliminarHash($id);
                }
            }
        }
    }
}
