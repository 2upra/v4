<?

add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/syncpre/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'obtenerAudiosUsuario',
        'permission_callback' => 'chequearElectron',
    ));
    register_rest_route('sync/v1', '/download/', array(
        'methods' => 'GET',
        'callback' => 'descargarAudiosSync',
        'args' => array(
            'token' => array('required' => true, 'type' => 'string'),
            'nonce' => array('required' => true, 'type' => 'string'),
        ),
    ));
    register_rest_route('1/v1', '/syncpre/(?P<user_id>\d+)/check', array(
        'methods'  => 'GET',
        'callback' => 'verificarCambiosAudios',
        'permission_callback' => 'chequearElectron',
    ));
});

function chequearElectron()
{
    if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
        return true;
    } else {
        error_log("Acceso denegado: Header X-Electron-App no presente o incorrecto.");
        return new WP_Error('forbidden', 'Acceso no autorizado', array('status' => 403));
    }
}

/*
NO ENTIENDO PORQUE DEVUELVE ESTO
Verificando cambios para usuario: 44 y directorio: C:\Users\1u\Documents\Audios con timestamp: 0
Respuesta inesperada del servidor: false
sync.js se ve asi: 


const fs = require('fs');
const path = require('path');
const axios = require('axios');
const API_BASE = 'https://2upra.com/wp-json';

let Store; 
let store;


(async () => {
    try {
        Store = (await import('electron-store')).default;
        store = new Store(); // Crea una instancia de electron-store después de importarla
        console.log('Electron-store cargado correctamente.');

        // Iniciar la sincronización solo después de que electron-store esté listo
        if(store.get('userId') && store.get('downloadDir')) {
            startSyncing(store.get('userId'), store.get('downloadDir'));
        } else {
            console.log('Faltan configuraciones de usuario o directorio en electron-store.');
        }
      
    } catch (error) {
        console.error('Error al importar electron-store:', error);
        // Manejar el error, posiblemente deshabilitar la funcionalidad que depende de electron-store
    }
})();

let lastSyncTimestamp = 0;
let syncInterval; // Variable para almacenar el intervalo de sincronización
const DOWNLOAD_INTERVAL_MS = 10000; // Intervalo de 10 segundos para verificar cambios

const downloadFile = async (url, filePath) => {
    const response = await axios({
        method: 'get',
        url: url,
        responseType: 'stream',
        headers: { 'X-Electron-App': 'true' },
    });
    return new Promise((resolve, reject) => {
        response.data.pipe(fs.createWriteStream(filePath))
            .on('finish', resolve)
            .on('error', reject);
    });
};

const syncSingleAudio = async (userId, postId, downloadDir) => {
    const url = `${API_BASE}/1/v1/syncpre/${userId}?post_id=${postId}`;
    try {
        const response = await axios.get(url, {
            headers: { 'X-Electron-App': 'true' },
            withCredentials: true
        });
        const audioToDownload = response.data[0]; // Esperamos un solo audio
        console.log('Respuesta de sincronización individual:', audioToDownload);

        if (!audioToDownload || !audioToDownload.download_url) {
            console.log('No se encontró URL de descarga o no hay audio disponible.');
            return;
        }

        const collectionDir = path.join(downloadDir, audioToDownload.collection);
        if (!fs.existsSync(collectionDir)) fs.mkdirSync(collectionDir, { recursive: true });
        const filePath = path.join(collectionDir, audioToDownload.audio_filename);

        await downloadFile(audioToDownload.download_url, filePath);
        console.log(`Audio ${audioToDownload.audio_filename} sincronizado correctamente.`);

    } catch (error) {
        console.error('Error al sincronizar audio individual:', error);
    }
};

const syncAudios = async (userId, downloadDir) => {
    const url = `${API_BASE}/1/v1/syncpre/${userId}`;
    try {
        const response = await axios.get(url, {
            headers: { 'X-Electron-App': 'true' },
            withCredentials: true
        });
        const audiosToDownload = response.data;
        console.log('Audios para sincronizar:', audiosToDownload);

        if (!audiosToDownload || audiosToDownload.length === 0) {
            console.log('No hay audios para sincronizar.');
            return;
        }

        if (!fs.existsSync(downloadDir)) fs.mkdirSync(downloadDir, { recursive: true });

        for (const audio of audiosToDownload) {
            if (!audio.download_url || typeof audio.download_url !== 'string') continue;

            const collectionDir = path.join(downloadDir, audio.collection);
            if (!fs.existsSync(collectionDir)) fs.mkdirSync(collectionDir, { recursive: true });

            const filePath = path.join(collectionDir, audio.audio_filename);

            if (!fs.existsSync(filePath)) {
                await downloadFile(audio.download_url, filePath);
                console.log(`Audio ${audio.audio_filename} sincronizado correctamente.`);
            } else {
                console.log(`El archivo ${audio.audio_filename} ya existe. No se descarga nuevamente.`);
            }
        }
        lastSyncTimestamp = Math.floor(Date.now() / 1000);
        store?.set('lastSyncTimestamp', lastSyncTimestamp);

    } catch (error) {
        console.error('Error al sincronizar audios:', error);
    }
};

const checkForChangesAndSync = async (userId, downloadDir) => {
    console.log('Verificando cambios para usuario:', userId, 'y directorio:', downloadDir, 'con timestamp:', lastSyncTimestamp);
    try {
        lastSyncTimestamp = store?.get('lastSyncTimestamp') || 0;

        const checkUrl = `${API_BASE}/1/v1/syncpre/${userId}/check?last_sync=${lastSyncTimestamp}`;
        const response = await axios.get(checkUrl, {
            headers: { 'X-Electron-App': 'true' },
            withCredentials: true
        });

        // Verificar la estructura de la respuesta del servidor
        if (response.data && typeof response.data === 'object' && 
            response.data.hasOwnProperty('descargas_modificado') &&
            response.data.hasOwnProperty('samplesGuardados_modificado')) {

            const descargasModificado = parseInt(response.data.descargas_modificado);
            const samplesModificado = parseInt(response.data.samplesGuardados_modificado);

            if (descargasModificado > lastSyncTimestamp || samplesModificado > lastSyncTimestamp) {
                console.log('Cambios detectados, sincronizando...');
                await syncAudios(userId, downloadDir);
                lastSyncTimestamp = Math.max(descargasModificado, samplesModificado); // Actualizar lastSyncTimestamp
                store?.set('lastSyncTimestamp', lastSyncTimestamp); // Guardar el nuevo timestamp
            } else {
                console.log('Sin cambios detectados.');
            }
        } else {
            console.error('Respuesta inesperada del servidor:', response.data);
        }

    } catch (error) {
        console.error('Error al verificar cambios o sincronizar:', error);
    }
};

const startSyncing = (userId, downloadDir) => {
    console.log('Iniciando sincronización para usuario:', userId, 'y directorio:', downloadDir); // Log
    if (syncInterval) clearInterval(syncInterval);
    syncInterval = setInterval(() => checkForChangesAndSync(userId, downloadDir), DOWNLOAD_INTERVAL_MS);
    checkForChangesAndSync(userId, downloadDir);
    console.log('Sincronización automática iniciada.');
    store.set('userId', userId);
    store.set('downloadDir', downloadDir);
    console.log('Valores guardados en electron-store:', store.get('userId'), store.get('downloadDir')); // Log
};

const stopSyncing = () => {
    if (syncInterval) {
        clearInterval(syncInterval);
        syncInterval = null;
        console.log('Sincronización automática detenida.');
    }
};

module.exports = {
    syncAudios,
    startSyncing,
    stopSyncing,
    syncSingleAudio
};
y en php: 
*/



function verificarCambiosAudios(WP_REST_Request $request) {
    $user_id = $request->get_param('user_id');
    $last_sync_timestamp = isset($_GET['last_sync']) ? intval($_GET['last_sync']) : 0;

    error_log("verificarCambiosAudios: User ID: $user_id, Last Sync Timestamp: $last_sync_timestamp");

    $descargas_timestamp = get_user_meta($user_id, 'descargas_modificado', true);
    $samples_timestamp = get_user_meta($user_id, 'samplesGuardados_modificado', true);

    // Verificar si los metadatos se obtuvieron correctamente y convertirlos a enteros
    $descargas_timestamp = ($descargas_timestamp !== '' && $descargas_timestamp !== false) ? intval($descargas_timestamp) : 0;
    $samples_timestamp = ($samples_timestamp !== '' && $samples_timestamp !== false) ? intval($samples_timestamp) : 0;

    error_log("verificarCambiosAudios: Descargas Timestamp: $descargas_timestamp, Samples Timestamp: $samples_timestamp");

    $response_data = [
        'descargas_modificado' => $descargas_timestamp,
        'samplesGuardados_modificado' => $samples_timestamp
    ];

    // Asegurarse de que la respuesta sea un JSON válido
    return rest_ensure_response($response_data);
}

function actualizarTimestampDescargas($user_id) {
    $time = time();
    update_user_meta($user_id, 'descargas_modificado', $time);
    error_log("actualizarTimestampDescargas: User ID: $user_id, Timestamp actualizado a: $time"); // Nuevo log
}

add_action('nueva_descarga_realizada', 'actualizarTimestampDescargas', 10, 2); 

function actualizarTimestampSamplesGuardados($user_id) {
    update_user_meta($user_id, 'samplesGuardados_modificado', time());
}
add_action('samples_guardados_actualizados', 'actualizarTimestampSamplesGuardados', 10, 2); 

function obtenerAudiosUsuario(WP_REST_Request $request) {
    $user_id = $request->get_param('user_id');
    error_log("obtenerAudiosUsuario: User ID: $user_id, Post ID (opcional): " . ($post_id ?? 'null')); // Log al inicio
    $post_id = $request->get_param('post_id'); // Nuevo parámetro opcional
    $descargas = get_user_meta($user_id, 'descargas', true);
    $samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true);
    $downloads = [];

    if (is_array($descargas)) {
        foreach ($descargas as $current_post_id => $count) {
            // Si se proporciona post_id, solo procesar ese post
            if ($post_id !== null && $current_post_id != $post_id) continue;

            $attachment_id = get_post_meta($current_post_id, 'post_audio', true);
            if ($attachment_id && get_post($attachment_id)) {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path) && strpos(mime_content_type($file_path), 'audio/') === 0) {
                    $token = wp_generate_password(20, false);
                    $nonce = wp_create_nonce('download_' . $token);
                    set_transient('sync_token_' . $token, $attachment_id, 300);
                    $colecciones = isset($samplesGuardados[$current_post_id]) ? $samplesGuardados[$current_post_id] : ['No coleccionados'];
                    foreach ($colecciones as $collection_id) {
                        $collection_name = ($collection_id !== 'No coleccionados') ? get_the_title($collection_id) : 'No coleccionados';
                        $collection_name = sanitize_title($collection_name);

                        $downloads[] = [
                            'post_id' => $current_post_id,
                            'collection' => $collection_name,
                            'download_url' => home_url("/wp-json/sync/v1/download/?token=$token&nonce=$nonce"),
                            'audio_filename' => get_the_title($attachment_id) . '.' . pathinfo($file_path, PATHINFO_EXTENSION),
                        ];
                    }
                } else {
                    error_log("Error con el archivo de audio para el post ID: $current_post_id. Archivo: $file_path");
                }
            }
        }
    } else {
        error_log("obtenerAudiosUsuario: El metadato 'descargas' no es un array o no está definido para el usuario $user_id"); // Nuevo log
    }
    error_log("obtenerAudiosUsuario: Se encontraron " . count($downloads) . " audios para el usuario $user_id"); // Log al final

    return rest_ensure_response($downloads);
}

function descargarAudiosSync(WP_REST_Request $request)
{
    $token = $request->get_param('token');
    $nonce = $request->get_param('nonce');
    if (!wp_verify_nonce($nonce, 'download_' . $token)) {
        error_log("Intento de descarga con nonce inválido. Token: $token, Nonce: $nonce");
        return new WP_Error('invalid_nonce', 'Nonce inválido.', array('status' => 403));
    }
    $attachment_id = get_transient('sync_token_' . $token);
    if ($attachment_id) {
        delete_transient('sync_token_' . $token);
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $mime_type = mime_content_type($file_path);
            if (strpos($mime_type, 'audio/') !== 0) {
                error_log("Intento de acceso a archivo no de audio. Ruta: $file_path");
                return new WP_Error('invalid_file_type', 'Tipo de archivo inválido.', array('status' => 400));
            }
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: no-cache');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            error_log("Archivo no encontrado en la ruta: $file_path. Token: $token");
            return new WP_Error('file_not_found', 'Archivo no encontrado.', array('status' => 404));
        }
    } else {
        error_log("Intento de descarga con token inválido o expirado: $token");
        return new WP_Error('invalid_token', 'Token inválido o expirado.', array('status' => 403));
    }
}
