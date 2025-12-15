<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Contenido\ImagenService
 * Este archivo existe solo por compatibilidad.
 */
class ImagenService extends \Kamples\Services\Contenido\ImagenService
{
    public static function obtenerInstancia(): \Kamples\Services\Contenido\ImagenService
    {
        return \Kamples\Services\Contenido\ImagenService::obtenerInstancia();
    }
}
