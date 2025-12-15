<?php

/**
 * @deprecated Usar Kamples\Controllers\Usuario\PerfilController
 * @see \Kamples\Controllers\Usuario\PerfilController
 */

namespace Kamples\Controllers;

class PerfilController extends Usuario\PerfilController
{
    public function __construct()
    {
        trigger_error(
            'PerfilController está deprecated. Usar Kamples\Controllers\Usuario\PerfilController',
            E_USER_DEPRECATED
        );
        parent::__construct();
    }
}
