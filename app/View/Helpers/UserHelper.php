<?php
// app/View/Helpers/UserHelper.php

/**
 * Contains helper functions for retrieving user data needed in view templates (e.g., header).
 */

// Refactor(Org): Moved header user data retrieval logic here
/**
 * Obtiene los datos del usuario necesarios para la cabecera.
 *
 * @return array Un array asociativo con los datos del usuario:
 *               'usuario' => WP_User|null, 'user_id' => int, 'nombre_usuario' => string,
 *               'url_imagen_perfil' => string, 'usuarioTipo' => string|null
 */
function obtenerDatosUsuarioCabecera() {
    // Si el usuario no está logueado, devolver valores por defecto
    if (!is_user_logged_in()) {
        return [
            'usuario' => null,
            'user_id' => 0,
            'nombre_usuario' => '',
            'url_imagen_perfil' => '',
            'usuarioTipo' => null
        ];
    }

    // Obtener datos del usuario logueado
    $usuario = wp_get_current_user();
    $user_id = get_current_user_id();
    $nombre_usuario = $usuario->display_name;
    $url_imagen_perfil = imagenPerfil($usuario->ID);
    $usuarioTipo = get_user_meta(get_current_user_id(), 'tipoUsuario', true);

    // Aplicar Jetpack Photon si está disponible
    if (function_exists('jetpack_photon_url')) {
        $url_imagen_perfil = jetpack_photon_url($url_imagen_perfil, array('quality' => 40, 'strip' => 'all'));
    }

    // Devolver los datos como un array asociativo
    return [
        'usuario' => $usuario,
        'user_id' => $user_id,
        'nombre_usuario' => $nombre_usuario,
        'url_imagen_perfil' => $url_imagen_perfil,
        'usuarioTipo' => $usuarioTipo
    ];
}

?>
