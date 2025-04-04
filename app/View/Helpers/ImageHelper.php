<?php
// Refactor(Clean): Mueve función duplicada imagenPost() a ImageHelper.php

/**
 * Obtiene la URL de la imagen destacada o una imagen temporal para un post,
 * opcionalmente optimizada con Jetpack Photon.
 *
 * @param int    $postId    ID del post.
 * @param string $size      Tamaño de la imagen (e.g., 'thumbnail', 'medium', 'large', 'full').
 * @param int    $quality   Calidad de la imagen para Photon (1-100).
 * @param string $strip     Metadatos a eliminar por Photon ('all', 'info', 'none').
 * @param bool   $pixelated Si se debe pixelar la imagen (para vistas previas borrosas).
 * @param bool   $use_temp  Si se debe usar/generar una imagen temporal si no hay destacada.
 * @return string|false La URL de la imagen procesada o false si falla.
 */
function imagenPost($postId, $size = 'medium', $quality = 50, $strip = 'all', $pixelated = false, $use_temp = false)
{
    $post_thumbnail_id = get_post_thumbnail_id($postId);
    $url = false; // Inicializar URL

    if ($post_thumbnail_id) {
        $url = wp_get_attachment_image_url($post_thumbnail_id, $size);
    } elseif ($use_temp) {
        $temp_image_id = get_post_meta($postId, 'imagenTemporal', true);

        // Si existe una imagen temporal, úsala
        if ($temp_image_id && wp_attachment_is_image($temp_image_id)) {
            $url = wp_get_attachment_image_url($temp_image_id, $size);
        } else {
            // Si no existe imagen temporal, sube una nueva
            // Nota: La dependencia de obtenerImagenAleatoria y subirImagenALibreria debe estar disponible globalmente
            // o ser inyectada/requerida aquí.
            // Asumiendo que están disponibles globalmente por ahora.
            $random_image_path = function_exists('obtenerImagenAleatoria') ? obtenerImagenAleatoria('/home/asley01/MEGA/Waw/random') : false;
            if (!$random_image_path) {
                if (function_exists('ejecutarScriptPermisos')) ejecutarScriptPermisos();
                error_log('imagenPost: No se pudo obtener imagen aleatoria para el post ID ' . $postId);
                return false;
            }
            $temp_image_id = function_exists('subirImagenALibreria') ? subirImagenALibreria($random_image_path, $postId) : false;
            if (!$temp_image_id) {
                 if (function_exists('ejecutarScriptPermisos')) ejecutarScriptPermisos();
                error_log('imagenPost: No se pudo subir imagen temporal para el post ID ' . $postId);
                return false;
            }
            update_post_meta($postId, 'imagenTemporal', $temp_image_id);
            $url = wp_get_attachment_image_url($temp_image_id, $size);
        }
    }

    // Si no se encontró ninguna URL, retornar false
    if (!$url) {
         error_log('imagenPost: No se encontró URL de imagen para el post ID ' . $postId . ' con size ' . $size . ' y use_temp ' . ($use_temp ? 'true' : 'false'));
        return false;
    }

    // Aplicar Jetpack Photon si está disponible
    if (function_exists('jetpack_photon_url')) {
        $args = array('quality' => $quality, 'strip' => $strip);
        if ($pixelated) {
            // Para pixelar, reducimos tamaño y aplicamos zoom
            // Ajusta w y h según el nivel de pixelación deseado
            $args['resize'] = '50,50'; // Reducir a 50x50
            // $args['zoom'] = 2; // Zoom puede no ser necesario si ya se redimensiona
        }
        return jetpack_photon_url($url, $args);
    }

    // Retornar la URL original si Photon no está disponible
    return $url;
}

?>