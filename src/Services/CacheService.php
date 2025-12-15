<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Core\CacheService
 * Este archivo existe solo por compatibilidad.
 */
class CacheService extends \Kamples\Services\Core\CacheService
{
    public static function obtenerInstancia(?string $subdir = 'feed'): \Kamples\Services\Core\CacheService
    {
        return \Kamples\Services\Core\CacheService::obtenerInstancia($subdir);
    }
}
