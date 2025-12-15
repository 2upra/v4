<?php

namespace Kamples\Services;

use WP_Error;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use DirectoryIterator;
use Exception;

/**
 * Servicio para gestión de posts automáticos y procesamiento de audio.
 * 
 * @package Kamples\Services
 * @since 1.0.0
 */
class AutoPostService
{
    private static ?AutoPostService $instancia = null;
    private ?\Logger $logger;
    private ?HashService $hashService;
    private ?PythonService $pythonService;
    private ?IAService $iaService;

    // Directorios
    private const DIR_VERIFICAR = '/home/asley01/MEGA/Waw/Verificar/';
    private const DIR_KITS = '/home/asley01/MEGA/Waw/Kits/'; // Directorio de audios a escanear
    private const LOCK_FILE = '/tmp/procesar_audios.lock';

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->hashService = HashService::obtenerInstancia();
        $this->pythonService = new PythonService();
        $this->iaService = new IAService();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Procesa un archivo de audio para crear un post automático
     * Migrado de autProcesarAudio en automaticPost.php
     * 
     * @param string $rutaOriginal Ruta absoluta del archivo original
     */
    public function procesarAudio(string $rutaOriginal): void
    {
        $this->logger->info('automatico', "Iniciando procesamiento de audio: {$rutaOriginal}");

        $fileId = $this->hashService->obtenerFileIdPorUrl($rutaOriginal);

        if (!file_exists($rutaOriginal)) {
            $this->logger->error('automatico', "Archivo original no encontrado: {$rutaOriginal}");
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return;
        }

        $fileSizeMB = filesize($rutaOriginal) / 1048576;
        if ($fileSizeMB < 0.01) {
            $motivo = "Archivo demasiado pequeño (< 0.01 MB)";
            $this->logger->warning('automatico', "$motivo: $rutaOriginal");
            $this->manejarArchivoFallido($rutaOriginal, $motivo);
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return;
        }

        $pathParts = pathinfo($rutaOriginal);
        $directory = realpath($pathParts['dirname']);
        if (!$directory) {
            $motivo = "Directorio inválido: {$pathParts['dirname']}";
            $this->logger->error('automatico', $motivo);
            $this->manejarArchivoFallido($rutaOriginal, $motivo);
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return;
        }

        $extension = strtolower($pathParts['extension']);
        $basename = $pathParts['filename'];
        $tempPath = "$directory/{$basename}_temp.$extension";

        // Stripping metadata
        $cmdStrip = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginal) . " -map_metadata -1 -map 0:a -c:a copy " . escapeshellarg($tempPath) . " -y";
        exec($cmdStrip, $outputStrip, $returnStrip);

        if ($returnStrip !== 0) {
            $motivo = "Error al eliminar metadatos: " . implode(" | ", $outputStrip);
            $this->logger->error('automatico', $motivo);
            if ($fileId) $this->hashService->eliminarHash($fileId);
            $this->manejarArchivoFallido($rutaOriginal, $motivo);
            return;
        }

        if (!rename($tempPath, $rutaOriginal)) {
            $motivo = "Error al reemplazar archivo original con versión sin metadata";
            $this->logger->error('automatico', $motivo);
            if (!copy($tempPath, $rutaOriginal)) {
                if ($fileId) $this->hashService->eliminarHash($fileId);
                $this->manejarArchivoFallido($tempPath, "Fallo critico al mover, original posiblemente perdido o bloqueado. " . $motivo);
                return;
            }
            unlink($tempPath);
        }

        // Crear versión lite
        $rutaWpLiteDos = "$directory/{$basename}_lite.mp3";
        $cmdLite = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginal) . " -b:a 128k " . escapeshellarg($rutaWpLiteDos) . " -y";
        exec($cmdLite, $outputLite, $returnLite);

        if ($returnLite !== 0 || !file_exists($rutaWpLiteDos)) {
            $motivo = "Error al crear versión lite";
            if ($fileId) $this->hashService->eliminarHash($fileId);
            $this->logger->error('automatico', $motivo);
            $this->manejarArchivoFallido($rutaOriginal, $motivo);
            return;
        }

        $uploadsDir = wp_upload_dir();
        $targetDirAudio = trailingslashit($uploadsDir['basedir']) . "audio/";

        if (!file_exists($targetDirAudio) && !wp_mkdir_p($targetDirAudio)) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            $this->manejarArchivoFallido($rutaOriginal, "No se pudo crear directorio audio/");
            return;
        }

        // Renombrar/Copiar lite a destino final
        $rutaWpLiteOne = $targetDirAudio . "{$basename}_lite.mp3";
        if (!copy($rutaWpLiteDos, $rutaWpLiteOne)) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            $this->manejarArchivoFallido($rutaOriginal, "Error al copiar lite a uploads/audio/");
            unlink($rutaWpLiteDos);
            return;
        }
        unlink($rutaWpLiteDos);
        chmod($rutaWpLiteOne, 0644);

        $this->logger->info('automatico', "Procesamiento de audio completado. Creando post...");

        $this->crearAutPost($rutaOriginal, $rutaWpLiteOne, $fileId);
    }

    /**
     * Mueve un archivo que falló a un directorio de verificación
     */
    private function manejarArchivoFallido(string $rutaArchivo, string $motivo): void
    {
        if (!file_exists(self::DIR_VERIFICAR)) {
            mkdir(self::DIR_VERIFICAR, 0777, true);
        }

        $nombre = basename($rutaArchivo);
        $destino = self::DIR_VERIFICAR . $nombre;

        if (rename($rutaArchivo, $destino)) {
            file_put_contents($destino . ".txt", "Fallo: $nombre\nMotivo: $motivo");
        } else {
            $this->logger->error('automatico', "Error al mover archivo fallido a verificación: $rutaArchivo");
        }
    }

    /**
     * Crea el post automático con los datos del audio
     */
    public function crearAutPost(?string $rutaOriginal, ?string $rutaWpLite, $fileId = null, $autorId = 44, $postOriginal = null)
    {
        if (!$autorId) $autorId = 44;

        if (empty($rutaWpLite) || !file_exists($rutaWpLite)) {
            return;
        }

        $carpeta = !empty($rutaOriginal) ? basename(dirname($rutaOriginal)) : null;
        $carpetaAbuela = !empty($rutaOriginal) ? basename(dirname(dirname($rutaOriginal))) : null;
        $nombreArchivo = !empty($rutaOriginal) ? pathinfo($rutaOriginal, PATHINFO_FILENAME) : null;

        $datosAlgoritmo = $this->generarDatosAudio($rutaWpLite, $nombreArchivo, $carpeta, $carpetaAbuela);

        if (!$datosAlgoritmo) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return;
        }

        $nombreGenerado = $datosAlgoritmo['nombre_corto']['en'] ?? ($datosAlgoritmo['nombre_corto']['es'] ?? '');
        if (is_array($nombreGenerado)) $nombreGenerado = $nombreGenerado[0] ?? '';

        if (!$nombreGenerado) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return;
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
                return;
            }
        }

        $extLite = pathinfo($rutaWpLite, PATHINFO_EXTENSION);
        $nuevoLite = dirname($rutaWpLite) . '/' . $nombreFinal . '_lite.' . $extLite;
        if (file_exists($nuevoLite)) @unlink($nuevoLite);

        if (!rename($rutaWpLite, $nuevoLite)) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return;
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
            // Fix: No need to delete an error
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return;
        }

        if ($nuevaRutaOriginal) update_post_meta($postId, 'rutaOriginal', $nuevaRutaOriginal);
        update_post_meta($postId, 'rutaLiteOriginal', $nuevoLite);
        update_post_meta($postId, 'postAut', true);

        $audioOriginalId = null;
        if ($nuevaRutaOriginal) {
            $audioOriginalId = $this->adjuntarArchivoAut($nuevaRutaOriginal, $postId, $fileId);
            if (is_wp_error($audioOriginalId)) {
                wp_delete_post($postId, true);
                if ($fileId) $this->hashService->eliminarHash($fileId);
                return $audioOriginalId;
            }
            if (file_exists($nuevaRutaOriginal)) {
                unlink($nuevaRutaOriginal);
            }
        }

        $audioLiteId = $this->adjuntarArchivoAut($nuevoLite, $postId);
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
     * Genera datos de IA y Python para el audio (equiv a automaticAudio)
     */
    private function generarDatosAudio($ruta, $nombre, $carpeta, $carpetaAbuela): array|false
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
    public function adjuntarArchivoAut($archivo, $postId, $fileId = null)
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

    /**
     * Ejecuta el escaneo de audios (Cron Job)
     */
    public function procesarAudiosScan()
    {
        if (defined('LOCAL') && LOCAL === true) {
            return;
        }

        $fp = fopen(self::LOCK_FILE, 'c');
        if (!$fp) return;

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return;
        }

        try {
            $audioInfo = $this->buscarUnAudioValido(self::DIR_KITS);
            if ($audioInfo) {
                $hashId = $this->hashService->guardarHash($audioInfo['hash'], $audioInfo['ruta'], 44, 'confirmed');
                if ($hashId) {
                    $this->procesarAudio($audioInfo['ruta']);
                }
            }
        } catch (Exception $e) {
            $this->logger->error('automatico', "Error scan: " . $e->getMessage());
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
            @unlink(self::LOCK_FILE);
        }
    }

    private function buscarUnAudioValido(string $directorio, int $intentos = 0): ?array
    {
        $maxIntentos = 100;
        $carpetaProtegida = self::DIR_KITS;

        if ($intentos >= $maxIntentos) {
            $this->logger->error('automatico', "Max intentos alcanzados en buscarUnAudioValido");
            return null;
        }

        if (!is_dir($directorio) || !is_readable($directorio)) {
            @shell_exec('sudo /var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh 2>&1');
            return null;
        }

        try {
            $subcarpetas = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $subcarpetas[] = $item->getPathname();
                }
            }
            if (empty($subcarpetas)) {
                $subcarpetas[] = $directorio;
            }

            $carpetaSeleccionada = $subcarpetas[array_rand($subcarpetas)];

            $archivos = [];
            $dirIterator = new DirectoryIterator($carpetaSeleccionada);
            foreach ($dirIterator as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, ['wav', 'mp3'])) {
                        $archivos[] = $file->getPathname();
                    } else {
                        @unlink($file->getPathname());
                    }
                }
            }

            foreach ($subcarpetas as $sub) {
                if ($sub !== $carpetaProtegida) {
                    $fi = new FilesystemIterator($sub);
                    if (!$fi->valid()) {
                        @rmdir($sub);
                    }
                }
            }

            if (empty($archivos)) {
                return $this->buscarUnAudioValido($directorio, $intentos + 1);
            }

            $archivoSeleccionado = $archivos[array_rand($archivos)];
            $hash = $this->hashService->recalcularHash($archivoSeleccionado);

            if (!$hash) {
                @unlink($archivoSeleccionado);
                return $this->buscarUnAudioValido($directorio, $intentos + 1);
            }

            if ($this->debeProcesarse($archivoSeleccionado, $hash)) {
                return ['ruta' => $archivoSeleccionado, 'hash' => $hash];
            } else {
                return $this->buscarUnAudioValido($directorio, $intentos + 1);
            }
        } catch (Exception $e) {
            $this->logger->error('automatico', "Error en buscarUnAudioValido: " . $e->getMessage());
            return null;
        }
    }

    private function debeProcesarse(string $rutaArchivo, string $fileHash): bool
    {
        $hashesExistentes = $this->hashService->obtenerHashesFiltrados(['wav', 'mp3']);
        $hashVerificado = $this->hashService->verificarCargaArchivoPorHash($fileHash);

        foreach ($hashesExistentes as $h) {
            if ($this->hashService->sonHashesSimilaresEuclidean($fileHash, $h['file_hash'])) {
                if ($hashVerificado && file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                    $this->hashService->eliminarPorHash($fileHash);
                } else {
                    $this->manejarArchivoFallido($rutaArchivo, "Hash similar encontrado y fallo verificación.");
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Procesa un post con múltiples audios y genera nuevos posts para cada uno.
     * Migrado de multiple.php
     */
    public function procesarMultiples(int $postIdOriginal): void
    {
        if (!$postIdOriginal) return;

        $post = get_post($postIdOriginal);
        if (!$post || $post->post_type !== 'social_post') return;

        $isMultiple = get_post_meta($postIdOriginal, 'multiple', true);
        if ($isMultiple !== '1') return;

        $authorId = $post->post_author;

        // Copiar metas
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

                    // Crear nuevo post
                    $nuevoPostId = $this->crearAutPost('', $rutaServidor, $audioIdHash, $authorId, $postIdOriginal);

                    if (!is_wp_error($nuevoPostId) && $nuevoPostId) {
                        $idsNuevosPosts[] = $nuevoPostId;

                        if (!empty($imagenDestacadaId)) {
                            set_post_thumbnail($nuevoPostId, $imagenDestacadaId);
                        }

                        // Actualizar metas en nuevo post
                        foreach ($metaValues as $key => $val) {
                            if (!empty($val)) update_post_meta($nuevoPostId, $key, $val);
                        }

                        // Metas de la rola especifica
                        $precio = get_post_meta($postIdOriginal, 'precioRola' . $i, true);
                        if ($precio) update_post_meta($nuevoPostId, 'precioRola', $precio);

                        $name = get_post_meta($postIdOriginal, 'nombreRola' . $i, true);
                        if ($name) update_post_meta($nuevoPostId, 'nombreRola', $name);

                        $audioUrl = get_post_meta($postIdOriginal, 'audioUrl' . $i, true);
                        if ($audioUrl) update_post_meta($nuevoPostId, 'audioUrl', $audioUrl);

                        $duration = get_post_meta($postIdOriginal, 'audio_duration_' . $i, true);
                        if ($duration) update_post_meta($nuevoPostId, 'audio_duration_1', $duration);

                        update_post_meta($nuevoPostId, 'post_audio', $audioId);

                        // Limpiar original
                        delete_post_meta($postIdOriginal, 'post_audio_lite_' . $i);
                        delete_post_meta($postIdOriginal, 'post_audio' . $i);
                        delete_post_meta($postIdOriginal, 'idHash_audioId' . $i);
                        delete_post_meta($postIdOriginal, 'precioRola' . $i);
                        delete_post_meta($postIdOriginal, 'nombreRola' . $i);
                        delete_post_meta($postIdOriginal, 'audioUrl' . $i);
                        delete_post_meta($postIdOriginal, 'audio_duration_' . $i);

                        sleep(2);
                    }
                }
            }
        }

        if (!$multiplesEncontrados) {
            delete_post_meta($postIdOriginal, 'multiple');
        } else {
            if (!empty($idsNuevosPosts)) {
                update_post_meta($postIdOriginal, 'posts_generados', $idsNuevosPosts);
            }

            // Verificar si quedan
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
