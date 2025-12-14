<?php

/**
 * Controlador de subida de archivos
 * 
 * Maneja las acciones AJAX para subir archivos y calcular hashes
 *
 * @package Kamples\Controllers
 * @since 1.0.0
 */

namespace Kamples\Controllers;

use Kamples\Services\HashService;

class ArchivoController
{
    private HashService $hashService;

    public function __construct()
    {
        $this->hashService = HashService::obtenerInstancia();

        add_action('wp_ajax_file_upload', [$this, 'manejarSubidaArchivo']);
        add_action('wp_ajax_recalcularHash', [$this, 'manejarRecalcularHash']);
    }

    /**
     * Maneja la subida de archivos con verificación de hash
     */
    public function manejarSubidaArchivo(): void
    {
        $isAdmin = current_user_can('administrator');
        $currentUserId = get_current_user_id();
        $file = $_FILES['file'] ?? null;
        $fileHash = sanitize_text_field($_POST['file_hash'] ?? '');
        $isChat = filter_var($_POST['chat'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$file || !$fileHash) {
            wp_send_json_error('No se proporcionó archivo o hash');
            return;
        }

        $existingFile = $this->obtenerHash($fileHash);

        if ($existingFile) {
            $resultado = $this->procesarArchivoExistente($existingFile, $currentUserId, $isAdmin, $file, $isChat);
            if ($resultado !== null) {
                return;
            }
        }

        $this->subirNuevoArchivo($file, $fileHash, $currentUserId, $isChat);
    }

    /**
     * Procesa un archivo que ya existe en la base de datos
     */
    private function procesarArchivoExistente(array $existingFile, int $currentUserId, bool $isAdmin, array $file, bool $isChat): ?bool
    {
        $fileId = $existingFile['id'];
        $fileUrl = $existingFile['file_url'];
        $ownerId = $existingFile['user_id'];

        $filePath = str_replace(get_site_url(), ABSPATH, $fileUrl);

        if (!file_exists($filePath)) {
            $this->subirArchivoYActualizar($file, $fileId, $isChat, $ownerId);
            return true;
        }

        if ($ownerId != $currentUserId && !$isAdmin) {
            wp_send_json_error('No tienes permiso para reutilizar este archivo');
            return true;
        }

        if ($existingFile['status'] === 'pending' && !$isAdmin) {
            wp_send_json_success(['fileUrl' => $fileUrl, 'fileId' => $fileId]);
            return true;
        }

        if ($existingFile['status'] === 'confirmed' || $isAdmin) {
            wp_send_json_success(['fileUrl' => $fileUrl, 'fileId' => $fileId]);
            return true;
        }

        return null;
    }

    /**
     * Sube un archivo y actualiza el registro existente
     */
    private function subirArchivoYActualizar(array $file, int $fileId, bool $isChat, int $ownerId): void
    {
        $uploadDir = wp_upload_dir();
        $customDir = $isChat ? $uploadDir['basedir'] . '/chat_uploads' : $uploadDir['path'];

        if (!file_exists($customDir)) {
            mkdir($customDir, 0755, true);
        }

        if ($isChat) {
            add_filter('upload_dir', function ($dirs) use ($customDir) {
                $dirs['path'] = $customDir;
                $dirs['url'] = str_replace($dirs['basedir'], $dirs['baseurl'], $customDir);
                $dirs['subdir'] = '';
                return $dirs;
            });
        }

        $movefile = wp_handle_upload($file, [
            'test_form' => false,
            'unique_filename_callback' => [$this->hashService, 'nombreUnicoFile'],
        ]);

        if ($isChat) {
            remove_filter('upload_dir', '__return_false');
        }

        if ($movefile && !isset($movefile['error'])) {
            if ($ownerId == 0) {
                $this->hashService->actualizarUrlArchivo($fileId, $movefile['url']);
            }
            wp_send_json_success(['fileUrl' => $movefile['url'], 'fileId' => $fileId]);
        } else {
            wp_send_json_error($movefile['error'] ?? 'Error desconocido');
        }
    }

    /**
     * Sube un nuevo archivo
     */
    private function subirNuevoArchivo(array $file, string $fileHash, int $currentUserId, bool $isChat): void
    {
        $uploadDir = wp_upload_dir();
        $customDir = $isChat ? $uploadDir['basedir'] . '/chat_uploads' : $uploadDir['path'];

        if (!file_exists($customDir)) {
            mkdir($customDir, 0755, true);
        }

        if ($isChat) {
            add_filter('upload_dir', function ($dirs) use ($customDir) {
                $dirs['path'] = $customDir;
                $dirs['url'] = str_replace($dirs['basedir'], $dirs['baseurl'], $customDir);
                $dirs['subdir'] = '';
                return $dirs;
            });
        }

        $movefile = wp_handle_upload($file, [
            'test_form' => false,
            'unique_filename_callback' => [$this->hashService, 'nombreUnicoFile'],
        ]);

        if ($isChat) {
            remove_filter('upload_dir', '__return_false');
        }

        if ($movefile && !isset($movefile['error'])) {
            $fileId = $this->hashService->guardarHash($fileHash, $movefile['url'], $currentUserId, 'pending');
            wp_send_json_success(['fileUrl' => $movefile['url'], 'fileId' => $fileId]);
        } else {
            wp_send_json_error($movefile['error'] ?? 'Error desconocido');
        }
    }

    /**
     * Maneja la recalculación del hash de un archivo de audio
     */
    public function manejarRecalcularHash(): void
    {
        try {
            if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(['message' => 'No se pudo subir el archivo o está corrupto.']);
            }

            $audioFile = $_FILES['audio_file'];
            $allowedMimeTypes = ['audio/mpeg', 'audio/wav'];

            if (!in_array($audioFile['type'], $allowedMimeTypes)) {
                wp_send_json_error(['message' => 'Tipo de archivo no permitido.']);
            }

            $uploadDir = wp_upload_dir();
            $tempFilePath = $uploadDir['path'] . '/' . basename($audioFile['name']);

            if (!move_uploaded_file($audioFile['tmp_name'], $tempFilePath)) {
                wp_send_json_error(['message' => 'Error al mover el archivo subido.']);
            }

            $hash = $this->hashService->recalcularHash($tempFilePath);

            if ($hash === false) {
                wp_send_json_error(['message' => 'Error al generar el hash del archivo.']);
            }

            unlink($tempFilePath);
            wp_send_json_success(['hash' => $hash]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene un hash de la base de datos
     */
    private function obtenerHash(string $hash): ?array
    {
        if (function_exists('obtenerHash')) {
            return obtenerHash($hash);
        }

        global $wpdb;
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s",
                $hash
            ),
            ARRAY_A
        );

        return $result ?: null;
    }
}

/* Inicializar controlador */
new ArchivoController();
