<?php

/**
 * Controlador de onboarding
 * 
 * Maneja las acciones AJAX de selección de tipo de usuario y géneros
 *
 * @package Kamples\Controllers
 * @since 1.0.0
 */

namespace Kamples\Controllers;

class OnboardingController
{
    public function __construct()
    {
        add_action('wp_ajax_guardarTipoUsuario', [$this, 'guardarTipoUsuario']);
        add_action('wp_ajax_guardarGenerosUsuario', [$this, 'guardarGenerosUsuario']);
    }

    /**
     * Guardar tipo de usuario (fan/artista)
     */
    public function guardarTipoUsuario(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Debes iniciar sesión para realizar esta acción.');
        }

        $tipoUsuario = isset($_POST['tipoUsuario']) ? sanitize_text_field($_POST['tipoUsuario']) : '';

        if (empty($tipoUsuario)) {
            wp_send_json_error('No se recibió el tipo de usuario.');
        }

        $userId = get_current_user_id();

        /* Reiniciar feed si existe la función */
        if (function_exists('reiniciarFeed')) {
            reiniciarFeed($userId);
        }

        update_user_meta($userId, 'tipoUsuario', $tipoUsuario);
        wp_send_json_success('El tipo de usuario ha sido guardado.');
    }

    /**
     * Guardar géneros preferidos del usuario
     */
    public function guardarGenerosUsuario(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Debes iniciar sesión para realizar esta acción.');
        }

        $generos = isset($_POST['generos']) ? explode(',', $_POST['generos']) : [];

        if (empty($generos) || !is_array($generos)) {
            wp_send_json_error('No se recibieron géneros seleccionados.');
        }

        $generosSanitizados = array_map('sanitize_text_field', $generos);
        $userId = get_current_user_id();

        update_user_meta($userId, 'usuarioPreferencias', $generosSanitizados);
        wp_send_json_success('Los géneros han sido guardados.');
    }
}

/* Inicializar controlador */
new OnboardingController();
