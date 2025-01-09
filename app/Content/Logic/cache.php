<?

function guardarCache($cacheKey, $data, $exp) {
    $cacheDir = WP_CONTENT_DIR . '/cache/feed/';
    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    $ruta = $cacheDir . $cacheKey . '.cache';
    $alm = [
        'exp' => time() + $exp,
        'data' => $data,
    ];
    $sData = serialize($alm);
    $cData = gzcompress($sData);
    if (file_put_contents($ruta, $cData)) {
      guardarLog("guardarCache: Cache creada $cacheKey");
    }
}

function obtenerCache($cacheKey) {
    $ruta = WP_CONTENT_DIR . '/cache/feed/' . $cacheKey . '.cache';
    guardarLog("obtenerCache: Buscando $cacheKey");
    if (file_exists($ruta)) {
        $cData = file_get_contents($ruta);
        $sData = gzuncompress($cData);
        if ($sData === false) {
            unlink($ruta);
            return false;
        }
        $data = unserialize($sData);
        if ($data['exp'] > time()) {
            guardarLog("obtenerCache: Cache encontrada $cacheKey");
            return $data['data'];
        } else {
            unlink($ruta);
        }
    }
    return false;
}

function borrarCache($cacheKey) {
    $ruta = WP_CONTENT_DIR . '/cache/feed/' . $cacheKey . '.cache';
    if (file_exists($ruta)) {
        if (unlink($ruta)) {
            guardarLog("borrarCache: Cache eliminada $cacheKey");
        }
    }
}

function borrarCacheIdeasUsuario($userId)
{
    $cacheMasterKey = 'cache_idea_user_' . $userId;
    $cacheKeys = obtenerCache($cacheMasterKey);
    if ($cacheKeys) {
        foreach ($cacheKeys as $cacheKey) {
            borrarCache($cacheKey);
        }
        borrarCache($cacheMasterKey);
    }
    guardarLog("borrarCacheIdeasUsuario: Eliminando cache de usuario $userId");
}

function borrarCacheColeccion($colecId)
{
    $userId = get_current_user_id();
    $cacheMasterKey = 'cache_colec_' . $colecId;
    $cacheKeys = obtenerCache($cacheMasterKey);
    if ($cacheKeys) {
        foreach ($cacheKeys as $cacheKey) {
            borrarCache($cacheKey);
        }
        borrarCache($cacheMasterKey);
        borrarCacheIdeasUsuario($userId);
    }
    guardarLog("borrarCacheColeccion: Eliminando cache de coleccion $colecId");
}