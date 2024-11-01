<?

// Función para agregar pinkys al usuario
function agregarPinkys($usuario_id, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($usuario_id, 'pinky', true);
    $nuevas_monedas = $monedas_actuales + $cantidad;
    update_user_meta($usuario_id, 'pinky', $nuevas_monedas);
}

// Función para restar pinkys al usuario
function restarPinkys($usuario_id, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($usuario_id, 'pinky', true);
    $nuevas_monedas = $monedas_actuales - $cantidad;
    update_user_meta($usuario_id, 'pinky', $nuevas_monedas);
}

// Acción para restar pinkys al eliminar un post
add_action('before_delete_post', 'restarPinkysEliminacion');
function restarPinkysEliminacion($post_id)
{
    $post = get_post($post_id);
    $usuario_id = $post->post_author;

    if ($usuario_id) {
        restarPinkys($usuario_id, 1);
    }
}

/*
Hay un problema grave, cuando descargo el audio desde el enlace con el token generado no funciona
por lo general son audios wav y dan este error
File: C:\Users\1u\Downloads\Rhodes-Dm_rdmS_2upra (5).wav
Code: -1 (FFFFFFFF)
Message: Decoder was not found for this format.

pero si lo descargo directamente desde el enlace funciona correctamente el archiv

2024-11-01 17:43:45 - Audio ID obtenido: 264969
2024-11-01 17:43:45 - Pinkys del usuario: 9223372036854775803
2024-11-01 17:43:45 - Restando 1 Pinky al usuario: 1
2024-11-01 17:43:45 - Token generado: f2fffe5dd275ef597333472c5e55bc85
2024-11-01 17:43:45 - Datos del token: Array
(
    [user_id] => 1
    [audio_id] => 264969
    [time] => 1730483025
)
2024-11-01 17:43:45 - Token almacenado en transients.
2024-11-01 17:43:45 - Enlace de descarga final: https://2upra.com?descarga_token=f2fffe5dd275ef597333472c5e55bc85
2024-11-01 17:43:45 - Enlace de descarga generado: https://2upra.com?descarga_token=f2fffe5dd275ef597333472c5e55bc85
2024-11-01 17:43:48 - Token de descarga recibido: f2fffe5dd275ef597333472c5e55bc85
2024-11-01 17:43:48 - Datos del token recuperados: Array
(
    [user_id] => 1
    [audio_id] => 264969
    [time] => 1730483025
)
2024-11-01 17:43:48 - Usuario actual: 1
2024-11-01 17:43:48 - URL del audio: https://2upra.com/wp-content/uploads/2024/11/Rhodes-Dm_rdmS_2upra.wav
2024-11-01 17:43:48 - Iniciando la descarga del archivo: Rhodes-Dm_rdmS_2upra.wav
2024-11-01 17:43:48 - Token eliminado después de la descarga.

*/


// Handler AJAX para procesar la descarga
add_action('wp_ajax_procesarDescarga', 'procesarDescarga');

function procesarDescarga() {
    $usuario_id = get_current_user_id();
    guardarLog("Usuario ID: " . $usuario_id);

    if (!$usuario_id) {
        guardarLog("Error: Usuario no autorizado.");
        wp_send_json_error(['message' => 'No autorizado.']);
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    guardarLog("Post ID recibido: " . $post_id);

    // Comprobación actualizada del post
    $post = get_post($post_id);
    guardarLog("Post obtenido: " . print_r($post, true));

    if (!$post || $post->post_status !== 'publish') {
        guardarLog('Post no válido o no publicado: ' . $post_id);
        wp_send_json_error(['message' => 'Post no válido.']);
    }

    $audio_id = get_post_meta($post_id, 'post_audio', true);
    guardarLog("Audio ID obtenido: " . $audio_id);

    if (!$audio_id) {
        guardarLog("Error: Audio no encontrado para el Post ID: " . $post_id);
        wp_send_json_error(['message' => 'Audio no encontrado.']);
    }

    $pinky = (int)get_user_meta($usuario_id, 'pinky', true);
    guardarLog("Pinkys del usuario: " . $pinky);

    if ($pinky >= 1) {
        guardarLog("Restando 1 Pinky al usuario: " . $usuario_id);
        restarPinkys($usuario_id, 1);

        $download_url = generarEnlaceDescarga($usuario_id, $audio_id);
        guardarLog("Enlace de descarga generado: " . $download_url);

        agregarNoti($usuario_id, 'Descarga disponible', $download_url, $usuario_id);
        wp_send_json_success(['download_url' => $download_url]);
    } else {
        guardarLog("Error: No suficientes Pinkys para el usuario: " . $usuario_id);
        agregarNoti($usuario_id, 'No tienes suficientes Pinkys para esta descarga.', 'https://2upra.com', $usuario_id);
        wp_send_json_error(['message' => 'No tienes suficientes Pinkys para esta descarga.']);
    }
}

function generarEnlaceDescarga($usuario_id, $audio_id) {
    $token = bin2hex(random_bytes(16));
    guardarLog("Token generado: " . $token);

    $token_data = array(
        'user_id' => $usuario_id,
        'audio_id' => $audio_id,
        'time' => time(),
    );
    guardarLog("Datos del token: " . print_r($token_data, true));

    set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS); // válido por 1 hora
    guardarLog("Token almacenado en transients.");

    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
    ], home_url());

    guardarLog("Enlace de descarga final: " . $enlaceDescarga);

    return $enlaceDescarga;
}

function descargaAudio() {
    if (isset($_GET['descarga_token'])) {
        $token = sanitize_text_field($_GET['descarga_token']);
        guardarLog("Token de descarga recibido: " . $token);

        $token_data = get_transient('descarga_token_' . $token);
        guardarLog("Datos del token recuperados: " . print_r($token_data, true));

        if ($token_data) {
            $usuario_id = get_current_user_id();
            guardarLog("Usuario actual: " . $usuario_id);

            if ($usuario_id != $token_data['user_id']) {
                guardarLog("Error: El usuario no tiene permiso para descargar este archivo.");
                wp_die('No tienes permiso para descargar este archivo.');
            }

            $audio_id = $token_data['audio_id'];
            // Obtener la ruta del archivo en el sistema de archivos
            $audio_path = get_attached_file($audio_id);
            guardarLog("Ruta del audio: " . $audio_path);

            if ($audio_path && file_exists($audio_path)) {
                // Obtener el tipo MIME del archivo
                $mime_type = get_post_mime_type($audio_id);
                if (!$mime_type) {
                    $mime_type = 'application/octet-stream';
                }

                // Establecer los encabezados adecuados
                header('Content-Description: File Transfer');
                header('Content-Type: ' . $mime_type);
                header('Content-Disposition: attachment; filename="' . basename($audio_path) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($audio_path));

                // Limpiar el búfer de salida para evitar corrupción
                ob_clean();
                flush();

                // Leer el archivo y enviarlo al usuario
                readfile($audio_path);

                // Eliminar el token después de la descarga
                delete_transient('descarga_token_' . $token);
                guardarLog("Token eliminado después de la descarga.");
                exit;
            } else {
                guardarLog("Error: El archivo no existe o no es accesible.");
                wp_die('El archivo no existe o no es accesible.');
            }
        } else {
            guardarLog("Error: El enlace de descarga no es válido o ha expirado.");
            wp_die('El enlace de descarga no es válido o ha expirado.');
        }
    }
}
add_action('template_redirect', 'descargaAudio');


// Función para generar el botón de descarga
function botonDescarga($post_id)
{
    ob_start();

    $paraDescarga = get_post_meta($post_id, 'paraDescarga', true);
    $usuario_id = get_current_user_id();

    if ($paraDescarga == '1') {
        if ($usuario_id) {
            ?>
            <div class="ZAQIBB">
                <button onclick="return procesarDescarga('<? echo esc_js($post_id); ?>', '<? echo esc_js($usuario_id); ?>')">
                    <? echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
            <?
        } else {
            ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down">
                    <? echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
            <?
        }
    }
    return ob_get_clean();
}

// Agregar pinkys al registrarse un nuevo usuario
function pinkysRegistro($user_id)
{
    $pinkys_iniciales = 10;
    update_user_meta($user_id, 'pinky', $pinkys_iniciales);
}
add_action('user_register', 'pinkysRegistro');

// Función para restablecer los pinkys semanalmente
function restablecerPinkys()
{
    $usuarios_query = new WP_User_Query(array(
        'fields' => 'ID',
    ));

    if (!empty($usuarios_query->results)) {
        foreach ($usuarios_query->results as $usuario_id) {
            $monedas_actuales = (int) get_user_meta($usuario_id, 'pinky', true);
            if ($monedas_actuales < 10) {
                update_user_meta($usuario_id, 'pinky', 10);
            }
        }
    }
}
add_action('restablecer_pinkys_semanal', 'restablecerPinkys');

// Programar el evento semanal para restablecer los pinkys
if (!wp_next_scheduled('restablecer_pinkys_semanal')) {
    wp_schedule_event(time(), 'weekly', 'restablecer_pinkys_semanal');
}


function botonDescargaPrueba()
{
    ob_start();
    ?>
    <div class="ZAQIBB ASDGD8">
        <button>
            <? echo $GLOBALS['descargaicono']; ?>
        </button>
    </div>
<?
    return ob_get_clean();
}
