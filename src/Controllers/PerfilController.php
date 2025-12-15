<?php

/**
 * Controlador de perfiles de usuario
 * 
 * Maneja acciones AJAX relacionadas con perfiles (actualizar descripción, presentación, imagen, etc.)
 *
 * @package Kamples\Controllers
 * @since 1.0.0
 */

namespace Kamples\Controllers;

use Kamples\Services\PerfilService;
use Logger;

class PerfilController
{
    private PerfilService $perfilService;
    private Logger $logger;

    public function __construct()
    {
        $this->perfilService = PerfilService::obtenerInstancia();
        $this->logger = Logger::obtenerInstancia();

        $this->registrarHooks();
    }

    private function registrarHooks(): void
    {
        // AJAX: Cambiar imagen de perfil
        add_action('wp_ajax_cambiar_imagen_perfil', [$this, 'cambiarImagenPerfil']);

        // AJAX: Cambiar nombre de usuario
        add_action('wp_ajax_cambiar_nombre', [$this, 'cambiarNombre']);

        // AJAX: Cambiar descripción (JSON - config modal)
        add_action('wp_ajax_cambiar_descripcion', [$this, 'cambiarDescripcion']);

        // AJAX: Cambiar descripción (Legacy text - admin/profile)
        add_action('wp_ajax_save_profile_description', [$this, 'cambiarDescripcionLegacy']);

        // AJAX: Cambiar enlace
        add_action('wp_ajax_cambiar_enlace', [$this, 'cambiarEnlace']);

        // AJAX: Actualizar presentación (Legacy - image + text)
        add_action('wp_ajax_update_presentacion', [$this, 'actualizarPresentacion']);
    }

    /**
     * AJAX Handler: Cambiar imagen de perfil
     */
    public function cambiarImagenPerfil(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'No estás autorizado.']);
            return;
        }

        $userId = get_current_user_id();

        if (!isset($_FILES['file'])) {
            wp_send_json_error(['error' => 'No se ha subido ningún archivo.']);
            return;
        }

        $resultado = $this->perfilService->cambiarImagenPerfil($userId, $_FILES['file']);

        if (is_wp_error($resultado)) {
            $this->logger->error('perfil', 'Error al cambiar imagen de perfil', [
                'user_id' => $userId,
                'error' => $resultado->get_error_message()
            ]);
            wp_send_json_error(['error' => $resultado->get_error_message()]);
        } else {
            wp_send_json_success($resultado);
        }
    }

    /**
     * AJAX Handler: Cambiar nombre de usuario
     */
    public function cambiarNombre(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('No estás autorizado.');
            return;
        }

        $nuevoNombre = isset($_POST['new_username']) ? sanitize_text_field($_POST['new_username']) : '';

        $resultado = $this->perfilService->cambiarNombre(get_current_user_id(), $nuevoNombre);

        if (is_wp_error($resultado)) {
            wp_send_json_error($resultado->get_error_message());
        } else {
            wp_send_json_success('El nombre de usuario ha sido cambiado exitosamente.');
        }
    }

    /**
     * AJAX Handler: Cambiar descripción (JSON)
     */
    public function cambiarDescripcion(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('No estás autorizado.');
            return;
        }

        $descripcion = isset($_POST['new_description']) ? sanitize_textarea_field($_POST['new_description']) : '';

        if (empty($descripcion)) {
            wp_send_json_error('La descripción no puede estar vacía.');
            return;
        }

        if (strlen($descripcion) > 300) {
            $descripcion = substr($descripcion, 0, 300);
        }

        $exito = $this->perfilService->actualizarDescripcion(get_current_user_id(), $descripcion);

        if ($exito) {
            wp_send_json_success('La descripción ha sido actualizada exitosamente.');
        } else {
            wp_send_json_error('Error al actualizar la descripción.');
        }
    }

    /**
     * AJAX Handler: Cambiar descripción (Legacy Text)
     */
    public function cambiarDescripcionLegacy(): void
    {
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $descripcion = isset($_POST['profile_description']) ? sanitize_text_field($_POST['profile_description']) : '';

        if ($userId && current_user_can('edit_user', $userId)) {
            $this->perfilService->actualizarDescripcion($userId, $descripcion);
            echo 'Descripción actualizada.';
        } else {
            echo 'No tienes permiso para actualizar este perfil.';
        }

        wp_die();
    }

    /**
     * AJAX Handler: Cambiar enlace
     */
    public function cambiarEnlace(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('No estás autorizado.');
            return;
        }

        $enlace = isset($_POST['new_link']) ? $_POST['new_link'] : '';

        $resultado = $this->perfilService->cambiarEnlace(get_current_user_id(), $enlace);

        if (is_wp_error($resultado)) {
            wp_send_json_error($resultado->get_error_message());
        } else {
            wp_send_json_success('El enlace ha sido actualizado exitosamente.');
        }
    }

    /**
     * AJAX Handler: Actualizar presentación
     */
    public function actualizarPresentacion(): void
    {
        $userId = get_current_user_id();
        if (!$userId) {
            wp_die('No autorizado'); // Legacy behavior used wp_die implicit or explicit
        }

        $texto = isset($_POST['texto']) ? sanitize_text_field($_POST['texto']) : 'Texto predeterminado';
        $imagenUrl = isset($_POST['imagen']) ? esc_url_raw($_POST['imagen']) : '';

        // Manejo de subida de imagen inline (si aplica)
        // El código original en perfilmusic.php manejaba la subida aquí mismo.
        if (isset($_FILES['newImage']) && $_FILES['newImage']['size'] > 0) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $imagenId = media_handle_upload('newImage', 0);
            if (!is_wp_error($imagenId)) {
                $imagenUrl = wp_get_attachment_url($imagenId);
            }
        }

        // Usamos update_user_meta directamente porque PerfilService::actualizarPresentacion
        // espera 'tipo' y 'url' (para video/imagen), pero aquí tenemos 'texto' e 'imagen'.
        // Podríamos extender PerfilService o hacerlo aquí. Lo haré aquí para mantener fidelidad legacy.

        update_user_meta($userId, 'presentacion_texto', $texto);
        update_user_meta($userId, 'presentacion_imagen', $imagenUrl);

        // Render response HTML
        echo "<div>";
        echo "<div class='imagen-container'>";
        echo "<img src='" . esc_url($imagenUrl) . "' alt='Imagen de Presentación' id='presentacion-imagen'>";
        echo "<p id='presentacion-texto'>" . esc_html($texto) . "</p>";
        echo "</div>";

        wp_die();
    }
}

/* Inicializar controlador */
new PerfilController();
