<?php

function handle_file_upload()
{
    guardarLog("---------------------------------------------");
    guardarLog("INICIO handle_file_upload");

    $file = $_FILES['file'] ?? null;
    $file_hash = sanitize_text_field($_POST['file_hash'] ?? '');

    if (!$file || !$file_hash) {
        guardarLog("No se proporcionó archivo o hash");
        wp_send_json_error('No se proporcionó archivo o hash');
        return;
    }

    guardarLog("Hash recibido: $file_hash");

    $existing_file_url = get_file_url_by_hash($file_hash);

    if ($existing_file_url) {
        $existing_file_path = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $existing_file_url);
        
        if (file_exists($existing_file_path)) {
            unlink($existing_file_path);
            guardarLog("Archivo anterior eliminado: $existing_file_path");
        } else {
            guardarLog("El archivo no existe físicamente en el servidor.");
        }

        delete_file_hash($file_hash);
        guardarLog("Registro del hash anterior eliminado.");
    } else {
        guardarLog("No se encontró un archivo existente con este hash.");
    }

    $movefile = wp_handle_upload($file, array('test_form' => false, 'unique_filename_callback' => 'custom_unique_filename'));
    guardarLog("Resultado de wp_handle_upload: " . print_r($movefile, true));

    if ($movefile && !isset($movefile['error'])) {
        save_file_hash($file_hash, $movefile['url']);
        guardarLog("Carga exitosa. Hash guardado: $file_hash. URL del nuevo archivo: " . $movefile['url']);
        wp_send_json_success(array('fileUrl' => $movefile['url']));
    } else {
        guardarLog("Error en la carga: " . ($movefile['error'] ?? 'Error desconocido'));
        wp_send_json_error($movefile['error'] ?? 'Error desconocido');
    }

    guardarLog("FIN handle_file_upload");
    guardarLog("---------------------------------------------");
}

function custom_unique_filename($dir, $name, $ext)
{
    return basename($name, $ext) . $ext;
}

add_action('wp_ajax_file_upload', 'handle_file_upload');
add_action('wp_ajax_nopriv_file_upload', 'handle_file_upload');

function get_file_url_by_hash($file_hash)
{
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT file_url FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s LIMIT 1",
        $file_hash
    ));
}

function save_file_hash($hash, $url)
{
    global $wpdb;
    $wpdb->insert(
        "{$wpdb->prefix}file_hashes",
        array('file_hash' => $hash, 'file_url' => $url, 'upload_date' => current_time('mysql')),
        array('%s', '%s', '%s')
    );
}

function delete_file_hash($file_hash)
{
    global $wpdb;
    return (bool) $wpdb->delete("{$wpdb->prefix}file_hashes", array('file_hash' => $file_hash), array('%s'));
}
