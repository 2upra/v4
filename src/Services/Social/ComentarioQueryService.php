<?php

/**
 * Servicio de consultas para comentarios
 * 
 * Maneja la obtencion y formateo de comentarios
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ComentarioQueryService
{
    private static ?ComentarioQueryService $instancia = null;
    private int $comentariosPorPagina = 12;

    private function __construct() {}

    /**
     * Obtiene la instancia unica del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtener comentarios de un post con paginacion
     *
     * @param int $postId ID del post
     * @param int $pagina Numero de pagina (1-indexed)
     * @return array ['comentarios' => WP_Post[], 'hayMas' => bool, 'total' => int]
     */
    public function obtenerComentariosPost(int $postId, int $pagina = 1): array
    {
        $comentariosIds = get_post_meta($postId, 'comentarios_ids', true);

        if (empty($comentariosIds) || !is_array($comentariosIds)) {
            return [
                'comentarios' => [],
                'hayMas' => false,
                'total' => 0
            ];
        }

        $offset = ($pagina - 1) * $this->comentariosPorPagina;

        $args = [
            'post_type'      => 'comentarios',
            'post_status'    => 'publish',
            'posts_per_page' => $this->comentariosPorPagina,
            'offset'         => $offset,
            'post__in'       => $comentariosIds,
            'orderby'        => 'post__in',
        ];

        $query = new \WP_Query($args);
        $totalComentarios = count($comentariosIds);
        $hayMas = ($offset + $this->comentariosPorPagina) < $totalComentarios;

        return [
            'comentarios' => $query->posts,
            'hayMas' => $hayMas,
            'total' => $totalComentarios
        ];
    }

    /**
     * Obtener datos formateados de un comentario para renderizado
     *
     * @param \WP_Post $comentario Post de tipo comentario
     * @return array Datos del comentario formateados
     */
    public function formatearComentario(\WP_Post $comentario): array
    {
        $comentarioId = $comentario->ID;
        $autorId = $comentario->post_author;
        $autor = get_userdata($autorId);

        $audio = get_post_meta($comentarioId, 'post_audio_lite', true);
        $imagenPortada = get_the_post_thumbnail_url($comentarioId, 'full');
        $audioUrl = wp_get_attachment_url(
            get_post_meta($comentarioId, 'post_audio', true)
        );

        return [
            'id' => $comentarioId,
            'autorId' => $autorId,
            'autorNombre' => $autor ? $autor->display_name : 'Usuario desconocido',
            'contenido' => $comentario->post_content,
            'fecha' => $comentario->post_date,
            'fechaRelativa' => function_exists('tiempoRelativo')
                ? tiempoRelativo($comentario->post_date)
                : human_time_diff(strtotime($comentario->post_date), current_time('timestamp')),
            'avatar' => function_exists('imagenPerfil')
                ? imagenPerfil($autorId)
                : get_avatar_url($autorId),
            'imagenPortada' => $imagenPortada
                ? (function_exists('img') ? img($imagenPortada) : $imagenPortada)
                : '',
            'audio' => $audio,
            'audioUrl' => $audioUrl ?: '',
        ];
    }

    /**
     * Obtener el numero total de comentarios de un post
     *
     * @param int $postId ID del post
     * @return int Numero de comentarios
     */
    public function contarComentarios(int $postId): int
    {
        $comentariosIds = get_post_meta($postId, 'comentarios_ids', true);
        return is_array($comentariosIds) ? count($comentariosIds) : 0;
    }

    /**
     * Establecer comentarios por pagina
     *
     * @param int $cantidad Cantidad de comentarios por pagina
     */
    public function setComentariosPorPagina(int $cantidad): void
    {
        $this->comentariosPorPagina = $cantidad;
    }
}
