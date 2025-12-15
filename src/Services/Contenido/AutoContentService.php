<?php

namespace Kamples\Services\Contenido;

use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use WP_Query;
use Kamples\Services\IAService;
use Kamples\Services\Audio\HashService;

/**
 * Servicio para mejora automática de contenido y edición via IA.
 * 
 * @package Kamples\Services\Contenido
 * @since 1.0.0
 */
class AutoContentService
{
    private static ?AutoContentService $instancia = null;
    private ?\Logger $logger;
    private ?IAService $iaService;
    private ?HashService $hashService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->iaService = new IAService();
        $this->hashService = HashService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Mejora la descripción de un audio existente usando IA Pro
     */
    public function mejorarDescripcionAudioPro(int $postId, string $archivoAudio): void
    {
        $postAut = get_post_meta($postId, 'postAut', true);
        $verificado = get_post_meta($postId, 'Verificado', true);

        if ($postAut == 1 && $verificado != 1) {
            $this->logger->info('ia', "Skip post $postId: postAut=1 pero no verificado.");
            return;
        }

        $postContent = get_post_field('post_content', $postId);
        $prompt = "El usuario ya subió este audio... descripción:\"{$postContent}\". Estrcutura JSON ONLY: " .
            '{"Descripcion":{"es":"","en":""},"Instrumentos posibles":{"es":[],"en":[]},"Estado de animo":{"es":[],"en":[]},"Genero posible":{"es":[],"en":[]},"Artista posible":{"es":[],"en":[]},"Tipo de audio":{"es":"","en":""},"Tags posibles":{"es":[],"en":[]},"Sugerencia de busqueda":{"es":[],"en":[]}}';

        $descripcionMejorada = $this->iaService->generarDescripcionPro($archivoAudio, $prompt);

        if ($descripcionMejorada) {
            $jsonStr = trim($descripcionMejorada);
            $jsonStr = trim($jsonStr, "```json");
            $jsonStr = trim($jsonStr, "```");

            $descripcionProcesada = json_decode($jsonStr, true);

            if ($descripcionProcesada) {
                /* Actualizar metadatos preservando existentes */
                $datosAlgoritmo = json_decode(get_post_meta($postId, 'datosAlgoritmo', true) ?: '[]', true);

                $nuevosDatos = [
                    'descripcion_ia_pro' => ['es' => $descripcionProcesada['Descripcion']['es'] ?? '', 'en' => $descripcionProcesada['Descripcion']['en'] ?? ''],
                    'instrumentos_posibles' => ['es' => $descripcionProcesada['Instrumentos posibles']['es'] ?? [], 'en' => $descripcionProcesada['Instrumentos posibles']['en'] ?? []],
                    'estado_animo' => ['es' => $descripcionProcesada['Estado de animo']['es'] ?? [], 'en' => $descripcionProcesada['Estado de animo']['en'] ?? []],
                    'artista_posible' => ['es' => $descripcionProcesada['Artista posible']['es'] ?? [], 'en' => $descripcionProcesada['Artista posible']['en'] ?? []],
                    'genero_posible' => ['es' => $descripcionProcesada['Genero posible']['es'] ?? [], 'en' => $descripcionProcesada['Genero posible']['en'] ?? []],
                    'tipo_audio' => ['es' => $descripcionProcesada['Tipo de audio']['es'] ?? [], 'en' => $descripcionProcesada['Tipo de audio']['en'] ?? []],
                    'tags_posibles' => ['es' => $descripcionProcesada['Tags posibles']['es'] ?? [], 'en' => $descripcionProcesada['Tags posibles']['en'] ?? []],
                    'sugerencia_busqueda' => ['es' => $descripcionProcesada['Sugerencia de busqueda']['es'] ?? [], 'en' => $descripcionProcesada['Sugerencia de busqueda']['en'] ?? []]
                ];

                $datosActualizados = array_merge($datosAlgoritmo, $nuevosDatos);
                update_post_meta($postId, 'datosAlgoritmo', json_encode($datosActualizados, JSON_UNESCAPED_UNICODE));
                update_post_meta($postId, 'proIA', true);

                $this->logger->info('ia', "Descripcion IA Pro mejorada para post $postId");
            }
        }
    }

    /**
     * Cron Job: Procesa un audio pendiente
     */
    public function procesarUnAudio(): void
    {
        global $wpdb;

        $this->logger->info('ia', "Iniciando cron procesarUnAudio...");

        $query = "
            SELECT p.ID, pm.meta_value AS archivo_audio_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            LEFT JOIN {$wpdb->postmeta} proIA_meta ON p.ID = proIA_meta.post_id AND proIA_meta.meta_key = 'proIA'
            LEFT JOIN {$wpdb->postmeta} verificado_meta ON p.ID = verificado_meta.post_id AND verificado_meta.meta_key = 'Verificado'
            WHERE pm.meta_key = 'post_audio_lite'
            AND proIA_meta.meta_value IS NULL
            AND (verificado_meta.meta_value IS NULL OR verificado_meta.meta_value NOT IN ('true', '1'))
            AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
            LIMIT 1
        ";

        $postConAudio = $wpdb->get_row($query);

        if ($postConAudio) {
            $postId = $postConAudio->ID;
            $attachId = $postConAudio->archivo_audio_id;

            $url = wp_get_attachment_url($attachId);
            if (!$url) return;

            $baseDir = wp_upload_dir()['basedir'];
            $baseUrl = wp_upload_dir()['baseurl'];
            $path = str_replace($baseUrl, $baseDir, $url);

            if (file_exists($path)) {
                $this->mejorarDescripcionAudioPro($postId, $path);
            }
        }
    }

    /**
     * Rehace el nombre de un audio basado en IA y descripción
     */
    public function rehacerNombreAudio(int $postId, string $archivoAudio): ?string
    {
        if (!file_exists($archivoAudio)) return null;

        $userId = get_current_user_id();
        if (!user_can($userId, 'administrator')) return null;

        $postContent = get_post_field('post_content', $postId);
        $nombreOriginal = pathinfo($archivoAudio, PATHINFO_FILENAME);

        $prompt = "El archivo se llama '$nombreOriginal' ... descripción nueva: '$postContent'. Genera nombre corto. SOLO EL NOMBRE.";

        $nombreGenerado = $this->iaService->generarDescripcion($archivoAudio, $prompt);

        if ($nombreGenerado) {
            $cleanName = preg_replace('/[^A-Za-z0-9\- ]/', '', trim($nombreGenerado));
            $cleanName = substr(str_replace(' ', '_', $cleanName), 0, 60);
            $idUnica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 4);
            $finalName = '2upra_' . $cleanName . '_' . $idUnica;

            /* Renombrar adjuntos */
            $attachId = get_post_meta($postId, 'post_audio', true);
            $attachLiteId = get_post_meta($postId, 'post_audio_lite', true);

            if ($attachId && $attachLiteId) {
                $this->renombrarAdjunto($attachId, $finalName, false);
                $this->renombrarAdjunto($attachLiteId, $finalName, true);

                /* Manejar archivo original (rutaOriginal) si existe */
                $rutaOriginal = get_post_meta($postId, 'rutaOriginal', true);
                if ($rutaOriginal && file_exists($rutaOriginal)) {
                    $newNameOriginal = dirname($rutaOriginal) . '/' . $finalName . '.' . pathinfo($rutaOriginal, PATHINFO_EXTENSION);
                    if (rename($rutaOriginal, $newNameOriginal)) {
                        update_post_meta($postId, 'rutaOriginal', $newNameOriginal);
                        $this->logger->info('ia', "Renombrado original a $newNameOriginal");
                    }
                }

                /* Actualizar URL hash */
                $idHash = get_post_meta($postId, 'idHash_audioId', true);
                if ($idHash) {
                    $this->hashService->actualizarUrlArchivo($idHash, wp_get_attachment_url($attachId));
                }

                update_post_meta($postId, 'Verificado', true);
                return $finalName;
            }
        }
        return null;
    }

    private function renombrarAdjunto(int $attachId, string $newName, bool $isLite): bool
    {
        $path = get_attached_file($attachId);
        if (!file_exists($path)) return false;

        $dir = dirname($path);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($isLite) $newName .= '_lite';

        $newPath = $dir . '/' . $newName . '.' . $ext;

        if (rename($path, $newPath)) {
            update_attached_file($attachId, $newPath);
            $attachData = [
                'ID' => $attachId,
                'post_name' => sanitize_title($newName),
                'guid' => home_url('/') . str_replace(ABSPATH, '', $newPath)
            ];
            wp_update_post($attachData);
            return true;
        }
        return false;
    }
}
