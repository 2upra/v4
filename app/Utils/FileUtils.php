<?php

function obtenerImagenAleatoria($directory)
{
    static $cache = array();

    if (isset($cache[$directory])) {
        return $cache[$directory][array_rand($cache[$directory])];
    }

    if (!is_dir($directory)) {
        return false;
    }

    $images = glob(rtrim($directory, '/') . '/*.{jpg,jpeg,png,gif,jfif}', GLOB_BRACE);

    if (!$images) {
        return false;
    }

    $cache[$directory] = $images;
    return $images[array_rand($images)];
}

/**
 * Sube una imagen desde una URL a la biblioteca de medios de WordPress.
 *
 * @param string $image_url La URL de la imagen a subir.
 * @param int $post_id El ID del post al que se adjuntará la imagen (opcional, por defecto 0).
 * @return int|false El ID del adjunto si tiene éxito, false en caso contrario.
 */
function subirImagenDesdeURL($image_url, $post_id = 0) {
    // Asegurarse de que las funciones necesarias de WordPress estén cargadas.
    if (!function_exists('media_sideload_image')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    // Usar la función media_sideload_image para subir la imagen al servidor.
    // El último parámetro 'id' indica que queremos que devuelva el ID del adjunto.
    $media_id = media_sideload_image($image_url, $post_id, null, 'id');

    // Verificar si hubo un error durante la subida.
    if (is_wp_error($media_id)) {
        // Opcional: Registrar el error para depuración.
        // error_log('Error al subir imagen desde URL: ' . $media_id->get_error_message());
        return false;
    }

    return $media_id;
}
