<?php

/**
 * Servicio de procesamiento de posts múltiples
 * 
 * Maneja posts con múltiples audios:
 * - División en posts individuales
 * - Copia de metadatos
 * - Limpieza del post original
 *
 * @package Kamples\Services\Contenido
 * @since 1.0.0
 */

namespace Kamples\Services\Contenido;

class AutoPostMultipleService
{
    private static ?AutoPostMultipleService $instancia = null;
    private \Logger $logger;
    private AutoPostCreacionService $creacionService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->creacionService = AutoPostCreacionService::obtenerInstancia();
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
     * Procesa un post con múltiples audios y genera nuevos posts para cada uno
     */
    public function procesarMultiples(int $postIdOriginal): void
    {
        if (!$postIdOriginal) return;

        $post = get_post($postIdOriginal);
        if (!$post || $post->post_type !== 'social_post') return;

        $isMultiple = get_post_meta($postIdOriginal, 'multiple', true);
        if ($isMultiple !== '1') return;

        $authorId = $post->post_author;

        /* Copiar metas */
        $metasToCopy = ['paraColab', 'paraDescarga', 'artista', 'fan', 'rola', 'sample', 'tagsUsuario', 'tienda', 'nombreLanzamiento'];
        $metaValues = [];
        foreach ($metasToCopy as $key) {
            $metaValues[$key] = get_post_meta($postIdOriginal, $key, true);
        }

        $imagenDestacadaId = get_post_thumbnail_id($postIdOriginal);
        $idsNuevosPosts = [];
        $multiplesEncontrados = false;

        for ($i = 2; $i <= 30; $i++) {
            $audioLiteId = get_post_meta($postIdOriginal, 'post_audio_lite_' . $i, true);
            $audioIdHash = get_post_meta($postIdOriginal, 'idHash_audioId' . $i, true);
            $audioId = get_post_meta($postIdOriginal, 'post_audio' . $i, true);

            if (!empty($audioLiteId) && !empty($audioIdHash) && !empty($audioId)) {
                $multiplesEncontrados = true;
                $rutaAudioLite = wp_get_attachment_url($audioLiteId);

                if ($rutaAudioLite) {
                    $uploadDir = wp_upload_dir();
                    $rutaServidor = str_replace($uploadDir['baseurl'], $uploadDir['basedir'], $rutaAudioLite);

                    /* Crear nuevo post */
                    $nuevoPostId = $this->creacionService->crearPost('', $rutaServidor, $audioIdHash, $authorId, $postIdOriginal);

                    if (!is_wp_error($nuevoPostId) && $nuevoPostId) {
                        $idsNuevosPosts[] = $nuevoPostId;

                        if (!empty($imagenDestacadaId)) {
                            set_post_thumbnail($nuevoPostId, $imagenDestacadaId);
                        }

                        /* Actualizar metas en nuevo post */
                        foreach ($metaValues as $key => $val) {
                            if (!empty($val)) update_post_meta($nuevoPostId, $key, $val);
                        }

                        /* Metas de la rola específica */
                        $this->copiarMetasEspecificas($postIdOriginal, $nuevoPostId, $i);

                        update_post_meta($nuevoPostId, 'post_audio', $audioId);

                        /* Limpiar original */
                        $this->limpiarMetasOriginales($postIdOriginal, $i);

                        sleep(2);
                    }
                }
            }
        }

        $this->finalizarProcesamiento($postIdOriginal, $multiplesEncontrados, $idsNuevosPosts);
    }

    /**
     * Copia metadatos específicos de una rola al nuevo post
     */
    private function copiarMetasEspecificas(int $postIdOriginal, int $nuevoPostId, int $index): void
    {
        $precio = get_post_meta($postIdOriginal, 'precioRola' . $index, true);
        if ($precio) update_post_meta($nuevoPostId, 'precioRola', $precio);

        $name = get_post_meta($postIdOriginal, 'nombreRola' . $index, true);
        if ($name) update_post_meta($nuevoPostId, 'nombreRola', $name);

        $audioUrl = get_post_meta($postIdOriginal, 'audioUrl' . $index, true);
        if ($audioUrl) update_post_meta($nuevoPostId, 'audioUrl', $audioUrl);

        $duration = get_post_meta($postIdOriginal, 'audio_duration_' . $index, true);
        if ($duration) update_post_meta($nuevoPostId, 'audio_duration_1', $duration);
    }

    /**
     * Limpia los metadatos del post original para un índice específico
     */
    private function limpiarMetasOriginales(int $postIdOriginal, int $index): void
    {
        delete_post_meta($postIdOriginal, 'post_audio_lite_' . $index);
        delete_post_meta($postIdOriginal, 'post_audio' . $index);
        delete_post_meta($postIdOriginal, 'idHash_audioId' . $index);
        delete_post_meta($postIdOriginal, 'precioRola' . $index);
        delete_post_meta($postIdOriginal, 'nombreRola' . $index);
        delete_post_meta($postIdOriginal, 'audioUrl' . $index);
        delete_post_meta($postIdOriginal, 'audio_duration_' . $index);
    }

    /**
     * Finaliza el procesamiento y actualiza el estado del post original
     */
    private function finalizarProcesamiento(int $postIdOriginal, bool $multiplesEncontrados, array $idsNuevosPosts): void
    {
        if (!$multiplesEncontrados) {
            delete_post_meta($postIdOriginal, 'multiple');
        } else {
            if (!empty($idsNuevosPosts)) {
                update_post_meta($postIdOriginal, 'posts_generados', $idsNuevosPosts);
            }

            /* Verificar si quedan más audios */
            $quedan = false;
            for ($i = 2; $i <= 30; $i++) {
                if (get_post_meta($postIdOriginal, 'post_audio_lite_' . $i, true)) {
                    $quedan = true;
                    break;
                }
            }
            if (!$quedan) delete_post_meta($postIdOriginal, 'multiple');
        }
    }
}
