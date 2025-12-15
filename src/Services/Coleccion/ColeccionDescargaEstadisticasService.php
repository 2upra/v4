<?php

/**
 * Servicio de estadisticas y pinkys para descarga de colecciones
 * 
 * Gestiona la clasificacion de samples, verificacion de pinkys y estadisticas
 *
 * @package Kamples\Services\Coleccion
 * @since 1.0.0
 */

namespace Kamples\Services\Coleccion;

class ColeccionDescargaEstadisticasService
{
    private static ?ColeccionDescargaEstadisticasService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
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
     * Clasifica samples en descargados y no descargados
     *
     * @param array $samples Array de IDs de samples
     * @param int $userId ID del usuario
     * @return array [samplesDescargados, samplesNoDescargados]
     */
    public function clasificarSamples(array $samples, int $userId): array
    {
        $samplesDescargados = [];
        $samplesNoDescargados = [];

        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];
        if (!is_array($descargasAnteriores)) {
            $descargasAnteriores = [];
        }

        foreach ($samples as $sampleId) {
            if (isset($descargasAnteriores[$sampleId])) {
                $samplesDescargados[] = $sampleId;
            } else {
                $samplesNoDescargados[] = $sampleId;
            }
        }

        return [$samplesDescargados, $samplesNoDescargados];
    }

    /**
     * Verifica y resta pinkys del usuario
     *
     * @param int $userId ID del usuario
     * @param int $numNoDescargados Numero de samples no descargados
     * @param bool $sync Si es modo sincronizacion
     * @param string $zipPath Ruta del ZIP (para eliminar si falla)
     * @return bool|\WP_Error True si tiene suficientes pinkys, WP_Error si no
     */
    public function verificarYRestarPinkys(int $userId, int $numNoDescargados, bool $sync, string $zipPath)
    {
        if ($numNoDescargados <= 0) {
            return true;
        }

        $pinky = (int)get_user_meta($userId, 'pinky', true);

        if ($pinky < $numNoDescargados) {
            if (!$sync && !empty($zipPath) && file_exists($zipPath)) {
                unlink($zipPath);
            }
            return new \WP_Error('no_pinkys', sprintf(
                __('No tienes suficientes Pinkys. Se requieren %d pinkys', 'kamples'),
                $numNoDescargados
            ));
        }

        if (function_exists('restarPinkys')) {
            restarPinkys($userId, $numNoDescargados);
        }

        return true;
    }

    /**
     * Actualiza estadisticas de descarga
     *
     * @param int $userId ID del usuario
     * @param int $postId ID del post de la coleccion
     * @param array $samplesNoDescargados Samples descargados por primera vez
     * @param array $samplesDescargados Samples ya descargados anteriormente
     */
    public function actualizarEstadisticas(int $userId, int $postId, array $samplesNoDescargados, array $samplesDescargados): void
    {
        if (function_exists('actualizarTimestampDescargas')) {
            actualizarTimestampDescargas($userId);
        }

        $this->actualizarDescargas($userId, $samplesNoDescargados, $samplesDescargados);

        $totalDescargas = (int)get_post_meta($postId, 'totalDescargas', true);
        $totalDescargas++;
        update_post_meta($postId, 'totalDescargas', $totalDescargas);

        $this->logger->info('descarga', 'Estadisticas actualizadas', [
            'postId' => $postId,
            'totalDescargas' => $totalDescargas
        ]);
    }

    /**
     * Actualiza el registro de descargas del usuario
     *
     * @param int $userId ID del usuario
     * @param array $samplesNoDescargados Samples nuevos
     * @param array $samplesDescargados Samples ya descargados
     */
    private function actualizarDescargas(int $userId, array $samplesNoDescargados, array $samplesDescargados): void
    {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];
        if (!is_array($descargasAnteriores)) {
            $descargasAnteriores = [];
        }

        foreach ($samplesNoDescargados as $sampleId) {
            $descargasAnteriores[$sampleId] = 1;
        }

        foreach ($samplesDescargados as $sampleId) {
            if (isset($descargasAnteriores[$sampleId])) {
                $descargasAnteriores[$sampleId]++;
            }
        }

        update_user_meta($userId, 'descargas', $descargasAnteriores);
    }

    /**
     * Obtiene el numero de pinkys de un usuario
     *
     * @param int $userId ID del usuario
     * @return int Numero de pinkys
     */
    public function obtenerPinkys(int $userId): int
    {
        return (int)get_user_meta($userId, 'pinky', true);
    }

    /**
     * Verifica si el usuario tiene suficientes pinkys
     *
     * @param int $userId ID del usuario
     * @param int $requeridos Pinkys requeridos
     * @return bool True si tiene suficientes
     */
    public function tieneSuficientesPinkys(int $userId, int $requeridos): bool
    {
        return $this->obtenerPinkys($userId) >= $requeridos;
    }
}
