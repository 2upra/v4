<?php

function guardarWaveImagen()
{
    if (!isset($_FILES['image']) || !isset($_POST['post_id'])) {
        wp_send_json_error('Datos incompletos');
        return;
    }

    $file = $_FILES['image'];
    $post_id = intval($_POST['post_id']);

    // Eliminar la imagen anterior si waveCargada es false.
    if (get_post_meta($post_id, 'waveCargada', true) === 'false') {
        $existing_attachment_id = get_post_meta($post_id, 'waveform_image_id', true);
        if ($existing_attachment_id) {
            wp_delete_attachment($existing_attachment_id, true);
        }
    }

    // Agregar el ID del post al nombre del archivo para evitar duplicados.
    add_filter('wp_handle_upload_prefilter', function ($file) use ($post_id) {
        $file['name'] = $post_id . '_' . $file['name'];
        return $file;
    });

    // Subir la imagen.
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Obtener el autor del post y asignar la imagen a él.
    $author_id = get_post_field('post_author', $post_id);
    $attachment_id = media_handle_upload('image', $post_id, array('post_author' => $author_id));

    // Remover el filtro.
    remove_filter('wp_handle_upload_prefilter', function ($file) use ($post_id) {
        $file['name'] = $post_id . '_' . $file['name'];
        return $file;
    });

    // Manejar errores de subida.
    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Error al subir la imagen');
        return;
    }

    // Obtener la URL y el tamaño de la imagen.
    $image_url = wp_get_attachment_url($attachment_id);
    $file_path = get_attached_file($attachment_id);
    $file_size = size_format(filesize($file_path), 2);

    // Actualizar los metadatos del post.
    update_post_meta($post_id, 'waveform_image_id', $attachment_id);
    update_post_meta($post_id, 'waveform_image_url', $image_url);
    update_post_meta($post_id, 'waveCargada', true);

    wp_send_json_success(array(
        'message' => 'Imagen guardada correctamente',
        'url' => $image_url,
        'size' => $file_size
    ));
}

add_action('wp_ajax_guardarWaveImagen', 'guardarWaveImagen');
add_action('wp_ajax_nopriv_guardarWaveImagen', 'guardarWaveImagen');
