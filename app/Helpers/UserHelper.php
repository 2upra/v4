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
