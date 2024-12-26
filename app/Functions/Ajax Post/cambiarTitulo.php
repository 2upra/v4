<?

# Cambia el titulo sin usar IA
function cambiarTitulo()
{
    if (!is_user_logged_in()) {
        wp_die(json_encode(['success' => false, 'message' => 'No estás autorizado']));
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $titulo = sanitize_text_field($_POST['titulo'] ?? '');

    if ($post_id <= 0) {
        wp_die(json_encode(['success' => false, 'message' => 'ID de post no válido']));
    }

    $current_user = wp_get_current_user();
    $post = get_post($post_id);

    if (!$post || ($post->post_author != $current_user->ID && !current_user_can('administrator'))) {
        wp_die(json_encode(['success' => false, 'message' => 'No tienes permisos para editar este post']));
    }

    $post->post_title = $titulo;
    wp_update_post($post);

    wp_die(json_encode(['success' => true]));
}
