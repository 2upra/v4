<?php

/**
 * Servicio de descargas de audio (Fachada).
 * 
 * Orquesta la descarga de archivos de audio individuales y colecciones.
 *
 * @package Kamples\Services\Core
 * @since 1.0.0
 */

namespace Kamples\Services\Core;

use Kamples\Services\Usuario\UsuarioService;

class DescargaService
{
    private static ?DescargaService $instancia = null;
    private DescargaTokenService $tokenService;
    private DescargaEnvioService $envioService;
    private \Logger $logger;

    private function __construct()
    {
        $this->tokenService = DescargaTokenService::obtenerInstancia();
        $this->envioService = DescargaEnvioService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Procesa una solicitud de descarga
     *
     * @param int $userId ID del usuario
     * @param int $postId ID del post
     * @param bool $esColeccion Si es una colección
     * @param bool $sync Si es sincronización (sin devolver URL)
     * @return array Resultado de la operación
     */
    public function procesarDescarga(int $userId, int $postId, bool $esColeccion = false, bool $sync = false): array
    {
        if (!$userId) {
            return ['success' => false, 'message' => 'No autorizado.'];
        }

        $post = get_post($postId);
        if (!$post || $post->post_status !== 'publish') {
            return ['success' => false, 'message' => 'Post no válido.'];
        }

        if ($esColeccion) {
            return $this->procesarDescargaColeccion($userId, $postId, $sync);
        }

        return $this->procesarDescargaIndividual($userId, $postId, $sync);
    }

    /**
     * Procesa descarga de audio individual
     */
    private function procesarDescargaIndividual(int $userId, int $postId, bool $sync): array
    {
        $audioId = get_post_meta($postId, 'post_audio', true);
        if (!$audioId) {
            return ['success' => false, 'message' => 'Audio no encontrado.'];
        }

        $descargasAnteriores = get_user_meta($userId, 'descargas', true);
        if (!is_array($descargasAnteriores)) {
            $descargasAnteriores = [];
        }

        $yaDescargado = isset($descargasAnteriores[$postId]);

        /* Cobrar pinkys si es primera descarga */
        if (!$yaDescargado) {
            $pinky = (int) get_user_meta($userId, 'pinky', true);
            if ($pinky < 1) {
                return ['success' => false, 'message' => 'No tienes suficientes Pinkys para esta descarga.'];
            }
            $this->restarPinkys($userId, 1);
        }

        /* Actualizar registro de descargas */
        if (!$yaDescargado) {
            $descargasAnteriores[$postId] = 1;
        } else {
            $descargasAnteriores[$postId]++;
        }

        update_user_meta($userId, 'descargas', $descargasAnteriores);

        /* Actualizar contador del post */
        $totalDescargas = (int) get_post_meta($postId, 'totalDescargas', true);
        update_post_meta($postId, 'totalDescargas', $totalDescargas + 1);

        $this->actualizarTimestampDescargas($userId);

        if ($sync) {
            return ['success' => true, 'message' => 'Sincronizado.'];
        }

        $downloadUrl = $this->generarEnlaceDescarga($userId, $audioId);
        return ['success' => true, 'download_url' => $downloadUrl];
    }

    /**
     * Procesa descarga de colección
     */
    private function procesarDescargaColeccion(int $userId, int $postId, bool $sync): array
    {
        /* La lógica de colecciones se delega a ColeccionService */
        if (function_exists('procesarColeccion')) {
            if (!$sync) {
                $zipUrl = procesarColeccion($postId, $userId);
                if (is_wp_error($zipUrl)) {
                    return ['success' => false, 'message' => $zipUrl->get_error_message()];
                }
                $downloadUrl = generarEnlaceDescargaColeccion($userId, $zipUrl, $postId);
                $this->actualizarTimestampDescargas($userId);
                return ['success' => true, 'download_url' => $downloadUrl];
            } else {
                procesarColeccion($postId, $userId, true);
                $this->actualizarTimestampDescargas($userId);
                return ['success' => true, 'message' => 'Sincronizado.'];
            }
        }

        return ['success' => false, 'message' => 'Función de colecciones no disponible.'];
    }

    /**
     * Genera un enlace de descarga temporal
     */
    public function generarEnlaceDescarga(int $userId, int $audioId): string
    {
        return $this->tokenService->generarEnlaceDescarga($userId, $audioId);
    }

    /**
     * Procesa la descarga de un archivo de audio
     */
    public function manejarDescarga(string $token): bool
    {
        return $this->envioService->manejarDescarga($token);
    }

    /**
     * Resta pinkys a un usuario
     */
    private function restarPinkys(int $userId, int $cantidad): void
    {
        UsuarioService::obtenerInstancia()->restarPinkys($userId, $cantidad);
    }

    /**
     * Actualiza el timestamp de descargas del usuario
     */
    private function actualizarTimestampDescargas(int $userId): void
    {
        if (function_exists('actualizarTimestampDescargas')) {
            actualizarTimestampDescargas($userId);
        } else {
            update_user_meta($userId, 'timestamp_descargas', time());
        }
    }

    /**
     * Verifica si un post ya fue descargado por el usuario
     */
    public function yaDescargado(int $userId, int $postId): bool
    {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true);
        return is_array($descargasAnteriores) && isset($descargasAnteriores[$postId]);
    }
}
