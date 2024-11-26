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
            'token' => array('required' => true,'type' => 'string'),
            'nonce' => array('required' => true,'type' => 'string'),
        ),
    ));
});

function chequearElectron() {
    if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
        return true;
    } else {
        error_log("Acceso denegado: Header X-Electron-App no presente o incorrecto.");
        return new WP_Error('forbidden', 'Acceso no autorizado', array('status' => 403));
    }
}

/*
Ahora será más inteligente

Necesito que haga algo en particular, necesito que la colección genere carpetas en base a las colecciones de los usuarios
El audio tiene que poder estar descargado es decir, es un requisito que este en la meta de descargas
La informacion de colecciones se guarda asi de esta manera
Si el sample no esta en ninguna coleccion lo guarda en una Carpeta llamada "No coleccionados"
Obviamente el nombre de las coleccioens puede cambiar y los archivos estar presente en varias colecciones
eso se tiene que manejar permitiendo que este en varias colecciones (evitar repetidos en una misma coleccion pero eso ya se maneja en el servidor evitando duplicados pero de igual manera hay que evitarlo)
si el usuario borra un sample de una colección, ya no debería de estar en esa carpeta

function añadirSampleEnColab($collection_id, $sample_id, $user_id)
//Un resumen de como se manejan los samples y colecciones
$samples = get_post_meta($collection_id, 'samples', true);
if (!is_array($samples)) {
    $samples = array();
}
$samples[] = $sample_id;
$updated = update_post_meta($collection_id, 'samples', $samples);
$samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true);
if (!is_array($samplesGuardados)) {
    $samplesGuardados = array();
}
if (!isset($samplesGuardados[$sample_id])) {
    $samplesGuardados[$sample_id] = [];
}
$samplesGuardados[$sample_id][] = $collection_id;
update_user_meta($user_id, 'samplesGuardados', $samplesGuardados);


*/

function obtenerAudiosUsuario(WP_REST_Request $request) {
    $user_id = $request->get_param('user_id');
    $descargas = get_user_meta($user_id, 'descargas', true); // Audios descargados
    $samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true); // Relación de samples a colecciones
    $downloads = [];

    if (is_array($descargas)) {
        foreach ($descargas as $post_id => $count) {
            $attachment_id = get_post_meta($post_id, 'post_audio', true);
            if ($attachment_id && get_post($attachment_id)) {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path) && strpos(mime_content_type($file_path), 'audio/') === 0) {
                    $token = wp_generate_password(20, false);
                    $nonce = wp_create_nonce('download_' . $token);
                    set_transient('sync_token_' . $token, $attachment_id, 300);

                    // Obtener las colecciones a las que pertenece el sample
                    $colecciones = isset($samplesGuardados[$post_id]) ? $samplesGuardados[$post_id] : ['No coleccionados'];

                    // Crear una entrada para cada colección
                    foreach ($colecciones as $collection_id) {
                        $collection_name = ($collection_id !== 'No coleccionados') ? get_the_title($collection_id) : 'No coleccionados';
                        $collection_name = sanitize_title($collection_name); // Sanitizar nombre de carpeta

                        $downloads[] = [
                            'post_id' => $post_id,
                            'collection' => $collection_name,
                            'download_url' => home_url("/wp-json/sync/v1/download/?token=$token&nonce=$nonce"),
                            'audio_filename' => get_the_title($attachment_id) . '.' . pathinfo($file_path, PATHINFO_EXTENSION),
                        ];
                    }
                } else {
                    error_log("Error con el archivo de audio para el post ID: $post_id. Archivo: $file_path");
                }
            }
        }
    }

    return rest_ensure_response($downloads);
}

function descargarAudiosSync(WP_REST_Request $request) {
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

/*
En la app el codigo se ve asi

//sync.js
const fs = require('fs');
const path = require('path');
const axios = require('axios');
const API_BASE = 'https://2upra.com/wp-json';

const downloadFile = async (url, filePath) => {
    try {
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
    } catch (error) {
        console.error(`Error descargando ${url}:`, error);
        throw error;
    }
};

module.exports = {
    syncAudios: async (userId, downloadDir) => {
        try {
            console.log(`Iniciando sincronización para usuario ${userId} en ${downloadDir}`);
            const response = await axios.get(`${API_BASE}/1/v1/syncpre/${userId}`, {
                headers: { 'X-Electron-App': 'true' },
                withCredentials: true
            });
            const audiosToDownload = response.data;
            if (!fs.existsSync(downloadDir)) {
                fs.mkdirSync(downloadDir, { recursive: true });
            }
            for (const audio of audiosToDownload) {
                if (!audio.download_url || typeof audio.download_url !== 'string') {
                    console.warn(`URL de descarga inválida, saltando audio:`, audio);
                    continue;
                }
                const filePath = path.join(downloadDir, audio.audio_filename);
                if (!fs.existsSync(filePath)) {
                    console.log(`Descargando ${audio.download_url} a ${filePath}`);
                    await downloadFile(audio.download_url, filePath);
                } else {
                    console.log(`Archivo ${audio.audio_filename} ya existe.`);
                }
            }
        } catch (error) {
            console.error("Error en syncAudios:", error);
            if (error.response) {
                console.error('Datos de respuesta:', error.response.data);
                console.error('Estado de respuesta:', error.response.status);
            }
            throw error;
        }
    }
};
*/