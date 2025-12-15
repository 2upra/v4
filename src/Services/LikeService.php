<?php

/**
 * @deprecated Usar Kamples\Services\Social\LikeService
 * 
 * Wrapper de compatibilidad para LikeService.
 * Redirige al nuevo servicio en Services/Social/.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class LikeService extends \Kamples\Services\Social\LikeService
{
    public function __construct()
    {
        parent::__construct();
    }
}
