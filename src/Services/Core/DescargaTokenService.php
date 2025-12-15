<?php

/**
 * Servicio de tokens para descargas.
 * 
 * Maneja la generación y validación de tokens de descarga.
 *
 * @package Kamples\Services\Core
 * @since 1.0.0
 */

namespace Kamples\Services\Core;

class DescargaTokenService
{
    private static ?DescargaTokenService $instancia = null;
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
     * Genera un enlace de descarga temporal
     *
     * @param int $userId ID del usuario
     * @param int $audioId ID del audio
     * @return string URL de descarga
     */
    public function generarEnlaceDescarga(int $userId, int $audioId): string
    {
        $token = bin2hex(random_bytes(16));

        $tokenData = [
            'user_id' => $userId,
            'audio_id' => $audioId,
            'time' => time(),
            'usos' => 0
        ];

        set_transient('descarga_token_' . $token, $tokenData, HOUR_IN_SECONDS);

        return add_query_arg(['descarga_token' => $token], home_url());
    }

    /**
     * Obtiene los datos de un token de descarga
     *
     * @param string $token Token de descarga
     * @return array|false Datos del token o false si no existe
     */
    public function obtenerTokenData(string $token)
    {
        return get_transient('descarga_token_' . $token);
    }

    /**
     * Actualiza los usos de un token
     *
     * @param string $token Token de descarga
     * @param array $tokenData Datos actualizados del token
     */
    public function actualizarTokenUsos(string $token, array $tokenData): void
    {
        $tokenData['usos']++;
        set_transient('descarga_token_' . $token, $tokenData, HOUR_IN_SECONDS);

        if ($tokenData['usos'] >= 3) {
            delete_transient('descarga_token_' . $token);
        }
    }

    /**
     * Elimina un token de descarga
     *
     * @param string $token Token a eliminar
     */
    public function eliminarToken(string $token): void
    {
        delete_transient('descarga_token_' . $token);
    }

    /**
     * Valida un token de descarga
     *
     * @param string $token Token a validar
     * @return array|false Datos del token o false si no es válido
     */
    public function validarToken(string $token)
    {
        $tokenData = $this->obtenerTokenData($token);

        if (!$tokenData) {
            return false;
        }

        /* Verificar usos */
        if ($tokenData['usos'] >= 2) {
            $this->eliminarToken($token);
            return false;
        }

        return $tokenData;
    }
}
