<?

// Función para agregar pinkys al usuario
function agregarPinkys($userID, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
    $nuevas_monedas = $monedas_actuales + $cantidad;
    update_user_meta($userID, 'pinky', $nuevas_monedas);
}

// Función para restar pinkys al usuario
function restarPinkys($userID, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
    $nuevas_monedas = $monedas_actuales - $cantidad;
    update_user_meta($userID, 'pinky', $nuevas_monedas);
}

// Acción para restar pinkys al eliminar un post
add_action('before_delete_post', 'restarPinkysEliminacion');
function restarPinkysEliminacion($postID)
{
    $post = get_post($postID);
    $userID = $post->post_author;

    if ($userID) {
        restarPinkys($userID, 1);
    }
}
add_action('wp_ajax_procesarDescarga', 'procesarDescarga');

function procesarDescarga() {
    error_log('Function procesarDescarga started');

    $userID = get_current_user_id();
    error_log('User ID: ' . $userID);
    if (!$userID) {
        wp_send_json_error(['message' => 'No autorizado.']);
        return;
    }

    $postID = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    error_log('Post ID: ' . $postID);
    $post = get_post($postID);
    if (!$post || $post->post_status !== 'publish') {
        wp_send_json_error(['message' => 'Post no válido.']);
        return;
    }

    $audioID = get_post_meta($postID, 'post_audio', true);
    error_log('Audio ID: ' . $audioID);
    if (!$audioID) {
        wp_send_json_error(['message' => 'Audio no encontrado.']);
        return;
    }

    $descargasAnteriores = get_user_meta($userID, 'descargas', true);
    error_log('Descargas before: ' . print_r($descargasAnteriores, true));
    if (!$descargasAnteriores) {
        $descargasAnteriores = [];
    }

    $yaDescargado = isset($descargasAnteriores[$postID]);
    error_log('Ya descargado: ' . ($yaDescargado ? 'true' : 'false'));

    if (!$yaDescargado) {
        $pinky = (int)get_user_meta($userID, 'pinky', true);
        error_log('Pinky count: ' . $pinky);
        if ($pinky < 1) {
            wp_send_json_error(['message' => 'No tienes suficientes Pinkys para esta descarga.']);
            return;
        }
        restarPinkys($userID, 1);
    }

    if (!$yaDescargado) {
        $descargasAnteriores[$postID] = 1;
    } else {
        $descargasAnteriores[$postID]++;
    }
    error_log('Descargas after: ' . print_r($descargasAnteriores, true));

    $updateResult = update_user_meta($userID, 'descargas', $descargasAnteriores);
    if ( ! $updateResult ) {
        error_log('Failed to update descargas meta for user ID ' . $userID);
    } else {
        error_log('Successfully updated descargas meta for user ID ' . $userID);
    }

    $total_descargas = (int)get_post_meta($postID, 'totalDescargas', true);
    $total_descargas++;
    $updatePostMeta = update_post_meta($postID, 'totalDescargas', $total_descargas);
    if ( ! $updatePostMeta ) {
        error_log('Failed to update totalDescargas meta for post ID ' . $postID);
    } else {
        error_log('Successfully updated totalDescargas meta for post ID ' . $postID);
    }

    $download_url = generarEnlaceDescarga($userID, $audioID);
    error_log('Download URL: ' . $download_url);
    actualizarTimestampDescargas($userID);
    wp_send_json_success(['download_url' => $download_url]);
}



function generarEnlaceDescarga($userID, $audioID) {
    $token = bin2hex(random_bytes(16));

    $token_data = array(
        'user_id' => $userID,
        'audio_id' => $audioID,
        'time' => time(),
    );

    set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS); // válido por 1 hora

    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
    ], home_url());


    return $enlaceDescarga;
}

function descargaAudio() {
    if (isset($_GET['descarga_token'])) {
        $token = sanitize_text_field($_GET['descarga_token']);

        $token_data = get_transient('descarga_token_' . $token);

        if ($token_data) {
            $userID = get_current_user_id();

            if ($userID != $token_data['user_id']) {
                error_log("Descarga de audio: Usuario no autorizado. UserID: " . $userID . ", Token UserID: " . $token_data['user_id']); // Log de error
                wp_die('No tienes permiso para descargar este archivo.');
            }

            $audioID = $token_data['audio_id'];
            $audio_path = get_attached_file($audioID);

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
                if ($finfo === false) {
                  error_log("Descarga de audio: Error al abrir finfo."); // Log de error
                }
                $mime_type = finfo_file($finfo, $audio_path);
                if ($mime_type === false) {
                  error_log("Descarga de audio: Error al obtener el tipo MIME del archivo: " . $audio_path); // Log de error
                }
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
                if ($fp === false) {
                    error_log("Descarga de audio: Error al abrir el archivo: " . $audio_path); // Log de error
                }
                fseek($fp, $range);

                while (!feof($fp)) {
                    $data = fread($fp, 8192);
                    if ($data === false) {
                        error_log("Descarga de audio: Error al leer el archivo: " . $audio_path); // Log de error
                        fclose($fp);
                        exit;
                    }
                    print($data);
                    flush();
                    if (connection_status() != 0) {
                        error_log("Descarga de audio: Conexión interrumpida. Estado: " . connection_status()); // Log de error
                        fclose($fp);
                        exit;
                    }
                }

                fclose($fp);

                // Eliminar el token después de la descarga
                delete_transient('descarga_token_' . $token);
                exit;
            } else {
                error_log("Descarga de audio: El archivo no existe o no es accesible. Ruta: " . $audio_path); // Log de error
                wp_die('El archivo no existe o no es accesible.');
            }
        } else {
            error_log("Descarga de audio: Token de descarga no válido o expirado. Token: " . $token); // Log de error
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
function botonDescarga($postID)
{
    ob_start();

    $paraDescarga = get_post_meta($postID, 'paraDescarga', true);
    $userID = get_current_user_id();

    if ($paraDescarga == '1') {
        if ($userID) {
            // Obtener descargas previas del usuario
            $descargas_anteriores = get_user_meta($userID, 'descargas', true);
            $yaDescargado = isset($descargas_anteriores[$postID]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';

            ?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down <?php echo esc_attr($claseExtra); ?>"
                        data-post-id="<?php echo esc_attr($postID); ?>"
                        aria-label="Boton Descarga" 
                        id="download-button-<?php echo esc_attr($postID); ?>"
                        onclick="return procesarDescarga('<?php echo esc_js($postID); ?>', '<?php echo esc_js($userID); ?>')">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
            <?php
        } else {
            ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar" >
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
            <?php
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
        foreach ($usuarios_query->results as $userID) {
            $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
            if ($monedas_actuales < 10) {
                update_user_meta($userID, 'pinky', 10);
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
        <button aria-label="Descarga ejemplo" >
            <? echo $GLOBALS['descargaicono']; ?>
        </button>
    </div>
<?
    return ob_get_clean();
}
