<?php

/**
 * Servicio de reproductor de audio
 * 
 * Gestiona reproducciones, oyentes y rate limiting
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class ReproductorService
{
    private static ?ReproductorService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
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
     * Registra una reproducción de un post
     *
     * @param int $postId ID del post
     * @param int $artistId ID del artista
     * @param string $ipAddress Dirección IP del cliente
     * @return array Resultado de la operación
     */
    public function registrarReproduccion(int $postId, int $artistId, string $ipAddress): array
    {
        /* Validar rate limiting */
        if (!$this->verificarRateLimiting($ipAddress)) {
            return [
                'success' => false,
                'error' => 'rate_limit_exceeded',
                'message' => 'Límite de solicitudes excedido'
            ];
        }

        /* Validar que el post exista */
        if (!get_post($postId)) {
            return [
                'success' => false,
                'error' => 'invalid_post',
                'message' => 'El post no existe'
            ];
        }

        /* Validar que el artista exista */
        if (!get_user_by('ID', $artistId)) {
            return [
                'success' => false,
                'error' => 'invalid_artist',
                'message' => 'El artista no es válido'
            ];
        }

        /* Registrar reproducción del post */
        $this->incrementarReproducciones($postId);

        /* Registrar oyente del artista */
        $this->actualizarOyente($artistId, $ipAddress);

        return [
            'success' => true,
            'message' => 'Datos procesados correctamente'
        ];
    }

    /**
     * Incrementa el contador de reproducciones de un post
     *
     * @param int $postId ID del post
     */
    private function incrementarReproducciones(int $postId): void
    {
        $reproduccionesKey = 'reproducciones_post';
        $conteoActual = (int) get_post_meta($postId, $reproduccionesKey, true);
        update_post_meta($postId, $reproduccionesKey, $conteoActual + 1);
    }

    /**
     * Actualiza el registro de oyentes de un artista
     *
     * @param int $artistId ID del artista
     * @param string $ipAddress Dirección IP del oyente
     */
    private function actualizarOyente(int $artistId, string $ipAddress): void
    {
        $metaKey = 'oyentes_' . $artistId;
        $oyentes = get_option($metaKey, []);
        $tiempoActual = current_time('mysql', 1);
        $hace30Dias = date('Y-m-d H:i:s', strtotime('-30 days'));

        /* Limpiar oyentes antiguos (más de 30 días) */
        $oyentes = array_filter($oyentes, function ($ultimaEscucha) use ($hace30Dias) {
            return $ultimaEscucha >= $hace30Dias;
        });

        /* Actualizar oyente basado en IP */
        $oyentes[$ipAddress] = $tiempoActual;
        update_option($metaKey, $oyentes);
    }

    /**
     * Verifica el rate limiting para una IP
     *
     * @param string $ipAddress Dirección IP del cliente
     * @return bool True si la solicitud está permitida
     */
    private function verificarRateLimiting(string $ipAddress): bool
    {
        $transientName = 'rate_limit_' . $ipAddress;
        $rateLimit = get_transient($transientName);

        if (false === $rateLimit) {
            set_transient($transientName, 1, 5);
            return true;
        }

        if ($rateLimit >= 15) {
            return false;
        }

        set_transient($transientName, $rateLimit + 1, 60);
        return true;
    }

    /**
     * Obtiene el número de reproducciones de un post
     *
     * @param int $postId ID del post
     * @return int Número de reproducciones
     */
    public function obtenerReproducciones(int $postId): int
    {
        return (int) get_post_meta($postId, 'reproducciones_post', true);
    }

    /**
     * Obtiene el número de oyentes únicos de un artista (últimos 30 días)
     *
     * @param int $artistId ID del artista
     * @return int Número de oyentes únicos
     */
    public function obtenerOyentesUnicos(int $artistId): int
    {
        $metaKey = 'oyentes_' . $artistId;
        $oyentes = get_option($metaKey, []);
        $hace30Dias = date('Y-m-d H:i:s', strtotime('-30 days'));

        $oyentesActivos = array_filter($oyentes, function ($ultimaEscucha) use ($hace30Dias) {
            return $ultimaEscucha >= $hace30Dias;
        });

        return count($oyentesActivos);
    }
}
