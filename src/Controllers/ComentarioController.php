<?php

/**
 * @deprecated Usar Kamples\Controllers\Social\ComentarioController
 * @see \Kamples\Controllers\Social\ComentarioController
 */

namespace Kamples\Controllers;

use Kamples\Services\ComentarioService;

class ComentarioController extends Social\ComentarioController
{
    public function __construct(?ComentarioService $comentarioService = null)
    {
        trigger_error(
            'ComentarioController está deprecated. Usar Kamples\Controllers\Social\ComentarioController',
            E_USER_DEPRECATED
        );
        parent::__construct($comentarioService);
    }
}
