<?php

/**
 * Servicio de creación de posts automáticos
 * 
 * Maneja la creación de posts en WordPress:
 * - Generación de datos con IA/Python
 * - Inserción de posts
 * - Adjuntar archivos
 * - Guardar metadatos
 *
 * @package Kamples\Services\Contenido
 * @since 1.0.0
 */

namespace Kamples\Services\Contenido;

use Kamples\Services\Audio\HashService;
use Kamples\Services\PythonService;
use Kamples\Services\IAService;
use WP_Error;

class AutoPostCreacionService
{
    private static ?AutoPostCreacionService $instancia = null;
    private \Logger $logger;
    private HashService $hashService;
    private PythonService $pythonService;
    private IAService $iaService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->hashService = HashService::obtenerInstancia();
        $this->pythonService = new PythonService();
        $this->iaService = new IAService();
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
     * Crea el post automático con los datos del audio
     */
    public function crearPost(?string $rutaOriginal, ?string $rutaWpLite, $fileId = null, $autorId = 44, $postOriginal = null)
    {
        if (!$autorId) $autorId = 44;

        if (empty($rutaWpLite) || !file_exists($rutaWpLite)) {
            return null;
        }

        $carpeta = !empty($rutaOriginal) ? basename(dirname($rutaOriginal)) : null;
        $carpetaAbuela = !empty($rutaOriginal) ? basename(dirname(dirname($rutaOriginal))) : null;
        $nombreArchivo = !empty($rutaOriginal) ? pathinfo($rutaOriginal, PATHINFO_FILENAME) : null;

        $datosAlgoritmo = $this->generarDatosAudio($rutaWpLite, $nombreArchivo, $carpeta, $carpetaAbuela);

        if (!$datosAlgoritmo) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return null;
        }

        $nombreGenerado = $datosAlgoritmo['nombre_corto']['en'] ?? ($datosAlgoritmo['nombre_corto']['es'] ?? '');
        if (is_array($nombreGenerado)) $nombreGenerado = $nombreGenerado[0] ?? '';

        if (!$nombreGenerado) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return null;
        }

        $nombreLimpio = preg_replace('/[^A-Za-z0-9\- áéíóúÁÉÍÓÚñÑ]/u', '', trim($nombreGenerado));
        $idUnica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $nombreFinal = substr($nombreLimpio . '_' . $idUnica . '_2upra', 0, 60);

        $nuevaRutaOriginal = null;
        $extOriginal = !empty($rutaOriginal) ? pathinfo($rutaOriginal, PATHINFO_EXTENSION) : '';

        if (!empty($rutaOriginal) && file_exists($rutaOriginal)) {
            $nuevaRutaOriginal = dirname($rutaOriginal) . '/' . $nombreFinal . '.' . $extOriginal;
            if (file_exists($nuevaRutaOriginal)) @unlink($nuevaRutaOriginal);

            if (!rename($rutaOriginal, $nuevaRutaOriginal)) {
                if ($fileId) $this->hashService->eliminarHash($fileId);
                return null;
            }
        }

        $extLite = pathinfo($rutaWpLite, PATHINFO_EXTENSION);
        $nuevoLite = dirname($rutaWpLite) . '/' . $nombreFinal . '_lite.' . $extLite;
        if (file_exists($nuevoLite)) @unlink($nuevoLite);

        if (!rename($rutaWpLite, $nuevoLite)) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return null;
        }

        $descripcion = $datosAlgoritmo['descripcion_corta']['en'] ?? ($datosAlgoritmo['descripcion_corta']['es'] ?? '');
        if (is_array($descripcion)) $descripcion = $descripcion[0] ?? '';

        $postData = [
            'post_title'    => mb_substr($descripcion, 0, 60),
            'post_content'  => $descripcion,
            'post_status'   => 'publish',
            'post_author'   => $autorId,
            'post_type'     => 'social_post',
        ];

        $postId = wp_insert_post($postData);

        if (is_wp_error($postId)) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return null;
        }

        if ($nuevaRutaOriginal) update_post_meta($postId, 'rutaOriginal', $nuevaRutaOriginal);
        update_post_meta($postId, 'rutaLiteOriginal', $nuevoLite);
        update_post_meta($postId, 'postAut', true);

        $audioOriginalId = null;
        if ($nuevaRutaOriginal) {
            $audioOriginalId = $this->adjuntarArchivo($nuevaRutaOriginal, $postId, $fileId);
            if (is_wp_error($audioOriginalId)) {
                wp_delete_post($postId, true);
                if ($fileId) $this->hashService->eliminarHash($fileId);
                return $audioOriginalId;
            }
            if (file_exists($nuevaRutaOriginal)) {
                unlink($nuevaRutaOriginal);
            }
        }

        $audioLiteId = $this->adjuntarArchivo($nuevoLite, $postId);
        if (is_wp_error($audioLiteId)) {
            wp_delete_post($postId, true);
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return $audioLiteId;
        }

        if ($audioOriginalId) update_post_meta($postId, 'post_audio', $audioOriginalId);
        update_post_meta($postId, 'post_audio_lite', $audioLiteId);

        if ($autorId === 44) {
            update_post_meta($postId, 'paraDescarga', true);
        }

        if ($nombreArchivo) update_post_meta($postId, 'nombreOriginal', $nombreArchivo);
        if ($carpeta) update_post_meta($postId, 'carpetaOriginal', $carpeta);
        if ($carpetaAbuela) update_post_meta($postId, 'carpetaAbuelaOriginal', $carpetaAbuela);

        update_post_meta($postId, 'audio_bpm', $datosAlgoritmo['bpm'] ?? null);
        update_post_meta($postId, 'audio_key', $datosAlgoritmo['key'] ?? null);
        update_post_meta($postId, 'audio_scale', $datosAlgoritmo['scale'] ?? null);
        update_post_meta($postId, 'datosAlgoritmo', json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE));

        return $postId;
    }

    /**
     * Genera datos de IA y Python para el audio
     */
    public function generarDatosAudio($ruta, $nombre, $carpeta, $carpetaAbuela): array|false
    {
        $resultadosPython = $this->pythonService->procesarAudio($ruta);

        $info = "";
        if ($nombre) $info .= "Archivo: '$nombre'\n";
        if ($carpeta) $info .= "Carpeta: '$carpeta'\n";
        if ($carpetaAbuela) $info .= "Carpeta Abuela: '$carpetaAbuela'\n";

        $prompt = "Este audio fue subido automáticamente. Info:\n$info\n" .
            "Determina descripcion JSON. Ignora 'lite', '2upra'. ESTRUCTURA JSON ONLY. " .
            '{"descripcion_ia":{"es":"","en":""},"instrumentos_principal":{"es":[],"en":[]},"nombre_corto":{"es":"","en":""},"descripcion_corta":{"es":"","en":""},"estado_animo":{"es":[],"en":[]},"genero_posible":{"es":[],"en":[]},"artista_posible":{"es":[],"en":[]},"tipo_audio":{"es":"","en":""},"tags_posibles":{"es":[],"en":[]},"sugerencia_busqueda":{"es":[],"en":[]}}';

        $descripcion = $this->iaService->generarDescripcionPro($ruta, $prompt);

        if (!$descripcion) return false;

        $jsonStr = trim($descripcion);
        $jsonStr = trim($jsonStr, "```json");
        $jsonStr = trim($jsonStr, "```");
        $jsonStr = trim($jsonStr);

        $datosIA = json_decode($jsonStr, true);
        if (!$datosIA || !isset($datosIA['descripcion_ia'])) return false;

        $datosFinales = $datosIA;
        if ($resultadosPython) {
            $datosFinales = array_merge($datosFinales, $resultadosPython);
        }

        return $datosFinales;
    }

    /**
     * Adjunta archivo a post
     */
    public function adjuntarArchivo($archivo, $postId, $fileId = null)
    {
        if ($fileId !== null) {
            update_post_meta($postId, 'idHash_audioId', $fileId);
        }

        $tmp = false;
        if (filter_var($archivo, FILTER_VALIDATE_URL)) {
            $contenido = @file_get_contents($archivo);
            if (!$contenido) return new WP_Error('error_descarga', 'Fallo descarga');
            $archivo = tempnam(sys_get_temp_dir(), 'upl');
            file_put_contents($archivo, $contenido);
            $tmp = true;
        }

        if (!file_exists($archivo)) return new WP_Error('no_file', 'Archivo no existe');

        $uploadDir = wp_upload_dir();
        $filename = basename($archivo);
        if ($tmp) $filename .= '.mp3';

        $destino = $uploadDir['path'] . '/' . wp_unique_filename($uploadDir['path'], $filename);
        if (!copy($archivo, $destino)) return new WP_Error('copy_error', 'Error copiando');

        $filetype = wp_check_filetype(basename($destino), null);

        $attachment = [
            'guid'           => $uploadDir['url'] . '/' . basename($destino),
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name(basename($destino)),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        $attachId = wp_insert_attachment($attachment, $destino, $postId);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachData = wp_generate_attachment_metadata($attachId, $destino);
        wp_update_attachment_metadata($attachId, $attachData);

        if ($fileId) {
            $this->hashService->actualizarUrlArchivo($fileId, wp_get_attachment_url($attachId));
        }

        if ($tmp) unlink($archivo);

        return $attachId;
    }
}
