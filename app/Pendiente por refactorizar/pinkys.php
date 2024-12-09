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


/**
 * Procesa la solicitud de descarga de un audio.
 *
 * Verifica la autorización del usuario, la validez del post y del audio asociado.
 * Actualiza el contador de descargas del usuario y del post.
 * Genera un enlace de descarga único y lo envía como respuesta.
 */
function procesarDescarga() {
    error_log('--------------------------------------------------');
    error_log('[Inicio] Function procesarDescarga started');

    $userID = get_current_user_id();
    error_log('User ID: ' . $userID);
    error_log('User Agent: ' . $_SERVER['HTTP_USER_AGENT']);
    error_log('Request Headers: ' . print_r(getallheaders(), true));

    if (!$userID) {
        error_log('[Error] No autorizado. Usuario no logueado.');
        wp_send_json_error(['message' => 'No autorizado.']);
        return;
    }

    $postID = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    error_log('Post ID: ' . $postID);

    $post = get_post($postID);
    if (!$post || $post->post_status !== 'publish') {
        error_log('[Error] Post no válido o no publicado. Post ID: ' . $postID);
        wp_send_json_error(['message' => 'Post no válido.']);
        return;
    }

    $audioID = get_post_meta($postID, 'post_audio', true);
    error_log('Audio ID: ' . $audioID);

    if (!$audioID) {
        error_log('[Error] Audio no encontrado para el Post ID: ' . $postID);
        wp_send_json_error(['message' => 'Audio no encontrado.']);
        return;
    }

    $descargasAnteriores = get_user_meta($userID, 'descargas', true);
    error_log('Descargas before: ' . print_r($descargasAnteriores, true));

    if (!is_array($descargasAnteriores)) {
        $descargasAnteriores = [];
        error_log('[Info] El usuario no tenía descargas previas. Se inicializa el array.');
    }

    $yaDescargado = isset($descargasAnteriores[$postID]);
    error_log('Ya descargado: ' . ($yaDescargado ? 'true' : 'false'));

    if (!$yaDescargado) {
        $pinky = (int)get_user_meta($userID, 'pinky', true);
        error_log('Pinky count: ' . $pinky);

        if ($pinky < 1) {
            error_log('[Error] No tienes suficientes Pinkys para esta descarga. Pinkys: ' . $pinky);
            wp_send_json_error(['message' => 'No tienes suficientes Pinkys para esta descarga.']);
            return;
        }
        restarPinkys($userID, 1);
    }

    if (!$yaDescargado) {
        $descargasAnteriores[$postID] = 1;
        error_log('[Info] Primera descarga de este audio. Se incrementa a 1.');
    } else {
        $descargasAnteriores[$postID]++;
        error_log('[Info] Descarga repetida. Se incrementa a: ' . $descargasAnteriores[$postID]);
    }

    error_log('Descargas after: ' . print_r($descargasAnteriores, true));

    $updateResult = update_user_meta($userID, 'descargas', $descargasAnteriores);
    if (!$updateResult) {
        error_log('[Error] Failed to update descargas meta for user ID ' . $userID);
    } else {
        error_log('[OK] Successfully updated descargas meta for user ID ' . $userID);
    }

    $total_descargas = (int)get_post_meta($postID, 'totalDescargas', true);
    $total_descargas++;
    $updatePostMeta = update_post_meta($postID, 'totalDescargas', $total_descargas);

    if (!$updatePostMeta) {
        error_log('[Error] Failed to update totalDescargas meta for post ID ' . $postID);
    } else {
        error_log('[OK] Successfully updated totalDescargas meta for post ID ' . $postID);
    }

    $download_url = generarEnlaceDescarga($userID, $audioID);
    error_log('Download URL: ' . $download_url);

    actualizarTimestampDescargas($userID);
    error_log('[Fin] Function procesarDescarga finished');
    error_log('--------------------------------------------------');
    wp_send_json_success(['download_url' => $download_url]);
}

/**
 * Genera un enlace de descarga único con un token de seguridad.
 *
 * @param int $userID ID del usuario.
 * @param int $audioID ID del audio.
 * @return string Enlace de descarga con token.
 */
function generarEnlaceDescarga($userID, $audioID) {
    $token = bin2hex(random_bytes(16));

    $token_data = array(
        'user_id' => $userID,
        'audio_id' => $audioID,
        'time' => time(),
        'usos' => 0, // Inicializar el contador de usos
    );

    error_log("--------------------------------------------------");
    error_log("[Inicio] Generando enlace de descarga. UserID: " . $userID . ", AudioID: " . $audioID . ", Token: " . $token . ", Time: " . time());

    set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS); // válido por 1 hora
    error_log("Token data set in transient: " . print_r($token_data, true));

    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
    ], home_url());

    error_log("Enlace de descarga generado: " . $enlaceDescarga);
    error_log("[Fin] Generando enlace de descarga.");
    error_log("--------------------------------------------------");
    return $enlaceDescarga;
}

/**
 * Procesa la descarga del audio utilizando un token único.
 *
 * Verifica la validez del token y la autorización del usuario.
 * Envía el archivo al usuario si la verificación es exitosa.
 */
function descargaAudio() {
    if (isset($_GET['descarga_token'])) {
        $token = sanitize_text_field($_GET['descarga_token']);

        error_log("--------------------------------------------------");
        error_log("[Inicio] Intentando descargar con token: " . $token);
        error_log('User Agent: ' . $_SERVER['HTTP_USER_AGENT']);
        error_log('Request Headers: ' . print_r(getallheaders(), true));

        $token_data = get_transient('descarga_token_' . $token);

        if ($token_data) {
            error_log("Datos del token recuperados: " . print_r($token_data, true));

            $userID = get_current_user_id();
            error_log("UserID actual: " . $userID);
            error_log("UserID del token: " . $token_data['user_id']);

            if ($userID != $token_data['user_id']) {
                error_log("[Error] Descarga de audio: Usuario no autorizado. UserID: " . $userID . ", Token UserID: " . $token_data['user_id']);
                error_log("--------------------------------------------------");
                wp_die('No tienes permiso para descargar este archivo.');
            }

            // Verificar el número de usos
            if ($token_data['usos'] >= 3) {
                error_log("[Error] Descarga de audio: Token ha excedido el número de usos permitidos. Usos: " . $token_data['usos']);
                delete_transient('descarga_token_' . $token);
                error_log("[Error] Token de descarga eliminado por exceder usos: " . $token);
                error_log("--------------------------------------------------");
                wp_die('El enlace de descarga ha excedido el número de usos permitidos.');
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
                error_log("Configuración del servidor ajustada para la descarga.");

                // Obtener el tipo MIME y el nombre del archivo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo === false) {
                    error_log("[Error] Descarga de audio: Error al abrir finfo.");
                    wp_die('Error al obtener información del archivo.');
                }
                $mime_type = finfo_file($finfo, $audio_path);
                if ($mime_type === false) {
                    error_log("[Error] Descarga de audio: Error al obtener el tipo MIME del archivo: " . $audio_path);
                    finfo_close($finfo);
                    wp_die('Error al obtener el tipo de archivo.');
                }
                finfo_close($finfo);
                error_log("Tipo MIME del archivo: " . $mime_type);

                $filename = basename($audio_path);
                error_log("Nombre del archivo: " . $filename);

                // Cabeceras HTTP
                header('Content-Type: ' . $mime_type);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($audio_path));
                header('Accept-Ranges: bytes');
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                error_log("Cabeceras HTTP configuradas correctamente.");

                // Manejo de rangos para descarga parcial
                if (isset($_SERVER['HTTP_RANGE'])) {
                    error_log("Solicitud de rango recibida: " . $_SERVER['HTTP_RANGE']);
                    list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
                    list($range) = explode(",", $range, 2);
                    list($range, $range_end) = explode("-", $range);
                    $range = intval($range);
                    $size = filesize($audio_path);
                    $range_end = ($range_end) ? intval($range_end) : $size - 1;

                    header('HTTP/1.1 206 Partial Content');
                    header("Content-Range: bytes $range-$range_end/$size");
                    header('Content-Length: ' . ($range_end - $range + 1));
                    error_log("Respondiendo con contenido parcial. Rango: $range-$range_end");
                } else {
                    $range = 0;
                    error_log("No se solicitó rango. Se enviará el archivo completo.");
                }

                // Abrir y enviar el archivo
                $fp = fopen($audio_path, 'rb');
                if ($fp === false) {
                    error_log("[Error] Descarga de audio: Error al abrir el archivo: " . $audio_path);
                    wp_die('Error al abrir el archivo.');
                }
                fseek($fp, $range);
                error_log("Posición del puntero de archivo ajustada a: " . $range);

                error_log("Iniciando la transmisión del archivo...");
                while (!feof($fp)) {
                    $data = fread($fp, 8192);
                    if ($data === false) {
                        error_log("[Error] Descarga de audio: Error al leer el archivo: " . $audio_path);
                        fclose($fp);
                        wp_die('Error al leer el archivo.');
                    }
                    print($data);
                    flush();
                    if (connection_status() != 0) {
                        error_log("[Error] Descarga de audio: Conexión interrumpida. Estado: " . connection_status());
                        fclose($fp);
                        exit;
                    }
                }

                fclose($fp);
                error_log("Transmisión del archivo finalizada con éxito.");

                // Incrementar el contador de usos y actualizar el token
                $token_data['usos']++;
                set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS);
                error_log("Contador de usos incrementado a: " . $token_data['usos']);

                // Eliminar el token si ha alcanzado el límite de usos
                if ($token_data['usos'] >= 3) {
                    delete_transient('descarga_token_' . $token);
                    error_log("[OK] Token de descarga eliminado después de 3 usos: " . $token);
                }

                error_log("[Fin] Descarga de audio completada.");
                error_log("--------------------------------------------------");
                exit;
            } else {
                error_log("[Error] Descarga de audio: El archivo no existe o no es accesible. Ruta: " . $audio_path);
                error_log("--------------------------------------------------");
                wp_die('El archivo no existe o no es accesible.');
            }
        } else {
            error_log("[Error] Descarga de audio: Token de descarga no válido o expirado. Token: " . $token);
            error_log("--------------------------------------------------");
            wp_die('El enlace de descarga no es válido o ha expirado.');
        }
    }
}


add_action('wp_ajax_descargar_audio', 'procesarDescarga');
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
