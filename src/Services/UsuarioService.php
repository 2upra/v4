<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Usuario\UsuarioService
 * Este archivo existe solo por compatibilidad.
 */
class UsuarioService extends \Kamples\Services\Usuario\UsuarioService
{
    public static function obtenerInstancia(): \Kamples\Services\Usuario\UsuarioService
    {
        return \Kamples\Services\Usuario\UsuarioService::obtenerInstancia();
    }
}
