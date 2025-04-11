<?php

/**
 * Optimiza una URL de imagen utilizando el servicio de CDN de WordPress.com (i0.wp.com).
 *
 * @param string|null $url La URL de la imagen original.
 * @param int $quality La calidad deseada de la imagen (0-100). Por defecto 40.
 * @param string $strip Qué metadatos eliminar ('all', 'info', 'none'). Por defecto 'all'.
 * @return string La URL de la imagen optimizada o una cadena vacía si la URL de entrada es inválida.
 */
function img($url, $quality = 40, $strip = 'all')
{
    if ($url === null || $url === '') {
        return '';
    }
    $parsed_url = parse_url($url);

    // Verificar si la URL ya es del CDN de WP.com
    if (isset($parsed_url['host']) && $parsed_url['host'] === 'i0.wp.com') {
        $cdn_url = $url;
        // Eliminar parámetros existentes de calidad y strip para evitar conflictos
        if (function_exists('remove_query_arg')) {
             $cdn_url = remove_query_arg(['quality', 'strip'], $cdn_url);
        }
    } else {
        // Construir la URL base para el CDN
        $path = isset($parsed_url['host']) ? $parsed_url['host'] . ($parsed_url['path'] ?? '') : ltrim($parsed_url['path'] ?? '', '/');
        // Si no hay host, asumimos que es una ruta relativa al sitio actual
        if (!isset($parsed_url['host']) && isset($parsed_url['path'])) {
             $path = ltrim($parsed_url['path'], '/'); // Mantener la lógica original para rutas relativas
        }

        // Construir la URL base para i0.wp.com
        $cdn_url = 'https://i0.wp.com/' . $path;
    }

    $query = [];
    if ($quality !== null && is_numeric($quality)) {
        $query['quality'] = (int) $quality;
    }
    if ($strip !== null && in_array($strip, ['all', 'info', 'none'])) {
         $query['strip'] = $strip;
    }


    // Añadir los nuevos parámetros a la URL del CDN
    // add_query_arg necesita estar disponible. Asumimos que está en el entorno WP.
    if (function_exists('add_query_arg')) {
        $final_url = add_query_arg($query, $cdn_url);
    } else {
        // Fallback simple si add_query_arg no existe (poco probable en WP)
        $final_url = $cdn_url . (strpos($cdn_url, '?') === false ? '?' : '&') . http_build_query($query);
    }

    // Escapar la URL final por seguridad si la función existe
    if (function_exists('esc_url')) {
        return esc_url($final_url);
    } else {
        return $final_url;
    }
}

// Refactor(Org): Funcion imagenPerfil movida a app/Helpers/UserHelper.php

// Refactor(Org): Función movida desde app/Sync/api.php
function obtenerImagenOptimizada($post_id)
{
    // Intentar obtener la imagen de portada
    $portada_id = get_post_thumbnail_id($post_id);
    if ($portada_id) {
        $portada_url = wp_get_attachment_url($portada_id);
        if ($portada_url) {
            return img($portada_url); // Optimizar la imagen
        }
    }

    // Si no hay portada, intentar obtener la imagen temporal
    $imagen_temporal_id = get_post_meta($post_id, 'imagenTemporal', true);
    if ($imagen_temporal_id) {
        $imagen_temporal_url = wp_get_attachment_url($imagen_temporal_id);
        if ($imagen_temporal_url) {
            return img($imagen_temporal_url); // Optimizar la imagen
        }
    }

    // Si no hay imagen, devolver null
    return null;
}

// Refactor(Org): Función obtenerImagenAleatoria movida desde app/Utils/FileUtils.php
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

// Refactor(Org): Función subirImagenDesdeURL movida desde app/Utils/FileUtils.php
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

// Refactor(Org): Función subirImagenALibreria movida desde app/Utils/FileUtils.php
function subirImagenALibreria($file_path, $postId)
{
    if (!file_exists($file_path)) {
        return false;
    }
    $file_contents = file_get_contents($file_path);
    if ($file_contents === false) {
        return false;
    }
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($file_ext === 'jfif') {
        $file_ext = 'jpeg';
        $new_file_name = pathinfo($file_path, PATHINFO_FILENAME) . '.jpeg';
        $upload_file = wp_upload_bits($new_file_name, null, $file_contents);
    } else {
        $upload_file = wp_upload_bits(basename($file_path), null, $file_contents);
    }

    if ($upload_file['error']) {
        return false;
    }
    $filetype = wp_check_filetype($upload_file['file'], null);
    if (!$filetype['type']) {
        return false;
    }
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($upload_file['file'], PATHINFO_BASENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_parent'    => $postId,
    );
    $attach_id = wp_insert_attachment($attachment, $upload_file['file'], $postId);
    if (!is_wp_error($attach_id)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
    return false;
}
