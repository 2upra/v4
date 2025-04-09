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
