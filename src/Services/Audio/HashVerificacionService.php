<?php

namespace Kamples\Services\Audio;

/**
 * Servicio de verificacion y consulta de hashes.
 * 
 * Responsabilidad unica: consultas, verificacion y limpieza de hashes.
 *
 * @since 1.0.0
 */
class HashVerificacionService
{
    private static ?HashVerificacionService $instancia = null;
    private \Logger $logger;
    private HashCrudService $crudService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->crudService = HashCrudService::obtenerInstancia();
    }

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

        if (!wp_next_scheduled('limpiar_archivos_pendientes')) {
            wp_schedule_event(time(), 'daily', 'limpiar_archivos_pendientes');
        }
        add_action('limpiar_archivos_pendientes', [$instancia, 'limpiarArchivosPendientes']);
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
     * Obtiene un registro por hash
     *
     * @param string $fileHash Hash del archivo
     * @return array|null Datos del registro o null
     */
    public function obtenerHash(string $fileHash): ?array
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s LIMIT 1",
            $fileHash
        ), ARRAY_A);
    }

    /**
     * Obtiene hashes filtrados por extension
     * 
     * @param array $extensiones Lista de extensiones permitidas
     * @return array Lista de hashes
     */
    public function obtenerHashesFiltrados(array $extensiones): array
    {
        global $wpdb;
        if (empty($extensiones)) {
            return [];
        }

        $extensiones_regex = implode('|', array_map('preg_quote', $extensiones));
        $query = $wpdb->prepare(
            "SELECT file_hash FROM {$wpdb->prefix}file_hashes WHERE file_url REGEXP %s",
            '\.(' . $extensiones_regex . ')$'
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Verifica si una URL de archivo responde correctamente
     *
     * @param string $fileHash Hash del archivo
     * @return bool True si el archivo responde 200-299
     */
    public function verificarCargaArchivoPorHash(string $fileHash): bool
    {
        $archivo = $this->obtenerHash($fileHash);
        if (!$archivo) {
            return false;
        }

        $fileUrl = $archivo['file_url'];

        $ch = curl_init($fileUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            $this->crudService->actualizarEstadoArchivo((int)$archivo['id'], 'loss');
            return false;
        }
    }

    /**
     * Limpia archivos pendientes de mas de 24 horas
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
}
