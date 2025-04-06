<?php
// Refactor(Org): Moved function guardarHash from HashUtils.php

function guardarHash($hash, $url, $user_id, $status = 'pending')
{
    global $wpdb;

    try {
        $wpdb->insert(
            "{$wpdb->prefix}file_hashes",
            array(
                'file_hash' => $hash,
                'file_url' => $url,
                'status' => $status,
                'user_id' => $user_id,
                'upload_date' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
        return $wpdb->insert_id;
    } catch (Exception $e) {
        // Obtener el registro existente para verificar su estado
        $registro_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s",
            $hash
        ), ARRAY_A);

        // Si el estado es 'loss', eliminar el registro
        if ($registro_existente && $registro_existente['status'] === 'loss') {
            $wpdb->delete("{$wpdb->prefix}file_hashes", array('file_hash' => $hash), array('%s'));
        } else {
            //////guardarLog("Error: el hash existe y no está en estado 'loss'.");
            return false;
        }

        // Reintentar la inserción después de borrar el registro en estado 'loss'
        try {
            $wpdb->insert(
                "{$wpdb->prefix}file_hashes",
                array(
                    'file_hash' => $hash,
                    'file_url' => $url,
                    'status' => $status,
                    'user_id' => $user_id,
                    'upload_date' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%d', '%s')
            );
            return $wpdb->insert_id;
        } catch (Exception $e) {
            //////guardarLog("Error al intentar guardar el hash nuevamente: " . $e->getMessage());
            return false;
        }
    }
}

// Refactor(Org): Moved function actualizarEstadoArchivo from app/Utils/HashUtils.php
function actualizarEstadoArchivo($id, $estado)
{
    global $wpdb;

    try {
        ////guardarLog("Intentando actualizar estado del archivo ID: {$id} a {$estado}");
        $actualizado = $wpdb->update(
            "{$wpdb->prefix}file_hashes",
            ['status' => $estado],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        if ($actualizado === false) {
            throw new Exception("Error al actualizar estado para ID: " . $id);
        }

        ////guardarLog("Estado actualizado para ID {$id}: {$estado}");
        return true;
    } catch (Exception $e) {
        ////guardarLog("Error en actualizarEstadoArchivo: " . $e->getMessage());
        return false;
    }
}

// Refactor(Org): Moved function confirmarHashId from app/Utils/HashUtils.php
function confirmarHashId($file_id)
{
    global $wpdb;
    return $wpdb->update(
        "{$wpdb->prefix}file_hashes",
        array('status' => 'confirmed'),
        array('id' => $file_id),
        array('%s'),
        array('%d')
    );
}

// Refactor(Org): Moved function obtenerHash from app/Auto/busquedaAudio.php
function obtenerHash($file_hash)
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s LIMIT 1",
        $file_hash
    ), ARRAY_A);
}

// Refactor(Org): Moved function handle_recalcular_hash() and its hook from app/Utils/HashUtils.php
function handle_recalcular_hash()
{
    try {
        if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'No se pudo subir el archivo o está corrupto.']);
        }
        $audio_file = $_FILES['audio_file'];
        $allowed_mime_types = ['audio/mpeg', 'audio/wav'];
        if (!in_array($audio_file['type'], $allowed_mime_types)) {
            wp_send_json_error(['message' => 'Tipo de archivo no permitido.']);
        }
        $upload_dir = wp_upload_dir();
        $temp_file_path = $upload_dir['path'] . '/' . basename($audio_file['name']);
        if (!move_uploaded_file($audio_file['tmp_name'], $temp_file_path)) {
            wp_send_json_error(['message' => 'Error al mover el archivo subido.']);
        }
        // Assuming recalcularHash is available globally or included/required
        $hash = recalcularHash($temp_file_path);
        if ($hash === false) {
            wp_send_json_error(['message' => 'Error al generar el hash del archivo.']);
        }
        unlink($temp_file_path);
        wp_send_json_success(['hash' => $hash]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_recalcularHash', 'handle_recalcular_hash');

?>