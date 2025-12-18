<?php

namespace Kamples\Controllers\Usuario;

use Kamples\Services\Usuario\UsuarioService;

/**
 * Controlador de operaciones de usuario.
 * 
 * Maneja los endpoints AJAX para gestión de usuarios:
 * - Cambio de tipo de usuario
 * - Bloqueo/desbloqueo de usuarios
 * - Procesamiento de descargas (pinkys)
 *
 * @package Kamples\Controllers\Usuario
 * @since 1.0.0
 */
class UsuarioController
{
    private static bool $registrado = false;
    private UsuarioService $servicio;

    /**
     * Constructor e inicialización de hooks.
     */
    public function __construct()
    {
        $this->servicio = UsuarioService::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Inicializa el controlador (Singleton-like).
     *
     * @return void
     */
    public static function inicializar(): void
    {
        if (!self::$registrado) {
            new self();
            self::$registrado = true;
        }
    }

    /**
     * Registra los hooks de WordPress.
     *
     * @return void
     */
    private function registrarHooks(): void
    {
        /* Cambiar tipo de usuario */
        add_action('wp_ajax_cambiar_tipo_usuario', [$this, 'cambiarTipoUsuario']);
        add_action('wp_ajax_nopriv_cambiar_tipo_usuario', [$this, 'cambiarTipoUsuario']);

        /* Bloquear usuario */
        add_action('wp_ajax_guardarBloqueo', [$this, 'guardarBloqueo']);

        /* Quitar bloqueo */
        add_action('wp_ajax_quitarBloqueo', [$this, 'quitarBloqueo']);

        /* Crear tabla de bloqueo al activar tema */
        add_action('after_switch_theme', [$this->servicio, 'crearTablaBloqueo']);

        /* Asignar pinkys al registro */
        add_action('user_register', [$this, 'pinkysRegistro']);

        /* Restablecer pinkys semanal */
        add_action('restablecer_pinkys_semanal', [$this, 'restablecerPinkysCron']);

        /* Programar cron si no existe */
        if (!wp_next_scheduled('restablecer_pinkys_semanal')) {
            wp_schedule_event(time(), 'weekly', 'restablecer_pinkys_semanal');
        }
    }

    /**
     * Cambia el tipo de usuario (Fan/Artista).
     *
     * @return void
     */
    public function cambiarTipoUsuario(): void
    {
        $userId = get_current_user_id();
        if (!$userId) {
            echo 'false';
            wp_die();
        }

        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
        $nuevoEstado = $this->servicio->cambiarTipoUsuario($userId, $tipo);

        echo $nuevoEstado ? '1' : '0';
        wp_die();
    }

    /**
     * Guarda o elimina un bloqueo de usuario.
     *
     * @return void
     */
    public function guardarBloqueo(): void
    {
        $usuarioActual = get_current_user_id();
        if (!$usuarioActual) {
            wp_send_json_error('No autenticado.');
            return;
        }

        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$postId) {
            wp_send_json_error('Post ID requerido.');
            return;
        }

        $resultado = $this->servicio->toggleBloqueo($usuarioActual, $postId);

        if ($resultado['success']) {
            wp_send_json_success($resultado['message']);
        } else {
            wp_send_json_error($resultado['message']);
        }
    }

    /**
     * Quita el bloqueo de un usuario.
     *
     * @return void
     */
    public function quitarBloqueo(): void
    {
        $usuarioActual = get_current_user_id();
        if (!$usuarioActual) {
            wp_send_json_error('No autenticado.');
            return;
        }

        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$postId) {
            wp_send_json_error('Post ID requerido.');
            return;
        }

        $resultado = $this->servicio->quitarBloqueo($usuarioActual, $postId);

        if ($resultado['success']) {
            wp_send_json_success($resultado['message']);
        } else {
            wp_send_json_error($resultado['message']);
        }
    }

    /**
     * Asigna pinkys iniciales al registrar un usuario.
     *
     * @param int $userId ID del nuevo usuario
     * @return void
     */
    public function pinkysRegistro(int $userId): void
    {
        $this->servicio->asignarPinkysRegistro($userId, 10);
    }

    /**
     * Restablece pinkys (cron job semanal).
     *
     * @return void
     */
    public function restablecerPinkysCron(): void
    {
        $this->servicio->restablecerPinkys(10);
    }
}

/* Inicializar el controlador */
UsuarioController::inicializar();
