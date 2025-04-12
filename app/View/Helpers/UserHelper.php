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

// Refactor(Org): Moved from app/Perfiles/perfiles.php
function my_custom_avatar($avatar, $id_or_email, $size, $default, $alt) {
    $urlAvatarDefecto = 'https://i.pinimg.com/564x/d2/64/e3/d264e36c185da291cf7964ec3dfa37b8.jpg'; // URL por defecto
    $usuario = false;

    if (is_numeric($id_or_email)) {
        $usuario = get_user_by('id', $id_or_email);
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $usuario = get_user_by('id', $id_or_email->user_id);
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $usuario = get_user_by('email', $id_or_email);
    }

    if ($usuario) {
        $idImagen = get_user_meta($usuario->ID, 'imagen_perfil_id', true);
        $urlAvatar = !empty($idImagen) ? wp_get_attachment_url($idImagen) : $urlAvatarDefecto;
        $avatar = "<img src='" . esc_url($urlAvatar) . "' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' alt='" . esc_attr($alt) . "' />";
    } else {
        // Si no se encuentra usuario, usar la URL por defecto o el avatar original
         $avatar = "<img src='" . esc_url($urlAvatarDefecto) . "' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' alt='" . esc_attr($alt) . "' />";
    }

    return $avatar;
}
add_filter('get_avatar', 'my_custom_avatar', 10, 5);

function imagenPerfil($idUsuario) {
    $idImagen = get_user_meta($idUsuario, 'imagen_perfil_id', true);
    return !empty($idImagen) ? wp_get_attachment_url($idImagen) : 'https://2upra.com/wp-content/uploads/2024/05/perfildefault.jpg';
}

// Refactor(Org): Funcion usuarioEsAdminOPro movida desde app/Utils/UserUtils.php
function usuarioEsAdminOPro($user_id)
{
    // Verificar que el ID de usuario sea válido
    if (empty($user_id) || !is_numeric($user_id)) {
        ////guardarLog("usuarioEsAdminOPro: Error - ID de usuario inválido.");
        return false;
    }

    // Obtener el objeto usuario
    $user = get_user_by('id', $user_id);

    // Verificar si el usuario existe
    if (!$user) {
        ////guardarLog("usuarioEsAdminOPro: Error - Usuario no encontrado para el ID: " . $user_id);
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($user->roles)) {
        ////guardarLog("usuarioEsAdminOPro: Error - Usuario sin roles asignados. ID: " . $user_id);
        ////guardarLog("usuarioEsAdminOPro: Información del usuario - " . print_r($user, true));
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $user->roles)) {
        ////guardarLog("usuarioEsAdminOPro: Usuario es administrador. ID: " . $user_id);
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($user_id, 'pro', true);
    if (!empty($is_pro)) {
        ////guardarLog("usuarioEsAdminOPro: Usuario tiene la meta 'pro'. ID: " . $user_id);
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    ////guardarLog("usuarioEsAdminOPro: Usuario no es administrador ni tiene la meta 'pro'. ID: " . $user_id);
    return false;
}

// Refactor(Exec): Moved function validarUsuario from app/Content/Logic/datosParaCalculo.php
function validarUsuario($userId) {
    $tiempoInicio = microtime(true);
    if (!$userId) {
        //guardarLog("[validarUsuario] Error: ID de usuario no válido");
        //rendimientolog("[validarUsuario] Terminó con error (ID de usuario no válido) en " . (microtime(true) - $tiempoInicio) . " segundos");
        return false;
    }
    return true;
}


?>
