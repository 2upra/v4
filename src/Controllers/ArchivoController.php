<?php

/**
 * @deprecated Usar Kamples\Controllers\Audio\ArchivoController
 * @see \Kamples\Controllers\Audio\ArchivoController
 */

namespace Kamples\Controllers;

class ArchivoController extends Audio\ArchivoController
{
    public function __construct()
    {
        trigger_error(
            'ArchivoController está deprecated. Usar Kamples\Controllers\Audio\ArchivoController',
            E_USER_DEPRECATED
        );
        parent::__construct();
    }
}
