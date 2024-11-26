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

function verificarCambiosAudios(WP_REST_Request $request)
{
    $user_id = $request->get_param('user_id');
    $last_sync_timestamp = isset($_GET['last_sync']) ? intval($_GET['last_sync']) : 0;
    $descargas = get_user_meta($user_id, 'descargas', true);
    $cambios_detectados = false;

    if (is_array($descargas)) {
        foreach ($descargas as $post_id => $count) {
            $modified_time = strtotime(get_post_modified_time('U', false, $post_id));
            if ($modified_time > $last_sync_timestamp) {
                $cambios_detectados = true;
                break;
            }
        }
    }
    // Añadimos chequeo para samplesGuardados, en caso de que la colección cambie
    $samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true);
    if (is_array($samplesGuardados)) {
        $modified_time_samples = strtotime(get_user_meta($user_id, 'samplesGuardados_modificado', true));
        if ($modified_time_samples > $last_sync_timestamp) {
            $cambios_detectados = true;
        }
    }
    // Guardar el timestamp actual como última modificación de samplesGuardados
    if (isset($_POST['samplesGuardados']) && is_array($_POST['samplesGuardados'])) {
        update_user_meta($user_id, 'samplesGuardados_modificado', time());
    }

    return rest_ensure_response($cambios_detectados);
}

//Esto funciona bien, pero, necesito que cuando el usuario descargue un audio nuevo, se sincronice automaticamente (lo hace por el momento mediante un boton), es una app que se ejecuta en windows, y este es el archivo sync.js, funciona con electron.js, como sería hacer que la sincronización fuera automatica?

/*
const fs = require('fs');
const path = require('path');
const axios = require('axios');
const API_BASE = 'https://2upra.com/wp-json';

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

module.exports = {
    syncAudios: async (userId, downloadDir) => {
        const url = `${API_BASE}/1/v1/syncpre/${userId}`;
        const response = await axios.get(url, {
            headers: { 'X-Electron-App': 'true' },
            withCredentials: true
        });
        const audiosToDownload = response.data;
        if (!audiosToDownload || audiosToDownload.length === 0) return;
        if (!fs.existsSync(downloadDir)) fs.mkdirSync(downloadDir, { recursive: true });
        for (const audio of audiosToDownload) {
            if (!audio.download_url || typeof audio.download_url !== 'string') continue;
            const collectionDir = path.join(downloadDir, audio.collection);
            if (!fs.existsSync(collectionDir)) fs.mkdirSync(collectionDir, { recursive: true });
            const filePath = path.join(collectionDir, audio.audio_filename);
            if (!fs.existsSync(filePath)) await downloadFile(audio.download_url, filePath);
        }
    }
};
*/


function obtenerAudiosUsuario(WP_REST_Request $request) {
    $user_id = $request->get_param('user_id');
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
    }

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
