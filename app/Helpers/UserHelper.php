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
