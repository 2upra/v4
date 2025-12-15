<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Contenido\IdeaService
 * Este archivo existe solo por compatibilidad.
 */
class IdeaService extends \Kamples\Services\Contenido\IdeaService
{
    public static function obtenerInstancia(): \Kamples\Services\Contenido\IdeaService
    {
        return \Kamples\Services\Contenido\IdeaService::obtenerInstancia();
    }
}
