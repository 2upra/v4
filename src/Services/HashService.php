<?php

/**
 * Servicio de gestión de hashes de archivos
 * 
 * Maneja la creación, verificación y limpieza de hashes para archivos subidos
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class HashService
{
    private static ?HashService $instancia = null;
    private \Logger $logger;

    /* Constantes de configuración */
    private const HASH_SIMILARITY_THRESHOLD = 0.7;
    private const WRAPPER_SCRIPT_PATH = '/var/www/wordpress/wp-content/themes/2upra3v/app/Commands/process_audio.sh';
    private const PERMISOS_SCRIPT_PATH = '/var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh';

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
     * Inicializa los hooks del servicio
     */
    public static function inicializar(): void
    {
        $instancia = self::obtenerInstancia();

        /* Programar limpieza diaria */
        if (!wp_next_scheduled('limpiar_archivos_pendientes')) {
            wp_schedule_event(time(), 'daily', 'limpiar_archivos_pendientes');
        }
        add_action('limpiar_archivos_pendientes', [$instancia, 'limpiarArchivosPendientes']);
    }

    /**
     * Compara dos hashes para determinar si son similares
     *
     * @param string $hash1 Primer hash
     * @param string $hash2 Segundo hash
     * @param float $umbral Umbral de similitud (0-1)
     * @return bool True si son similares
     */
    public function sonHashesSimilares(string $hash1, string $hash2, float $umbral = self::HASH_SIMILARITY_THRESHOLD): bool
    {
        if (empty($hash1) || empty($hash2)) {
            return false;
        }

        $bin1 = @hex2bin($hash1);
        $bin2 = @hex2bin($hash2);

        if ($bin1 === false || $bin2 === false) {
            return false;
        }

        $similitud = 1 - (count(array_diff_assoc(str_split($bin1), str_split($bin2))) / strlen($bin1));

        return $similitud >= $umbral;
    }

    /**
     * Recalcula el hash de un archivo de audio usando Python
     *
     * @param string $audioFilePath Ruta del archivo de audio
     * @return string|false Hash calculado o false en error
     */
    public function recalcularHash(string $audioFilePath)
    {
        try {
            if (!is_string($audioFilePath) || empty($audioFilePath)) {
                throw new \Exception("Ruta de archivo inválida: " . $audioFilePath);
            }

            if (!file_exists($audioFilePath)) {
                throw new \Exception("Archivo no encontrado: " . $audioFilePath);
            }

            if (!is_readable($audioFilePath)) {
                $this->ejecutarScriptPermisos();
                throw new \Exception("No hay permisos de lectura para el archivo: " . $audioFilePath);
            }

            if (!file_exists(self::WRAPPER_SCRIPT_PATH)) {
                throw new \Exception("Script wrapper no encontrado en: " . self::WRAPPER_SCRIPT_PATH);
            }

            if (!is_executable(self::WRAPPER_SCRIPT_PATH)) {
                throw new \Exception("Script wrapper no tiene permisos de ejecución");
            }

            $command = escapeshellarg(self::WRAPPER_SCRIPT_PATH) . ' ' . escapeshellarg($audioFilePath);

            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (!is_resource($process)) {
                throw new \Exception("No se pudo iniciar el proceso");
            }

            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);

            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            $returnValue = proc_close($process);

            if ($returnValue !== 0) {
                throw new \Exception("Error en el proceso Python: " . $error);
            }

            $hash = trim($output);
            if (!preg_match('/^[a-f0-9]{64}$/', $hash)) {
                throw new \Exception("Hash inválido generado: " . $output);
            }

            return $hash;
        } catch (\Exception $e) {
            $this->logger->error('hash', 'Error recalculando hash', ['error' => $e->getMessage()]);
            $this->ejecutarScriptPermisos();
            return false;
        }
    }

    /**
     * Guarda un hash en la base de datos
     *
     * @param string $hash Hash del archivo
     * @param string $url URL del archivo
     * @param int $userId ID del usuario
     * @param string $status Estado del hash
     * @return int|false ID insertado o false en error
     */
    public function guardarHash(string $hash, string $url, int $userId, string $status = 'pending')
    {
        global $wpdb;

        try {
            $wpdb->insert(
                "{$wpdb->prefix}file_hashes",
                [
                    'file_hash' => $hash,
                    'file_url' => $url,
                    'status' => $status,
                    'user_id' => $userId,
                    'upload_date' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%d', '%s']
            );
            return $wpdb->insert_id;
        } catch (\Exception $e) {
            $registroExistente = $wpdb->get_row($wpdb->prepare(
                "SELECT status FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s",
                $hash
            ), ARRAY_A);

            if ($registroExistente && $registroExistente['status'] === 'loss') {
                $wpdb->delete("{$wpdb->prefix}file_hashes", ['file_hash' => $hash], ['%s']);

                try {
                    $wpdb->insert(
                        "{$wpdb->prefix}file_hashes",
                        [
                            'file_hash' => $hash,
                            'file_url' => $url,
                            'status' => $status,
                            'user_id' => $userId,
                            'upload_date' => current_time('mysql')
                        ],
                        ['%s', '%s', '%s', '%d', '%s']
                    );
                    return $wpdb->insert_id;
                } catch (\Exception $e) {
                    $this->logger->error('hash', 'Error al reintentar guardar hash', ['error' => $e->getMessage()]);
                    return false;
                }
            }
            return false;
        }
    }

    /**
     * Actualiza el estado de un archivo
     *
     * @param int $id ID del registro
     * @param string $estado Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function actualizarEstadoArchivo(int $id, string $estado): bool
    {
        global $wpdb;

        try {
            $actualizado = $wpdb->update(
                "{$wpdb->prefix}file_hashes",
                ['status' => $estado],
                ['id' => $id],
                ['%s'],
                ['%d']
            );

            return $actualizado !== false;
        } catch (\Exception $e) {
            $this->logger->error('hash', 'Error actualizando estado', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Actualiza la URL de un archivo
     *
     * @param int $fileId ID del archivo
     * @param string $newUrl Nueva URL
     * @return bool True si se actualizó correctamente
     */
    public function actualizarUrlArchivo(int $fileId, string $newUrl): bool
    {
        global $wpdb;

        $resultado = $wpdb->update(
            "{$wpdb->prefix}file_hashes",
            ['file_url' => $newUrl],
            ['id' => $fileId],
            ['%s'],
            ['%d']
        );

        return $resultado !== false;
    }

    /**
     * Confirma un hash por ID
     *
     * @param int $fileId ID del archivo
     * @return bool True si se confirmó
     */
    public function confirmarHashId(int $fileId): bool
    {
        global $wpdb;

        $resultado = $wpdb->update(
            "{$wpdb->prefix}file_hashes",
            ['status' => 'confirmed'],
            ['id' => $fileId],
            ['%s'],
            ['%d']
        );

        return $resultado !== false;
    }

    /**
     * Elimina un hash por ID
     *
     * @param int $id ID del registro
     * @return bool True si se eliminó
     */
    public function eliminarHash(int $id): bool
    {
        global $wpdb;
        return (bool) $wpdb->delete("{$wpdb->prefix}file_hashes", ['id' => $id], ['%d']);
    }

    /**
     * Elimina un registro por hash
     *
     * @param string $fileHash Hash del archivo
     * @return bool True si se eliminó
     */
    public function eliminarPorHash(string $fileHash): bool
    {
        global $wpdb;
        return (bool) $wpdb->delete(
            "{$wpdb->prefix}file_hashes",
            ['file_hash' => $fileHash],
            ['%s']
        );
    }

    /**
     * Obtiene el ID de archivo por URL
     *
     * @param string $url URL del archivo
     * @return int|false ID del archivo o false
     */
    public function obtenerFileIdPorUrl(string $url)
    {
        global $wpdb;

        $fileId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}file_hashes WHERE file_url = %s",
                $url
            )
        );

        return $fileId !== null ? (int) $fileId : false;
    }

    /**
     * Limpia archivos pendientes de más de 24 horas
     */
    public function limpiarArchivosPendientes(): void
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'file_hashes';

        $archivosPendientes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tableName WHERE status = 'pending' AND upload_date < %s",
                date('Y-m-d H:i:s', strtotime('-24 hours'))
            ),
            ARRAY_A
        );

        foreach ($archivosPendientes as $archivo) {
            $filePath = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $archivo['file_url']);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $wpdb->delete($tableName, ['id' => $archivo['id']]);
            $this->logger->debug('hash', 'Archivo pendiente eliminado', ['url' => $archivo['file_url']]);
        }
    }

    /**
     * Genera un nombre único para archivos
     */
    public function nombreUnicoFile(string $dir, string $name, string $ext): string
    {
        return basename($name, $ext) . $ext;
    }

    /**
     * Ejecuta el script de permisos
     */
    private function ejecutarScriptPermisos(): void
    {
        if (file_exists(self::PERMISOS_SCRIPT_PATH)) {
            @shell_exec('sudo ' . self::PERMISOS_SCRIPT_PATH . ' 2>&1');
        }
    }
}
