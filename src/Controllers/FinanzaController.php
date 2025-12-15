<?php

/**
 * @deprecated Usar Kamples\Controllers\Finanza\FinanzaController
 * @see \Kamples\Controllers\Finanza\FinanzaController
 * 
 * Este archivo se mantiene por compatibilidad.
 * La funcionalidad ha sido dividida en:
 * - Finanza\AccionesController (donaciones)
 * - Finanza\CompraController (compra de beats)
 * - Finanza\SuscripcionController (suscripciones PRO)
 */

namespace Kamples\Controllers;

class FinanzaController extends Finanza\FinanzaController
{
    public function __construct()
    {
        trigger_error(
            'FinanzaController está deprecated. Usar Kamples\Controllers\Finanza\FinanzaController',
            E_USER_DEPRECATED
        );
        parent::__construct();
    }
}
