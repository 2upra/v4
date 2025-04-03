<?

// Funciones y hooks relacionados con el estado del post movidos a app/Services/Post/PostStatusService.php

add_action('wp_ajax_corregirTags', 'corregirTags');
add_action('wp_ajax_cambiarTitulo', 'cambiarTitulo');




add_action('wp_ajax_cambiarDescripcion', 'cambiarDescripcion');


add_action('wp_ajax_cambiar_imagen_post', 'cambiar_imagen_post_handler'); // Acción AJAX autenticada

function cambiar_imagen_post_handler() {
    if (empty($_POST['post_id']) || empty($_FILES['imagen'])) {
        wp_send_json_error(['message' => 'Faltan datos necesarios.']);
    }

    $post_id = intval($_POST['post_id']);

    // Verificar que el post existe
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(['message' => 'El post no existe.']);
    }

    // Verificar que el usuario actual sea el autor del post
    if ((int) $post->post_author !== get_current_user_id()) {
        wp_send_json_error(['message' => 'No tienes permisos para cambiar la imagen de este post.']);
    }

    // Procesar la imagen subida
    $file = $_FILES['imagen'];

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $upload = wp_handle_upload($file, ['test_form' => false]);

    if (isset($upload['error']) || !isset($upload['file'])) {
        wp_send_json_error(['message' => 'Error al subir la imagen: ' . $upload['error']]);
    }

    $file_path = $upload['file'];
    $file_url = $upload['url']; // URL de la imagen subida

    // Crear un attachment en la biblioteca de medios
    $attachment_id = wp_insert_attachment([
        'guid'           => $file_url,
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name($file['name']),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $file_path, $post_id);

    if (is_wp_error($attachment_id) || !$attachment_id) {
        wp_send_json_error(['message' => 'Error al guardar la imagen en la biblioteca de medios.']);
    }

    // Generar los metadatos de la imagen
    $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    // Establecer la imagen destacada del post
    set_post_thumbnail($post_id, $attachment_id);

    // Devolver la URL de la nueva imagen
    wp_send_json_success(['new_image_url' => $file_url]); // Asegurarse de devolver la URL correcta
}
