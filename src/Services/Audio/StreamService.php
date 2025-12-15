<?php

/**
 * Servicio de streaming de audio (Fachada)
 * 
 * Orquesta los servicios especializados de streaming:
 * - StreamTokenService: Generacion y verificacion de tokens
 * - StreamSeguridadService: Rate limiting y bloqueo de IPs
 * - StreamEnvioService: Streaming de archivos con range requests
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class StreamService
{
    private static ?StreamService $instancia = null;
    private StreamTokenService $tokenService;
    private StreamSeguridadService $seguridadService;
    private StreamEnvioService $envioService;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->tokenService = StreamTokenService::obtenerInstancia();
        $this->seguridadService = StreamSeguridadService::obtenerInstancia();
        $this->envioService = StreamEnvioService::obtenerInstancia();
        $this->inicializarAutenticacion();
    }

    /**
     * Obtiene la instancia unica del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa la autenticacion para solicitudes AJAX/REST
     */
    private function inicializarAutenticacion(): void
    {
        add_action('init', function () {
            if (!defined('DOING_AJAX') && !defined('REST_REQUEST')) {
                return;
            }
            $userId = wp_validate_auth_cookie('', 'logged_in');
            if ($userId) {
                wp_set_current_user($userId);
            }
        });

        add_action('init', [$this, 'bloquearAccesoDirecto']);
    }

    /**
     * Bloquea acceso directo a archivos de audio
     */
    public function bloquearAccesoDirecto(): void
    {
        $this->seguridadService->bloquearAccesoDirecto($this->tokenService);
    }

    /**
     * Genera una URL segura para un audio
     *
     * @param string|int $audioId ID del archivo de audio
     * @return string|\WP_Error URL segura o error
     */
    public function generarUrlSegura($audioId)
    {
        $token = $this->tokenService->generarToken($audioId);
        if (!$token) {
            return new \WP_Error('invalid_audio_id', 'Audio ID invalido.');
        }

        $nonce = wp_create_nonce('wp_rest');
        return site_url("/wp-json/1/v1/2?token=" . urlencode($token) . '&_wpnonce=' . $nonce);
    }

    /**
     * Genera un token de acceso para un audio
     *
     * @param string|int $audioId ID del audio
     * @return string|false Token generado o false si hay error
     */
    public function generarToken($audioId)
    {
        return $this->tokenService->generarToken($audioId);
    }

    /**
     * Verifica si un token de audio es valido
     *
     * @param string $token Token a verificar
     * @return bool True si el token es valido
     */
    public function verificarToken(string $token): bool
    {
        return $this->tokenService->verificarToken($token, $this->seguridadService);
    }

    /**
     * Realiza el streaming del archivo de audio
     *
     * @param string $token Token de acceso
     * @return \WP_Error|void Error si falla, o realiza streaming y termina
     */
    public function streamAudio(string $token)
    {
        $audioId = $this->tokenService->extraerAudioId($token);
        if (!$audioId) {
            return new \WP_Error('invalid_token', 'Token invalido.');
        }

        return $this->envioService->streamAudio($audioId);
    }

    /**
     * Verifica si un usuario es admin o tiene suscripcion pro
     *
     * @param int $userId ID del usuario
     * @return bool True si es admin o pro
     */
    public function usuarioEsAdminOPro(int $userId): bool
    {
        if (empty($userId)) {
            return false;
        }

        $user = get_user_by('id', $userId);
        if (!$user || empty($user->roles)) {
            return false;
        }

        if (in_array('administrator', (array)$user->roles)) {
            return true;
        }

        $isPro = get_user_meta($userId, 'pro', true);
        return !empty($isPro);
    }

    /**
     * Limpia el cache de audio
     */
    public function limpiarCache(): void
    {
        $this->envioService->limpiarCache();
    }

    /**
     * Programa la limpieza automatica del cache
     */
    public function programarLimpiezaCache(): void
    {
        $this->envioService->programarLimpiezaCache();
    }

    /* 
     * Accesores para servicios especializados
     */

    public function obtenerTokenService(): StreamTokenService
    {
        return $this->tokenService;
    }

    public function obtenerSeguridadService(): StreamSeguridadService
    {
        return $this->seguridadService;
    }

    public function obtenerEnvioService(): StreamEnvioService
    {
        return $this->envioService;
    }
}
