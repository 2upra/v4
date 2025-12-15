<?php

namespace Kamples\Services\Audio;

/**
 * Servicio CRUD para la tabla de hashes.
 * 
 * Responsabilidad unica: operaciones CRUD en la tabla file_hashes.
 *
 * @since 1.0.0
 */
class HashCrudService
{
    private static ?HashCrudService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
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
     * @return bool True si se actualizo correctamente
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
     * @return bool True si se actualizo correctamente
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
     * @return bool True si se confirmo
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
     * @return bool True si se elimino
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
     * @return bool True si se elimino
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
}
