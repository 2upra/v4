<?php

/**
 * @deprecated Usar Kamples\Services\Social\ColabService
 * 
 * Wrapper de compatibilidad para ColabService.
 * Redirige al nuevo servicio en Services/Social/.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColabService extends \Kamples\Services\Social\ColabService
{
    public function __construct()
    {
        parent::__construct();
    }
}
