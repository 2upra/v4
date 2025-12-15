<?php

/**
 * @deprecated Usar Kamples\Services\Moderacion\ModeracionService
 * 
 * Wrapper de compatibilidad para ModeracionService.
 * Redirige al nuevo servicio en Services/Moderacion/.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ModeracionService extends \Kamples\Services\Moderacion\ModeracionService
{
    private static ?ModeracionService $instanciaWrapper = null;

    /**
     * Obtiene la instancia única del servicio (Singleton).
     * Mantiene compatibilidad con el patrón Singleton original.
     */
    public static function obtenerInstancia(): \Kamples\Services\Moderacion\ModeracionService
    {
        return parent::obtenerInstancia();
    }
}
