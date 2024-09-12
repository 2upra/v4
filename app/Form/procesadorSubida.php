<?php

function handle_file_upload()
{
    log_action("INICIO handle_file_upload");

    if (empty($_FILES['file']) || empty($_POST['file_hash'])) {
        log_and_respond_error('No se proporcionó archivo o hash');
        return;
    }

    $file_hash = sanitize_text_field($_POST['file_hash']);
    log_action("Hash recibido: $file_hash");

    $existing_file_url = get_file_url_by_hash($file_hash);

    if ($existing_file_url) {
        handle_existing_file($existing_file_url, $file_hash);
    } else {
        log_action("No se encontró un archivo existente con este hash.");
    }

    $upload_overrides = ['test_form' => false, 'unique_filename_callback' => 'custom_unique_filename'];
    $movefile = wp_handle_upload($_FILES['file'], $upload_overrides);

    if ($movefile && empty($movefile['error'])) {
        save_file_hash($file_hash, $movefile['url']);
        log_and_respond_success(['fileUrl' => $movefile['url']], "Carga exitosa. Hash guardado: $file_hash. URL: {$movefile['url']}");
    } else {
        log_and_respond_error($movefile['error'] ?? 'Error desconocido');
    }

    log_action("FIN handle_file_upload");
}

function handle_existing_file($existing_file_url, $file_hash)
{
    log_action("Archivo existente: $existing_file_url");

    $existing_file_path = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $existing_file_url);

    if (file_exists($existing_file_path)) {
        unlink($existing_file_path);
        log_action("Archivo anterior eliminado: $existing_file_path");
    } else {
        log_action("El archivo no existe físicamente en el servidor.");
    }

    delete_file_hash($file_hash);
    log_action("Registro del hash anterior eliminado.");
}

function custom_unique_filename($dir, $name, $ext)
{
    return basename($name, $ext) . $ext;
}

add_action('wp_ajax_file_upload', 'handle_file_upload');
add_action('wp_ajax_nopriv_file_upload', 'handle_file_upload');

function save_file_hash($hash, $url)
{
    global $wpdb;
    $wpdb->insert("{$wpdb->prefix}file_hashes", [
        'file_hash' => $hash,
        'file_url' => $url,
        'upload_date' => current_time('mysql')
    ], ['%s', '%s', '%s']);
}

function get_file_url_by_hash($file_hash)
{
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT file_url FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s LIMIT 1", $file_hash)) ?: false;
}

function delete_file_hash($file_hash)
{
    global $wpdb;
    return (bool) $wpdb->delete("{$wpdb->prefix}file_hashes", ['file_hash' => $file_hash], ['%s']);
}

function log_action($message)
{
    guardarLog($message);
}

function log_and_respond_error($error)
{
    log_action($error);
    wp_send_json_error($error);
}

function log_and_respond_success($data, $message)
{
    log_action($message);
    wp_send_json_success($data);
}