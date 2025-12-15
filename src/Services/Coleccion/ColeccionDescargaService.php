<?php

/**
 * Servicio de descarga de colecciones (Fachada)
 * 
 * Orquesta los servicios especializados de descarga:
 * - ColeccionDescargaZipService: Creacion y gestion de ZIPs
 * - ColeccionDescargaTokenService: Tokens y streaming
 * - ColeccionDescargaEstadisticasService: Pinkys y estadisticas
 *
 * @package Kamples\Services\Coleccion
 * @since 1.0.0
 */

namespace Kamples\Services\Coleccion;

class ColeccionDescargaService
{
    private static ?ColeccionDescargaService $instancia = null;
    private ColeccionDescargaZipService $zipService;
    private ColeccionDescargaTokenService $tokenService;
    private ColeccionDescargaEstadisticasService $estadisticasService;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->zipService = ColeccionDescargaZipService::obtenerInstancia();
        $this->tokenService = ColeccionDescargaTokenService::obtenerInstancia();
        $this->estadisticasService = ColeccionDescargaEstadisticasService::obtenerInstancia();
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
     * Procesa una coleccion para descarga o sincronizacion
     *
     * @param int $postId ID del post de la coleccion
     * @param int $userId ID del usuario
     * @param bool $sync Si es sincronizacion (no crea ZIP)
     * @return string|bool|\WP_Error Ruta del ZIP o true si sync, WP_Error en caso de error
     */
    public function procesarColeccion(int $postId, int $userId, bool $sync = false)
    {
        $this->logger->debug('descarga', 'Inicio de procesarColeccion', [
            'postId' => $postId,
            'userId' => $userId,
            'sync' => $sync
        ]);

        $samples = get_post_meta($postId, 'samples', true);
        $numSamples = is_array($samples) ? count($samples) : 0;

        if ($numSamples === 0) {
            $this->logger->warning('descarga', 'No hay samples en la coleccion', ['postId' => $postId]);
            return new \WP_Error('no_samples', __('No hay samples en esta coleccion.', 'kamples'));
        }

        $zipPath = '';
        if (!$sync) {
            $zipInfo = $this->zipService->generarRutaZip($postId, $numSamples);
            $zipPath = $zipInfo['zipPath'];

            $verificacion = $this->zipService->verificarDirectorioEscribible($zipInfo['uploadDir']);
            if (is_wp_error($verificacion)) {
                return $verificacion;
            }

            $this->zipService->limpiarZipsAntiguos($zipInfo['uploadDir']['path'], $postId, $zipPath);
        }

        list($samplesDescargados, $samplesNoDescargados) = $this->estadisticasService->clasificarSamples($samples, $userId);
        $numSamplesNoDescargados = count($samplesNoDescargados);

        if (!$sync) {
            $resultado = $this->zipService->crearOValidarZip($zipPath, $samples, $userId, $numSamplesNoDescargados);
            if (is_wp_error($resultado)) {
                return $resultado;
            }
        }

        $resultadoPinkys = $this->estadisticasService->verificarYRestarPinkys($userId, $numSamplesNoDescargados, $sync, $zipPath);
        if (is_wp_error($resultadoPinkys)) {
            return $resultadoPinkys;
        }

        $this->estadisticasService->actualizarEstadisticas($userId, $postId, $samplesNoDescargados, $samplesDescargados);

        if (!$sync) {
            return $zipPath;
        }

        return true;
    }

    /**
     * Genera un enlace de descarga seguro con token
     *
     * @param int $userId ID del usuario
     * @param string $zipPath Ruta fisica del ZIP
     * @param int $postId ID del post
     * @return string URL de descarga
     */
    public function generarEnlaceDescarga(int $userId, string $zipPath, int $postId): string
    {
        return $this->tokenService->generarEnlaceDescarga($userId, $zipPath, $postId);
    }

    /**
     * Procesa la descarga de una coleccion desde un token
     * Se llama desde template_redirect hook
     */
    public function procesarDescargaDesdeToken(): void
    {
        $this->tokenService->procesarDescargaDesdeToken();
    }

    /**
     * Clasifica samples en descargados y no descargados
     *
     * @param array $samples Array de IDs de samples
     * @param int $userId ID del usuario
     * @return array [samplesDescargados, samplesNoDescargados]
     */
    public function clasificarSamples(array $samples, int $userId): array
    {
        return $this->estadisticasService->clasificarSamples($samples, $userId);
    }

    /* 
     * Accesores para servicios especializados
     */

    public function obtenerZipService(): ColeccionDescargaZipService
    {
        return $this->zipService;
    }

    public function obtenerTokenService(): ColeccionDescargaTokenService
    {
        return $this->tokenService;
    }

    public function obtenerEstadisticasService(): ColeccionDescargaEstadisticasService
    {
        return $this->estadisticasService;
    }
}
