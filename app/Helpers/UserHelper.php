<?php

// Refactor(Org): Funcion obtenerNombreUsuario movida desde app/Utils/UserUtils.php
function obtenerNombreUsuario($usuarioId)
{
    $usuario = get_userdata($usuarioId);

    if ($usuario) {
        return !empty($usuario->display_name) ? $usuario->display_name : $usuario->user_login;
    }

    return 'Usuario desconocido';
}

// Refactor(Org): Funcion imagenPerfil movida desde app/Utils/ImageUtils.php
/**
 * Obtiene la URL de la imagen de perfil de un usuario.
 *
 * @param int $idUsuario El ID del usuario.
 * @return string La URL de la imagen de perfil o la URL de la imagen por defecto.
 */
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

// Refactor(Org): Añadida función para obtener datos de usuario para la cabecera.
/**
 * Obtiene los datos básicos del usuario actual para la cabecera.
 *
 * @return array|null Un array con 'user_id', 'nombre_usuario', 'url_imagen_perfil', 'usuarioTipo', o null si el usuario no está logueado.
 */
function obtenerDatosUsuarioCabecera()
{
    if (!is_user_logged_in()) {
        return null;
    }

    $usuario = wp_get_current_user();
    $user_id = $usuario->ID;
    $nombre_usuario = $usuario->display_name;
    $url_imagen_perfil = imagenPerfil($user_id); // Asume que imagenPerfil() está disponible
    $usuarioTipo = get_user_meta($user_id, 'tipoUsuario', true);

    // Optimizar URL de imagen si Jetpack Photon está activo
    if (function_exists('jetpack_photon_url')) {
        $url_imagen_perfil = jetpack_photon_url($url_imagen_perfil, array('quality' => 40, 'strip' => 'all'));
    }

    return [
        'user_id' => $user_id,
        'nombre_usuario' => $nombre_usuario,
        'url_imagen_perfil' => $url_imagen_perfil,
        'usuarioTipo' => $usuarioTipo,
    ];
}

?>
