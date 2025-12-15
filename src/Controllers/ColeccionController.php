<?php

/**
 * @deprecated Usar Kamples\Controllers\Coleccion\ColeccionController
 * @see \Kamples\Controllers\Coleccion\ColeccionController
 */

namespace Kamples\Controllers;

use Kamples\Services\ColeccionService;

class ColeccionController extends Coleccion\ColeccionController
{
    public function __construct(?ColeccionService $coleccionService = null)
    {
        trigger_error(
            'ColeccionController está deprecated. Usar Kamples\Controllers\Coleccion\ColeccionController',
            E_USER_DEPRECATED
        );
        parent::__construct($coleccionService);
    }
}
