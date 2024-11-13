<?

// Función para guardar datos en caché en archivos con compresión y serialización
function guardarCache($cache_key, $data, $expiration) {
    $cache_dir = WP_CONTENT_DIR . '/cache/feed/';
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    $file_path = $cache_dir . $cache_key . '.cache';

    $data_to_store = [
        'expiration' => time() + $expiration,
        'data' => $data,
    ];
    // Serializar y comprimir los datos
    $serialized_data = serialize($data_to_store);
    $compressed_data = gzcompress($serialized_data);

    file_put_contents($file_path, $compressed_data);
}

// Función para recuperar datos del caché de archivos
function obtenerCache($cache_key) {
    $file_path = WP_CONTENT_DIR . '/cache/feed/' . $cache_key . '.cache';
    if (file_exists($file_path)) {
        $compressed_content = file_get_contents($file_path);
        $serialized_data = gzuncompress($compressed_content);
        if ($serialized_data === false) {
            // Datos corruptos o inválidos, eliminar el archivo de caché
            unlink($file_path);
            return false;
        }
        $data = unserialize($serialized_data);
        if ($data['expiration'] > time()) {
            return $data['data'];
        } else {
            // El caché ha expirado, eliminar el archivo
            unlink($file_path);
        }
    }
    return false;
}

// Función para eliminar un archivo de caché específico
function borrarCache($cache_key) {
    $file_path = WP_CONTENT_DIR . '/cache/feed/' . $cache_key . '.cache';
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}