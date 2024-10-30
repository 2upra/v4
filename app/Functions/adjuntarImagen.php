<? 

function subirImagenDesdeURL($image_url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Usa la función media_sideload_image para subir la imagen al servidor
    $media = media_sideload_image($image_url, $post_id, null, 'id');

    if (is_wp_error($media)) {
        return false;
    }

    return $media;
}