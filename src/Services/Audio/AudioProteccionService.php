<?php

/**
 * Servicio de protección y optimización de audio (Fachada)
 * 
 * Orquesta la protección de archivos, regeneración y optimización de audio
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class AudioProteccionService
{
    private static ?AudioProteccionService $instancia = null;
    private \Logger $logger;
    private AudioRegeneracionService $regeneracionService;
    private AudioOptimizacionService $optimizacionService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->regeneracionService = AudioRegeneracionService::obtenerInstancia();
        $this->optimizacionService = AudioOptimizacionService::obtenerInstancia();
        $this->registrarHooks();
        $this->registrarCronJobs();
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
     * Registra los hooks de WordPress
     */
    private function registrarHooks(): void
    {
        /* Proteger archivos de audio contra eliminación */
        add_filter('pre_delete_attachment', [$this, 'protegerArchivoAudio'], 10, 2);
    }

    /**
     * Registra los cron jobs
     */
    private function registrarCronJobs(): void
    {
        /* Intervalo de 6 horas */
        add_filter('cron_schedules', [$this, 'agregarIntervalos']);

        /* Programar eventos */
        if (!wp_next_scheduled('regenerar_audio_lite_evento')) {
            wp_schedule_event(time(), 'cada_seis_horas', 'regenerar_audio_lite_evento');
        }

        if (!wp_next_scheduled('minutos55_evento')) {
            wp_schedule_event(time(), 'cada55', 'minutos55_evento');
        }

        /* Registrar callbacks delegando a servicios especializados */
        add_action('regenerar_audio_lite_evento', [$this->regeneracionService, 'regenerarLite']);
        add_action('minutos55_evento', [$this->optimizacionService, 'optimizar64kAudios']);
    }

    /**
     * Agrega intervalos personalizados al cron
     */
    public function agregarIntervalos(array $schedules): array
    {
        $schedules['cada_seis_horas'] = [
            'interval' => 21600,
            'display' => __('Cada 6 Horas')
        ];

        $schedules['cada55'] = [
            'interval' => 3300,
            'display' => __('Cada 55 minutos')
        ];

        return $schedules;
    }

    /**
     * Protege archivos de audio contra eliminación accidental
     */
    public function protegerArchivoAudio($delete, $post): bool
    {
        $archivoPath = get_attached_file($post);

        if ($archivoPath && strpos($archivoPath, '/audio/') !== false) {
            $this->logger->warning('audio', "Intento de eliminación de archivo de audio: $archivoPath");
            return false;
        }

        return $delete;
    }

    /* 
     * Métodos delegados a servicios especializados
     */

    /**
     * Regenera archivos de audio lite que falten
     */
    public function regenerarLite(): void
    {
        $this->regeneracionService->regenerarLite();
    }

    /**
     * Optimiza audios a 64kbps en lote
     */
    public function optimizar64kAudios(int $limite = 10000): void
    {
        $this->optimizacionService->optimizar64kAudios($limite);
    }

    /**
     * Optimiza el audio de un post individual
     */
    public function optimizarAudioPost(int $postId): void
    {
        $this->optimizacionService->optimizarAudioPost($postId);
    }

    /**
     * Limpia los datos de waveform de un post
     */
    public function limpiarWaveform(int $postId): void
    {
        $this->optimizacionService->limpiarWaveform($postId);
    }
}
