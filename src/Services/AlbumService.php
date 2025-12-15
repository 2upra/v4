<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Coleccion\AlbumService
 * Este archivo existe solo por compatibilidad.
 */
class AlbumService extends \Kamples\Services\Coleccion\AlbumService
{
    public static function obtenerInstancia(): \Kamples\Services\Coleccion\AlbumService
    {
        return \Kamples\Services\Coleccion\AlbumService::obtenerInstancia();
    }
}
