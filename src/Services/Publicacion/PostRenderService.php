<?php

/**
 * Servicio de preparación de datos para renderizado de posts
 * 
 * Centraliza la lógica de obtención y preparación de variables
 * necesarias para renderizar posts.
 *
 * @package Kamples\Services\Publicacion
 * @since 1.0.0
 */

namespace Kamples\Services\Publicacion;

class PostRenderService
{
    private \Logger $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene las variables necesarias para renderizar un post
     * 
     * @param int|null $postId ID del post (null usa el global)
     * @return array Variables del post
     */
    public function obtenerVariablesPost(?int $postId = null): array
    {
        if ($postId === null) {
            global $post;
            $postId = $post->ID;
        }

        $usuarioActual = get_current_user_id();
        $autoresSuscritos = get_user_meta($usuarioActual, 'offering_user_ids', true);
        $autorId = get_post_field('post_author', $postId);

        $datosAlgoritmo = get_post_meta($postId, 'datosAlgoritmo', true);
        $datosAlgoritmoRespaldo = get_post_meta($postId, 'datosAlgoritmo_respaldo', true);

        if (is_array($datosAlgoritmoRespaldo)) {
            $datosAlgoritmoRespaldo = json_encode($datosAlgoritmoRespaldo);
        } elseif (is_object($datosAlgoritmoRespaldo)) {
            $datosAlgoritmoRespaldo = serialize($datosAlgoritmoRespaldo);
        }

        $datosAlgoritmoFinal = empty($datosAlgoritmo) ? $datosAlgoritmoRespaldo : $datosAlgoritmo;

        return [
            'current_user_id' => $usuarioActual,
            'autores_suscritos' => $autoresSuscritos,
            'author_id' => $autorId,
            'es_suscriptor' => in_array($autorId, (array)$autoresSuscritos),
            'author_name' => get_the_author_meta('display_name', $autorId),
            'author_avatar' => function_exists('imagenPerfil') ? imagenPerfil($autorId) : '',
            'audio_id_lite' => get_post_meta($postId, 'post_audio_lite', true),
            'audio_id' => get_post_meta($postId, 'post_audio', true),
            'audio_url' => wp_get_attachment_url(get_post_meta($postId, 'post_audio', true)),
            'audio_lite' => wp_get_attachment_url(get_post_meta($postId, 'post_audio_lite', true)),
            'wave' => get_post_meta($postId, 'waveform_image_url', true),
            'post_date' => get_the_date('', $postId),
            'block' => get_post_meta($postId, 'esExclusivo', true),
            'colab' => get_post_meta($postId, 'paraColab', true),
            'post_status' => get_post_status($postId),
            'bpm' => get_post_meta($postId, 'audio_bpm', true),
            'key' => get_post_meta($postId, 'audio_key', true),
            'scale' => get_post_meta($postId, 'audio_scale', true),
            'detallesIA' => get_post_meta($postId, 'audio_descripcion', true),
            'datosAlgoritmo' => $datosAlgoritmoFinal,
            'postAut' => get_post_meta($postId, 'postAut', true),
            'ultimoEdit' => get_post_meta($postId, 'ultimoEdit', true),
        ];
    }

    /**
     * Obtiene variables para renderizar un artículo
     * 
     * @param int|null $postId ID del post
     * @return array
     */
    public function obtenerVariablesArticulo(?int $postId = null): array
    {
        if ($postId === null) {
            global $post;
            $postId = $post->ID;
        }

        $autorId = get_post_field('post_author', $postId);

        return [
            'fecha' => get_the_date('', $postId),
            'colecStatus' => get_post_status($postId),
            'autorId' => $autorId,
        ];
    }

    /**
     * Obtiene la URL de imagen del artículo optimizada
     * 
     * @param int $postId ID del post
     * @return string URL de la imagen
     */
    public function obtenerImagenArticulo(int $postId): string
    {
        $imagenSize = 'large';
        $quality = 60;

        if (function_exists('imagenPost')) {
            $imagenUrl = imagenPost($postId, $imagenSize, $quality, 'all', false, true);
        } else {
            $imagenUrl = get_the_post_thumbnail_url($postId, $imagenSize);
        }

        if (function_exists('img')) {
            $imagenProcesada = img($imagenUrl, $quality, 'all');
        } else {
            $imagenProcesada = $imagenUrl;
        }

        return esc_url($imagenProcesada);
    }

    /**
     * Obtiene la URL del audio de forma segura
     * 
     * @param mixed $audioId ID del audio
     * @return string URL segura del audio
     */
    public function obtenerUrlAudioSegura($audioId): string
    {
        if (function_exists('audioUrlSegura')) {
            $url = audioUrlSegura($audioId);
            if (is_wp_error($url)) {
                return '';
            }
            return $url;
        }

        return wp_get_attachment_url($audioId) ?: '';
    }

    /**
     * Obtiene una imagen aleatoria de un directorio
     * 
     * @param string $directory Directorio a buscar
     * @return string|false Ruta de la imagen o false
     */
    public function obtenerImagenAleatoria(string $directory)
    {
        static $cache = [];

        if (isset($cache[$directory])) {
            return $cache[$directory][array_rand($cache[$directory])];
        }

        if (!is_dir($directory)) {
            return false;
        }

        $images = glob(rtrim($directory, '/') . '/*.{jpg,jpeg,png,gif,jfif}', GLOB_BRACE);

        if (!$images) {
            return false;
        }

        $cache[$directory] = $images;
        return $images[array_rand($images)];
    }

    /**
     * Sube una imagen a la librería de medios
     * 
     * @param string $filePath Ruta del archivo
     * @param int $postId ID del post padre
     * @return int|false ID del attachment o false
     */
    public function subirImagenALibreria(string $filePath, int $postId)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileContents = file_get_contents($filePath);
        if ($fileContents === false) {
            return false;
        }

        $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($fileExt === 'jfif') {
            $fileExt = 'jpeg';
            $newFileName = pathinfo($filePath, PATHINFO_FILENAME) . '.jpeg';
            $uploadFile = wp_upload_bits($newFileName, null, $fileContents);
        } else {
            $uploadFile = wp_upload_bits(basename($filePath), null, $fileContents);
        }

        if ($uploadFile['error']) {
            $this->logger->error('post', 'Error subiendo imagen', [
                'error' => $uploadFile['error']
            ]);
            return false;
        }

        $filetype = wp_check_filetype($uploadFile['file'], null);
        if (!$filetype['type']) {
            return false;
        }

        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name(pathinfo($uploadFile['file'], PATHINFO_BASENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $postId,
        ];

        $attachId = wp_insert_attachment($attachment, $uploadFile['file'], $postId);

        if (!is_wp_error($attachId)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachData = wp_generate_attachment_metadata($attachId, $uploadFile['file']);
            wp_update_attachment_metadata($attachId, $attachData);
            return $attachId;
        }

        return false;
    }

    /**
     * Limpia el JSON de datos del algoritmo
     * 
     * @param mixed $jsonData Datos JSON
     * @return string JSON limpio
     */
    public function limpiarJSON($jsonData): string
    {
        if (
            is_string($jsonData) &&
            substr($jsonData, 0, 1) === '"' &&
            substr($jsonData, -1) === '"'
        ) {
            $jsonData = json_decode($jsonData);
        }

        if (is_array($jsonData) || is_object($jsonData)) {
            $jsonData = json_encode($jsonData);
        }

        return (string)$jsonData;
    }

    /**
     * Inicializa los filtros de soporte para formatos de imagen
     * 
     * @return void
     */
    public static function inicializarFiltros(): void
    {
        add_filter('upload_mimes', function ($mimes) {
            $mimes['jfif'] = 'image/jpeg';
            return $mimes;
        });

        add_filter('wp_check_filetype_and_ext', function ($types, $filename, $mimes) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($ext === 'jfif') {
                return ['ext' => 'jpeg', 'type' => 'image/jpeg'];
            }
            return $types;
        }, 10, 3);
    }
}
