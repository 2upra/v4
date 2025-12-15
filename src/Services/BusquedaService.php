<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Core\BusquedaService
 * Este archivo existe solo por compatibilidad.
 */
class BusquedaService extends \Kamples\Services\Core\BusquedaService
{
    public static function obtenerInstancia(): \Kamples\Services\Core\BusquedaService
    {
        return \Kamples\Services\Core\BusquedaService::obtenerInstancia();
    }
}
