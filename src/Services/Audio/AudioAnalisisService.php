<?php

/**
 * Servicio de análisis de audio
 * 
 * Maneja el análisis técnico del audio con scripts Python:
 * - Ejecución del script de análisis
 * - Extracción de BPM, pitch, emotion, key, scale
 * - Guardado de resultados como metadatos
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class AudioAnalisisService
{
    private static ?AudioAnalisisService $instancia = null;
    private \Logger $logger;

    private const PYTHON_SCRIPT_PATH = '/var/www/wordpress/wp-content/themes/2upra3v/app/python/audio.py';

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
     * Ejecuta el script Python de análisis de audio
     */
    public function ejecutarAnalisis(string $audioPath): ?array
    {
        $pythonCommand = escapeshellcmd("python3 " . self::PYTHON_SCRIPT_PATH . " \"{$audioPath}\"");
        $this->logger->debug('audio', "Ejecutando comando de Python: {$pythonCommand}");

        exec($pythonCommand, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->logger->error('audio', "Error al ejecutar el script de Python. Código de retorno: {$returnVar}", ['output' => implode("\n", $output)]);
            return null;
        }

        $resultadosPath = $audioPath . '_resultados.json';

        if (file_exists($resultadosPath)) {
            $resultados = json_decode(file_get_contents($resultadosPath), true);
            if ($resultados && is_array($resultados)) {
                return $resultados;
            }
            $this->logger->warning('audio', 'El archivo de resultados JSON no contiene datos válidos');
        } else {
            $this->logger->warning('audio', "No se encontró el archivo de resultados en {$resultadosPath}");
        }

        return null;
    }

    /**
     * Guarda los resultados del análisis de Python como metadatos
     */
    public function guardarResultados(int $postId, array $resultados, int $index): void
    {
        $suffix = ($index == 1) ? '' : "_{$index}";

        update_post_meta($postId, "audio_bpm{$suffix}", $resultados['bpm'] ?? '');
        update_post_meta($postId, "audio_pitch{$suffix}", $resultados['pitch'] ?? '');
        update_post_meta($postId, "audio_emotion{$suffix}", $resultados['emotion'] ?? '');
        update_post_meta($postId, "audio_key{$suffix}", $resultados['key'] ?? '');
        update_post_meta($postId, "audio_scale{$suffix}", $resultados['scale'] ?? '');
        update_post_meta($postId, "audio_strength{$suffix}", $resultados['strength'] ?? '');

        $this->logger->info('audio', "Resultados de análisis guardados para post ID: {$postId}");
    }
}
