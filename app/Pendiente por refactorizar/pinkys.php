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

pero si lo descargo directamente desde el enlace del servidor sin token funciona correctamente el archivo

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

        // agregarNoti($usuario_id, 'Descarga disponible', $download_url, $usuario_id);
        wp_send_json_success(['download_url' => $download_url]);
    } else {
        guardarLog("Error: No suficientes Pinkys para el usuario: " . $usuario_id);
        // agregarNoti($usuario_id, 'No tienes suficientes Pinkys para esta descarga.', 'https://2upra.com', $usuario_id);
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
            $audio_path = get_attached_file($audio_id);
            guardarLog("Ruta del audio: " . $audio_path);

            if ($audio_path && file_exists($audio_path) && is_readable($audio_path)) {
                // Limpiar cualquier salida previa
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Configuración del servidor
                ini_set('zlib.output_compression', 'Off');
                ini_set('output_buffering', 'Off');
                set_time_limit(0);

                // Obtener el tipo MIME y el nombre del archivo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $audio_path);
                finfo_close($finfo);
                
                $filename = basename($audio_path);

                // Cabeceras HTTP
                header('Content-Type: ' . $mime_type);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($audio_path));
                header('Accept-Ranges: bytes');
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');

                // Manejo de rangos para descarga parcial
                if (isset($_SERVER['HTTP_RANGE'])) {
                    list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
                    list($range) = explode(",", $range, 2);
                    list($range, $range_end) = explode("-", $range);
                    $range = intval($range);
                    $size = filesize($audio_path);
                    $range_end = ($range_end) ? intval($range_end) : $size - 1;
                    
                    header('HTTP/1.1 206 Partial Content');
                    header("Content-Range: bytes $range-$range_end/$size");
                    header('Content-Length: ' . ($range_end - $range + 1));
                } else {
                    $range = 0;
                }

                // Abrir y enviar el archivo
                $fp = fopen($audio_path, 'rb');
                fseek($fp, $range);
                
                while (!feof($fp)) {
                    print(fread($fp, 8192));
                    flush();
                    if (connection_status() != 0) {
                        fclose($fp);
                        exit;
                    }
                }
                
                fclose($fp);
                
                // Eliminar el token después de la descarga
                delete_transient('descarga_token_' . $token);
                guardarLog("Descarga completada y token eliminado.");
                exit;
            } else {
                guardarLog("Error: El archivo no existe o no es accesible.");
                wp_die('El archivo no existe o no es accesible.');
            }
        } else {
            guardarLog("Error: Token inválido o expirado.");
            wp_die('El enlace de descarga no es válido o ha expirado.');
        }
    }
}

add_action('template_redirect', 'descargaAudio');

/*
async function procesarDescarga(postId, usuarioId) {
    console.log('Iniciando procesarDescarga', postId, usuarioId);

    const confirmed = await new Promise(resolve => {
        const confirmBox = confirm('Esta descarga costará 1 Pinky. ¿Deseas continuar?');
        resolve(confirmBox);
    });

    if (!confirmed) {
        console.log('Descarga cancelada por el usuario.');
        return false;
    }

    try {
        const data = {
            post_id: postId 
        };

        // Enviar la solicitud AJAX
        const responseData = await enviarAjax('procesarDescarga', data);
        console.log('Datos de respuesta:', responseData);

        // Verificar si la respuesta fue exitosa
        if (responseData.success) {
            // Acceder a la propiedad download_url dentro de responseData.data
            if (responseData.data && responseData.data.download_url) {
                console.log('Descarga autorizada, iniciando descarga');
                window.location.href = responseData.data.download_url;  // Redirige a la URL de descarga
            } else {
                console.error('Error: download_url no está definido en la respuesta.');
                alert('Hubo un problema obteniendo el enlace de descarga.');
            }
        } else {
            console.log('No hay suficientes pinkys o error en la descarga.');
            alert(responseData.message || 'No tienes suficientes pinkys');
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
        alert('Ocurrió un error al procesar la descarga. Por favor, intenta de nuevo.');
    }

    return false;
}
*/

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
