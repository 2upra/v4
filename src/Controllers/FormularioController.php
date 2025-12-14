<?php

/**
 * Controlador del formulario de subida
 * 
 * Maneja la acción AJAX principal de subida de posts
 *
 * @package Kamples\Controllers
 * @since 1.0.0
 */

namespace Kamples\Controllers;

use Kamples\Services\PostCreacionService;

class FormularioController
{
    private PostCreacionService $postCreacionService;

    public function __construct()
    {
        $this->postCreacionService = PostCreacionService::obtenerInstancia();

        add_action('wp_ajax_subidaRs', [$this, 'manejarSubidaRs']);
    }

    /**
     * Maneja la subida principal del formulario RS
     */
    public function manejarSubidaRs(): void
    {
        if (!is_user_logged_in()) {
            if (function_exists('guardarLog')) {
                guardarLog('Error: Usuario no autorizado');
            }
            wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
        }

        /* Paso 1: Crear post */
        $postId = $this->postCreacionService->crearPost();

        if (is_wp_error($postId)) {
            wp_send_json_error(['message' => 'Error al crear el post']);
        }

        /* Paso 2: Actualizar metadatos */
        $this->postCreacionService->actualizarMetaDatos($postId);

        /* Paso 3: Datos para algoritmo */
        $this->postCreacionService->datosParaAlgoritmo($postId);

        /* Paso 4: Confirmar archivos */
        $this->postCreacionService->confirmarArchivos($postId);

        /* Paso 5: Procesar URLs */
        $this->postCreacionService->procesarURLs($postId);

        /* Paso 6: Asignar tags */
        $this->postCreacionService->asignarTags($postId);

        wp_send_json_success(['message' => 'Post creado exitosamente']);

        /* Manejar múltiples posts si aplica */
        if (isset($_POST['multiple']) && $_POST['multiple'] == '1') {
            if (function_exists('multiplesPost')) {
                multiplesPost($postId);
            }
        }

        wp_die();
    }
}

/* Inicializar controlador */
new FormularioController();
