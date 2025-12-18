<?php

/**
 * Servicio de procesamiento de audio (Fachada)
 * 
 * Orquesta el procesamiento completo de archivos de audio delegando
 * las responsabilidades específicas a servicios especializados:
 * - AudioConversionService: Conversión FFmpeg y operaciones de archivo
 * - AudioAnalisisService: Análisis técnico con Python
 * - AudioIAService: Generación de descripciones con IA
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

use Kamples\Services\Audio\HashService;

class AudioProcessingService
{
    private static ?AudioProcessingService $instancia = null;
    private \Logger $logger;
    private HashService $hashService;
    private AudioConversionService $conversionService;
    private AudioAnalisisService $analisisService;
    private AudioIAService $iaService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->hashService = HashService::obtenerInstancia();
        $this->conversionService = AudioConversionService::obtenerInstancia();
        $this->analisisService = AudioAnalisisService::obtenerInstancia();
        $this->iaService = AudioIAService::obtenerInstancia();
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
     * Procesa un archivo de audio creando una versión ligera (128k)
     * 
     * @param int $postId ID del post
     * @param int $audioId ID del attachment de audio
     * @param int $index Índice del audio (1-30)
     * @return bool True si el procesamiento fue exitoso
     */
    public function procesarAudioLigero(int $postId, int $audioId, int $index): bool
    {
        $this->logger->info('audio', "Inicio procesarAudioLigero para Post ID: {$postId} y Audio ID: {$audioId}");

        $audioPath = get_attached_file($audioId);

        if (!$audioPath || !file_exists($audioPath)) {
            $this->logger->error('audio', "Archivo de audio no encontrado: {$audioPath}");
            return false;
        }

        $pathParts = pathinfo($audioPath);
        $basePath = $pathParts['dirname'] . '/' . $pathParts['filename'];

        /* Eliminar metadatos del archivo original */
        $this->conversionService->eliminarMetadatos($audioPath);

        /* Obtener información del autor */
        $authorUsername = $this->conversionService->obtenerNombreAutor($postId);
        $pageName = parse_url(home_url(), PHP_URL_HOST);

        /* Crear versión ligera (128k) con metadatos personalizados */
        $archivoLitePath = $basePath . '_128k.mp3';

        if (!$this->conversionService->crearVersionLigera($audioPath, $archivoLitePath, $authorUsername, $pageName)) {
            return false;
        }

        /* Insertar en la biblioteca de medios */
        $attachIdLite = $this->conversionService->insertarEnMediaLibrary($archivoLitePath, $postId);

        if (!$attachIdLite) {
            return false;
        }

        /* Guardar metadatos del archivo ligero */
        $metaKey = ($index == 1) ? 'post_audio_lite' : "post_audio_lite_{$index}";
        update_post_meta($postId, $metaKey, $attachIdLite);

        /* Extraer y guardar duración */
        $this->conversionService->guardarDuracion($archivoLitePath, $postId, $index);

        /* Análisis con IA solo para el primer audio */
        if ($index === 1) {
            $this->analizarYGuardarMetasAudio($postId, $archivoLitePath, $index);
        }

        $this->logger->info('audio', "Procesamiento completado para Post ID: {$postId}");
        return true;
    }

    /**
     * Analiza el audio con scripts de Python e IA y guarda los metadatos
     * 
     * @param int $postId ID del post
     * @param string $audioPath Ruta del archivo de audio
     * @param int $index Índice del audio
     * @param string|null $nombreArchivo Nombre del archivo (opcional, para contexto IA)
     * @param string|null $carpeta Nombre de la carpeta (opcional, para contexto IA)
     * @param string|null $carpetaAbuela Nombre de la carpeta abuela (opcional, para contexto IA)
     */
    public function analizarYGuardarMetasAudio(
        int $postId,
        string $audioPath,
        int $index,
        ?string $nombreArchivo = null,
        ?string $carpeta = null,
        ?string $carpetaAbuela = null
    ): void {
        /* Ejecutar script Python para análisis de audio */
        $resultados = $this->analisisService->ejecutarAnalisis($audioPath);

        if ($resultados) {
            $this->analisisService->guardarResultados($postId, $resultados, $index);
        }

        /* Generar descripción con IA */
        $this->iaService->generarDescripcion($postId, $audioPath, $index, $nombreArchivo, $carpeta, $carpetaAbuela);

        /* Actualizar datos del algoritmo con los resultados */
        $this->iaService->actualizarDatosAlgoritmo($postId, $resultados ?? [], $index);

        /* Marcar como procesado por IA */
        update_post_meta($postId, 'flashIA', true);

        $this->logger->info('audio', "Metadatos de 'datosAlgoritmo' actualizados para el post ID: {$postId}");
    }
}
