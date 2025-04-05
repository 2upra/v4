<?php

// La función procesarComentario() y su hook AJAX se movieron a app/Services/CommentService.php

/**
 * Redirige los accesos directos a las entradas de tipo 'comentarios'
 * a la entrada original a la que pertenecen.
 */
function redirigir_comentarios() {
    // Verifica si estamos en una página individual (singular)
    if (is_singular('comentarios')) {
        // Obtiene el ID del comentario actual
        $comment_id = get_the_ID();

        // Obtiene el ID del post original al que pertenece el comentario
        $post_id = get_post_meta($comment_id, 'postId', true);

        // Si se encuentra el ID del post original
        if ($post_id) {
            // Obtiene la URL del post original
            $post_url = get_permalink($post_id);

            // Realiza la redirección
            wp_redirect($post_url);
            exit; // Asegura que el script se detenga después de la redirección
        } else {
            // Si no se encuentra el post original, podrías redirigir a la página de inicio o mostrar un error.
            // Por ejemplo, redirigir a la página de inicio:
            wp_redirect(home_url());
            exit;
        }
    }
}
add_action('template_redirect', 'redirigir_comentarios');
