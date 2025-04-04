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
