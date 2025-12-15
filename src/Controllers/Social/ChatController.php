<?php

namespace Kamples\Controllers\Social;

use Kamples\Services\ChatService;

/**
 * Controlador principal del sistema de chat.
 * 
 * Fachada que orquesta los sub-controladores:
 * - ChatApiController: endpoints REST
 * - ChatAjaxController: handlers AJAX
 *
 * @since 1.0.0
 * @see ChatApiController
 * @see ChatAjaxController
 */
class ChatController
{
    private ChatApiController $apiController;
    private ChatAjaxController $ajaxController;

    public function __construct(?ChatService $chatService = null)
    {
        $chatService = $chatService ?? new ChatService();

        $this->apiController = new ChatApiController($chatService);
        $this->ajaxController = new ChatAjaxController($chatService);
    }

    /**
     * Registrar rutas y acciones.
     */
    public function registrar(): void
    {
        add_action('rest_api_init', [$this->apiController, 'registrarEndpoints']);
        $this->ajaxController->registrarAjax();
    }
}
