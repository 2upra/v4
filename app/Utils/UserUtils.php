<?php

// Refactor(Exec): Funcion obtenerNombreUsuario() movida desde app/Helpers/UserHelper.php
function obtenerNombreUsuario($usuarioId)
{
    $usuario = get_userdata($usuarioId);

    if ($usuario) {
        return !empty($usuario->display_name) ? $usuario->display_name : $usuario->user_login;
    }

    return 'Usuario desconocido';
}

?>