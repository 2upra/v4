<?php

namespace App\Services\Post;

use WP_Error;

class PostCreationService
{
    /**
     * Creates a new WordPress post.
     *
     * @param string $tipoPost The post type.
     * @param string $estadoPost The post status.
     * @return int|WP_Error The ID of the new post on success, or WP_Error on failure.
     */
    public function crearPost($tipoPost = 'social_post', $estadoPost = 'publish')
    {
        $contenido = isset($_POST['textoNormal']) ? sanitize_textarea_field($_POST['textoNormal']) : '';
        $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';

        if (empty($contenido)) {
            error_log('Error en crearPost: El contenido no puede estar vacio.');
            return new WP_Error('empty_content', 'El contenido no puede estar vacio.');
        }

        $titulo = wp_trim_words($contenido, 15, '...');
        $autor = get_current_user_id();

        $idPost = wp_insert_post([
            'post_title'   => $titulo,
            'post_content' => $contenido,
            'post_status'  => $estadoPost,
            'post_author'  => $autor,
            'post_type'    => $tipoPost,
        ]);

        if (is_wp_error($idPost)) {
            $mensajeError = str_replace("\n", " | ", $idPost->get_error_message());
            error_log('Error en crearPost: Error al insertar el post. Detalles: ' . $mensajeError);
            return $idPost;
        }

        return $idPost;
    }
}
