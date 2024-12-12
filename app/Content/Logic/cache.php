<?

// Función para guardar datos en caché en archivos con compresión y serialización
function guardarCache($cache_key, $data, $expiration) {
    $cache_dir = WP_CONTENT_DIR . '/cache/feed/';
    error_log("Intentando guardar caché. Directorio: " . $cache_dir);
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
        error_log("Directorio de caché creado: " . $cache_dir);
    }
    $file_path = $cache_dir . $cache_key . '.cache';
    error_log("Ruta completa del archivo de caché: " . $file_path);

    $data_to_store = [
        'expiration' => time() + $expiration,
        'data' => $data,
    ];
    error_log("Datos a guardar en caché: " . print_r($data_to_store, true));
    $serialized_data = serialize($data_to_store);
    error_log("Datos serializados");
    $compressed_data = gzcompress($serialized_data);
    error_log("Datos comprimidos");
    if (file_put_contents($file_path, $compressed_data)) {
        error_log("Caché guardada exitosamente. Nombre de la caché: " . $cache_key . ".cache");
    } else {
        error_log("Error al guardar la caché. Nombre de la caché: " . $cache_key . ".cache");
    }
}
function obtenerCache($cache_key) {
    $file_path = WP_CONTENT_DIR . '/cache/feed/' . $cache_key . '.cache';
    if (file_exists($file_path)) {
        $compressed_content = file_get_contents($file_path);
        $serialized_data = gzuncompress($compressed_content);
        if ($serialized_data === false) {
            unlink($file_path);
            return false;
        }
        $data = unserialize($serialized_data);
        if ($data['expiration'] > time()) {
            return $data['data'];
        } else {
            unlink($file_path);
        }
    }
    return false;
}


function borrarCache($cache_key) {
    $file_path = WP_CONTENT_DIR . '/cache/feed/' . $cache_key . '.cache';
    
    if (file_exists($file_path)) {
        // Intentar eliminar el archivo, y manejar posibles errores
        if (!unlink($file_path)) {
            error_log("[borrarCache] No se pudo eliminar el archivo de caché: " . $file_path);
        } else {
            guardarLog("Archivo de caché eliminado: " . $file_path);
        }
    } else {
        error_log("[borrarCache] Archivo de caché no encontrado: " . $file_path);
    }
}

function borrarCacheIdeasParaUsuario($user_id)
{
    // Recuperar la lista de claves de caché asociadas al usuario
    $cache_master_key = 'cache_idea_user_' . $user_id;
    $cache_keys = obtenerCache($cache_master_key);

    if ($cache_keys) {
        // Eliminar todas las claves de la caché
        foreach ($cache_keys as $cache_key) {
            borrarCache($cache_key); // Suponiendo que tienes una función eliminarCache
        }
        // Eliminar también la clave maestra
        borrarCache($cache_master_key);
    }
}

function borrarCacheColeccion($colec_id)
{
    // Recuperar la lista de claves de caché asociadas a la colección
    $user_id = get_current_user_id();
    $cache_master_key = 'cache_colec_' . $colec_id;
    $cache_keys = obtenerCache($cache_master_key);

    if ($cache_keys) {
        // Eliminar todas las claves de la caché
        foreach ($cache_keys as $cache_key) {
            borrarCache($cache_key); // Suponiendo que tienes una función eliminarCache
        }
        // Eliminar también la clave maestra
        borrarCache($cache_master_key);
        borrarCacheIdeasParaUsuario($user_id);
    }
}