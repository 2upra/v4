<?php

/**
 * Servicio de procesamiento de archivos para posts.
 * 
 * Maneja el procesamiento de archivos adjuntos: URLs, renombrado, IDs.
 *
 * @package Kamples\Services\Publicacion
 * @since 1.0.0
 */

namespace Kamples\Services\Publicacion;

use Kamples\Services\Audio\HashService;
use Kamples\Services\Audio\AudioProcessingService;

class PostArchivosService
{
    private static ?PostArchivosService $instancia = null;
    private HashService $hashService;
    private \Logger $logger;

    private function __construct()
    {
        $this->hashService = HashService::obtenerInstancia();
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
     * Confirma archivos subidos asociados al post
     */
    public function confirmarArchivos(int $postId): void
    {
        $tiposCampos = ['archivoId', 'audioId', 'imagenId'];
        $maxCampos = 30;

        foreach ($tiposCampos as $tipo) {
            for ($i = 1; $i <= $maxCampos; $i++) {
                $campo = $tipo . $i;
                if (!empty($_POST[$campo])) {
                    $fileId = intval($_POST[$campo]);
                    if ($fileId > 0) {
                        update_post_meta($postId, 'idHash_' . $campo, $fileId);
                        $this->hashService->confirmarHashId($fileId);
                    }
                }
            }
        }
    }

    /**
     * Procesa las URLs de archivos
     */
    public function procesarURLs(int $postId): void
    {
        $tiposURLs = [
            'imagenUrl' => ['procesarArchivo', false],
            'audioUrl' => ['procesarArchivo', true],
            'archivoUrl' => ['procesarArchivo', false],
        ];
        $maxCampos = 30;

        foreach ($tiposURLs as $tipo => $callbackData) {
            $parametroAdicional = $callbackData[1] ?? null;

            for ($i = 1; $i <= $maxCampos; $i++) {
                $campo = $tipo . $i;
                if (!empty($_POST[$campo])) {
                    $url = esc_url_raw($_POST[$campo]);
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        $this->procesarArchivo($postId, $campo, $parametroAdicional);
                    }
                }
            }
        }
    }

    /**
     * Procesa un archivo individual
     */
    private function procesarArchivo(int $postId, string $campo, bool $renombrar = false): bool
    {
        $url = esc_url_raw($_POST[$campo]);
        $archivoId = $this->obtenerArchivoId($url, $postId);

        if ($archivoId && !is_wp_error($archivoId)) {
            if ($this->actualizarMetaConArchivo($postId, $campo, $archivoId) === false) {
                return false;
            }
            if ($renombrar) {
                $this->renombrarArchivoAdjunto($postId, $archivoId, $campo);
            }
            return true;
        }

        return false;
    }

    /**
     * Obtiene el ID de un archivo desde su URL
     */
    private function obtenerArchivoId(string $url, int $postId)
    {
        $archivoId = attachment_url_to_postid($url);

        if (!$archivoId) {
            $filePath = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $url);
            if (file_exists($filePath)) {
                $archivoId = media_handle_sideload([
                    'name' => basename($filePath),
                    'tmp_name' => $filePath
                ], $postId);

                if (is_wp_error($archivoId)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return $archivoId;
    }

    /**
     * Actualiza meta del post con ID de archivo
     */
    private function actualizarMetaConArchivo(int $postId, string $campo, int $archivoId): bool
    {
        $metaMapping = [
            'imagenUrl' => 'imagenID',
            'audioUrl' => 'post_audio',
            'archivoUrl' => 'archivoID'
        ];

        $pattern = '/^(?<base>imagenUrl|audioUrl|archivoUrl)(?<index>\d*)$/';

        if (preg_match($pattern, $campo, $matches)) {
            $baseField = $matches['base'];
            $index = $matches['index'];

            if (isset($metaMapping[$baseField])) {
                $baseMetaKey = $metaMapping[$baseField];
                $metaKey = $baseMetaKey . ($index !== '' ? $index : '');
                update_post_meta($postId, $metaKey, $archivoId);

                if ($baseField === 'imagenUrl' && $index === '1') {
                    set_post_thumbnail($postId, $archivoId);
                }
            } else {
                update_post_meta($postId, $campo, $archivoId);
            }
        } else {
            update_post_meta($postId, $campo, $archivoId);
        }

        return update_post_meta($postId, $campo, $archivoId) !== false;
    }

    /**
     * Renombra un archivo adjunto
     */
    private function renombrarArchivoAdjunto(int $postId, int $archivoId, string $campo): void
    {
        if (!preg_match('/(\d+)$/', $campo, $matches)) {
            return;
        }

        $indice = intval($matches[1]);
        $idHashCampo = "idHash_audioId{$indice}";
        $idHash = get_post_meta($postId, $idHashCampo, true);

        if (empty($idHash)) {
            return;
        }

        $post = get_post($postId);
        $author = get_userdata($post->post_author);

        if (!$post || !$author) {
            return;
        }

        $filePath = get_attached_file($archivoId);
        if (!$filePath || !file_exists($filePath)) {
            return;
        }

        $info = pathinfo($filePath);
        $randomId = rand(10000, 99999);

        $newFilename = sprintf(
            '2upra_%s_%s_%s.%s',
            sanitize_file_name(mb_substr($author->user_login, 0, 20)),
            sanitize_file_name(mb_substr($post->post_content, 0, 40)),
            $randomId,
            $info['extension']
        );

        $newFilePath = $info['dirname'] . DIRECTORY_SEPARATOR . $newFilename;

        if (rename($filePath, $newFilePath)) {
            $uploadDir = wp_upload_dir();
            $publicUrl = str_replace($uploadDir['basedir'], $uploadDir['baseurl'], $newFilePath);

            $this->hashService->actualizarUrlArchivo((int)$idHash, $publicUrl);
            update_attached_file($archivoId, $newFilePath);
            update_post_meta($postId, 'sample', true);

            /* Procesar audio ligero usando el servicio */
            $audioProcessingService = AudioProcessingService::obtenerInstancia();
            $audioProcessingService->procesarAudioLigero($postId, $archivoId, $indice);
        }
    }
}
