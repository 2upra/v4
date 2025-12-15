<?php

/**
 * @deprecated Usar Kamples\Controllers\Social\ChatController
 * @see \Kamples\Controllers\Social\ChatController
 * 
 * Este archivo se mantiene por compatibilidad.
 * La funcionalidad ha sido dividida en:
 * - Social\ChatApiController (endpoints REST)
 * - Social\ChatAjaxController (handlers AJAX)
 */

namespace Kamples\Controllers;

use Kamples\Services\ChatService;

class ChatController extends Social\ChatController
{
    public function __construct(?ChatService $chatService = null)
    {
        trigger_error(
            'ChatController está deprecated. Usar Kamples\Controllers\Social\ChatController',
            E_USER_DEPRECATED
        );
        parent::__construct($chatService);
    }
}
