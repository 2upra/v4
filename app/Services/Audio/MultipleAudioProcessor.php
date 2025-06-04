<?php

namespace App\Services\Audio;

use App\Services\Post\PostCreationService;
use App\Services\Post\PostMetaHandler; // Placeholder for RF-PMH-002

class MultipleAudioProcessor
{
    private PostCreationService $postCreationService;
    private PostMetaHandler $postMetaHandler;

    public function __construct(PostCreationService $postCreationService, PostMetaHandler $postMetaHandler)
    {
        $this->postCreationService = $postCreationService;
        $this->postMetaHandler = $postMetaHandler;
    }

    /**
     * Main entry point to process multiple audio posts from an original post.
     *
     * @param int $postIdOriginal The ID of the original post.
     * @return void
     */
    public function processMultiplePosts(int $postIdOriginal): void
    {
        if (!get_post($postIdOriginal)) {
            error_log("El post con ID: {$postIdOriginal} no existe.");
            return;
        }

        if (get_post_type($postIdOriginal) !== 'social_post') {
            error_log("El post con ID: {$postIdOriginal} no es del tipo 'social_post'.");
            return;
        }

        $isMultiple = get_post_meta($postIdOriginal, 'multiple', true);
        if ($isMultiple !== '1') {
            error_log("El post con ID: {$postIdOriginal} no tiene el meta 'multiple' igual a 1.");
            return;
        }

        // Collect all necessary metadata from the original post
        $postMeta = [
            'author_id' => get_post_field('post_author', $postIdOriginal),
            'paraColab' => get_post_meta($postIdOriginal, 'paraColab', true),
            'paraDescarga' => get_post_meta($postIdOriginal, 'paraDescarga', true),
            'artista' => get_post_meta($postIdOriginal, 'artista', true),
            'fan' => get_post_meta($postIdOriginal, 'fan', true),
            'rola' => get_post_meta($postIdOriginal, 'rola', true),
            'sample' => get_post_meta($postIdOriginal, 'sample', true),
            'tagsUsuario' => get_post_meta($postIdOriginal, 'tagsUsuario', true),
            'tienda' => get_post_meta($postIdOriginal, 'tienda', true),
            'nombreLanzamiento' => get_post_meta($postIdOriginal, 'nombreLanzamiento', true),
            'imagen_destacada_id' => get_post_thumbnail_id($postIdOriginal),
        ];

        list($multiples_audios_encontrados, $ids_nuevos_posts) = $this->processAudiosForOriginalPost($postIdOriginal, $postMeta);

        if (!$multiples_audios_encontrados) {
            delete_post_meta($postIdOriginal, 'multiple');
        } else {
            if (!empty($ids_nuevos_posts)) {
                update_post_meta($postIdOriginal, 'posts_generados', $ids_nuevos_posts);
            }
            $quedan_audios = false;
            for ($i = 2; $i <= 30; $i++) {
                if (get_post_meta($postIdOriginal, 'post_audio_lite_' . $i, true)) {
                    $quedan_audios = true;
                    break;
                }
            }
            if (!$quedan_audios) {
                delete_post_meta($postIdOriginal, 'multiple');
            }
        }
    }

    /**
     * Processes individual audio files attached to the original post.
     * This method encapsulates the logic previously in procesarAudiosMultiples.
     *
     * @param int $postIdOriginal The ID of the original post.
     * @param array $postMeta An associative array of post metadata to copy.
     * @return array A tuple containing:
     *               - bool $multiples_audios_encontrados True if any multiple audios were found and processed.
     *               - array $ids_nuevos_posts An array of IDs of the newly created posts.
     */
    private function processAudiosForOriginalPost(int $postIdOriginal, array $postMeta): array
    {
        $multiples_audios_encontrados = false;
        $ids_nuevos_posts = [];

        // Extract individual meta values for clarity, though passing the whole array is cleaner
        $author_id = $postMeta['author_id'];
        $paraColab = $postMeta['paraColab'];
        $paraDescarga = $postMeta['paraDescarga'];
        $artista = $postMeta['artista'];
        $fan = $postMeta['fan'];
        $rola = $postMeta['rola'];
        $sample = $postMeta['sample'];
        $tagsUsuario = $postMeta['tagsUsuario'];
        $tienda = $postMeta['tienda'];
        $nombreLanzamiento = $postMeta['nombreLanzamiento'];
        $imagen_destacada_id = $postMeta['imagen_destacada_id'];

        // Define constants for the loop range
        const MIN_AUDIO_INDEX = 2;
        const MAX_AUDIO_INDEX = 30;

        for ($i = self::MIN_AUDIO_INDEX; $i <= self::MAX_AUDIO_INDEX; $i++) {
            $audio_lite_meta_key = 'post_audio_lite_' . $i;
            $audio_meta_key = 'post_audio' . $i;
            $idHash_audioId_key = 'idHash_audioId' . $i;
            $precio_key = 'precioRola' . $i;
            $name_key = 'nombreRola' . $i;
            $audioUrl_key = 'audioUrl' . $i;
            $audio_duration_key = 'audio_duration_' . $i;

            $audio_lite_id = get_post_meta($postIdOriginal, $audio_lite_meta_key, true);
            $audio_id_hash = get_post_meta($postIdOriginal, $idHash_audioId_key, true);
            $audio_id = get_post_meta($postIdOriginal, $audio_meta_key, true);
            $precio = get_post_meta($postIdOriginal, $precio_key, true);
            $name = get_post_meta($postIdOriginal, $name_key, true);
            $audioUrl = get_post_meta($postIdOriginal, $audioUrl_key, true);
            $audio_duration = get_post_meta($postIdOriginal, $audio_duration_key, true);

            if (!empty($audio_lite_id) && !empty($audio_id_hash) && !empty($audio_id)) {
                $multiples_audios_encontrados = true;
                $ruta_audio_lite = wp_get_attachment_url($audio_lite_id);
                $upload_dir = wp_upload_dir();
                $ruta_servidor = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ruta_audio_lite);

                // Call the global crearAutPost function, as it's refactored in RF-CAP-003
                // It's not a dependency of this class, but a global utility.
                $nuevoPost = crearAutPost('', $ruta_servidor, $audio_id_hash, $author_id, $postIdOriginal);

                if (!is_wp_error($nuevoPost) && $nuevoPost) {
                    $ids_nuevos_posts[] = $nuevoPost;

                    // Copy the featured image to the new post
                    if (!empty($imagen_destacada_id)) {
                        set_post_thumbnail($nuevoPost, $imagen_destacada_id);
                    }

                    // Copy other metadata
                    // This section is highly repetitive and should be refactored using PostMetaHandler in RF-PMH-002
                    if (!empty($audioUrl)) {
                        update_post_meta($nuevoPost, 'audioUrl', $audioUrl);
                    }
                    if (!empty($audio_duration)) {
                        update_post_meta($nuevoPost, 'audio_duration_1', $audio_duration);
                    }
                    if (!empty($paraColab)) {
                        update_post_meta($nuevoPost, 'paraColab', $paraColab);
                    }
                    if (!empty($paraDescarga)) {
                        update_post_meta($nuevoPost, 'paraDescarga', $paraDescarga);
                    }
                    if (!empty($artista)) {
                        update_post_meta($nuevoPost, 'artista', $artista);
                    }
                    if (!empty($fan)) {
                        update_post_meta($nuevoPost, 'fan', $fan);
                    }
                    if (!empty($rola)) {
                        update_post_meta($nuevoPost, 'rola', $rola);
                    }
                    if (!empty($tienda)) {
                        update_post_meta($nuevoPost, 'tienda', $sample); // Note: Original code copies $sample to 'tienda'
                    }
                    if (!empty($sample)) {
                        update_post_meta($nuevoPost, 'sample', $sample);
                    }
                    if (!empty($tagsUsuario)) {
                        update_post_meta($nuevoPost, 'tagsUsuario', $tagsUsuario);
                    }
                    if (!empty($audio_id)) {
                        update_post_meta($nuevoPost, 'post_audio', $audio_id);
                    }
                    if (!empty($precio)) {
                        update_post_meta($nuevoPost, 'precioRola', $precio);
                    }
                    if (!empty($name)) {
                        update_post_meta($nuevoPost, 'nombreRola', $name);
                    }
                    if (!empty($nombreLanzamiento)) {
                        update_post_meta($nuevoPost, 'nombreLanzamiento', $nombreLanzamiento);
                    }

                    // Delete metadata from the original post
                    // This section is also highly repetitive and should be refactored using PostMetaHandler in RF-PMH-002
                    delete_post_meta($postIdOriginal, $audio_lite_meta_key);
                    delete_post_meta($postIdOriginal, $audio_meta_key);
                    delete_post_meta($postIdOriginal, $idHash_audioId_key);
                    delete_post_meta($postIdOriginal, $precio_key);
                    delete_post_meta($postIdOriginal, $name_key);
                    delete_post_meta($postIdOriginal, $audioUrl_key);
                    delete_post_meta($postIdOriginal, $audio_duration_key);
                    sleep(2);
                }
            }
        }
        return [$multiples_audios_encontrados, $ids_nuevos_posts];
    }
}
