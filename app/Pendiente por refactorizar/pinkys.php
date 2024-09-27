<?php

// Función para agregar pinkys al usuario
function agregar_pinkys_al_usuario($usuario_id, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($usuario_id, 'pinky', true);
    $nuevas_monedas = $monedas_actuales + $cantidad;
    update_user_meta($usuario_id, 'pinky', $nuevas_monedas);
}

// Función para restar pinkys al usuario
function restar_pinkys_al_usuario($usuario_id, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($usuario_id, 'pinky', true);
    $nuevas_monedas = $monedas_actuales - $cantidad;
    update_user_meta($usuario_id, 'pinky', $nuevas_monedas);
}

// Acción para restar pinkys al eliminar un post
add_action('before_delete_post', 'restar_pinkys_al_usuario_por_eliminacion');
function restar_pinkys_al_usuario_por_eliminacion($post_id)
{
    $post = get_post($post_id);
    $usuario_id = $post->post_author;

    if ($usuario_id) {
        restar_pinkys_al_usuario($usuario_id, 1);
    }
}

// Handler AJAX para procesar la descarga
add_action('wp_ajax_procesar_descarga', 'procesar_descarga_ajax_handler');
function procesar_descarga_ajax_handler()
{
    check_ajax_referer('procesar_descarga_nonce', 'nonce');

    $usuario_id = get_current_user_id();
    if (!$usuario_id) {
        wp_send_json_error(['message' => 'No autorizado.']);
    }

    $audio_id = isset($_POST['audio_id']) ? intval($_POST['audio_id']) : 0;

    if (!$audio_id || get_post_status($audio_id) != 'publish') {
        wp_send_json_error(['message' => 'Audio no válido.']);
    }

    $pinky = (int)get_user_meta($usuario_id, 'pinky', true);

    if ($pinky >= 1) {
        restar_pinkys_al_usuario($usuario_id, 1);

        // Generar enlace de descarga
        $download_url = generar_enlace_descarga($usuario_id, $audio_id);

        // Puedes insertar una notificación si lo deseas
        // insertar_notificacion($usuario_id, 'Descarga disponible', $download_url, $usuario_id);

        wp_send_json_success(['download_url' => $download_url]);
    } else {
        // inserta una notificación si es necesario
        // insertar_notificacion($usuario_id, 'No tienes suficientes Pinkys para esta descarga.', 'https://2upra.com', $usuario_id);
        wp_send_json_error(['message' => 'No tienes suficientes Pinkys para esta descarga.']);
    }
}

// Función para generar el botón de descarga
function botonDescarga($post_id)
{
    ob_start();

    $paraDescarga = get_post_meta($post_id, 'paraDescarga', true);
    $usuario_id = get_current_user_id();

    if ($paraDescarga == '1') {
        $audio_id = get_post_meta(get_the_ID(), 'post_audio', true);

        if ($usuario_id) {
            ?>
            <div class="ZAQIBB">
                <button onclick="return procesarDescarga('<?php echo esc_js($audio_id); ?>')">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
            <?php
        } else {
            ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
            <?php
        }
    }

    return ob_get_clean();
}

// Función para generar el enlace de descarga seguro
function generar_enlace_descarga($usuario_id, $audio_id) {
    $token = bin2hex(random_bytes(16));
    $token_data = array(
        'user_id' => $usuario_id,
        'audio_id' => $audio_id,
        'time' => time(),
    );
    set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS); // válido por 1 hora
    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
    ], home_url());

    return $enlaceDescarga;
}

// Procesar la descarga del audio
function procesar_descarga_audio() {
    if (isset($_GET['descarga_token'])) {
        $token = sanitize_text_field($_GET['descarga_token']);
        $token_data = get_transient('descarga_token_' . $token);
        if ($token_data) {
            $usuario_id = get_current_user_id();
            if ($usuario_id != $token_data['user_id']) {
                wp_die('No tienes permiso para descargar este archivo.');
            }
            $audio_id = $token_data['audio_id'];

            // Obtener la URL del audio
            $audio_url = wp_get_attachment_url($audio_id);

            if ($audio_url) {
                // Forzar la descarga del archivo
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($audio_url) . '"');
                readfile($audio_url);
                delete_transient('descarga_token_' . $token); // Eliminar el token después de usarlo
                exit;
            } else {
                wp_die('El archivo no existe o no es accesible.');
            }
        } else {
            wp_die('El enlace de descarga no es válido o ha expirado.');
        }
    }
}
add_action('template_redirect', 'procesar_descarga_audio');

// Agregar pinkys al registrarse un nuevo usuario
function agregar_pinkys_al_registrarse($user_id)
{
    $pinkys_iniciales = 10;
    update_user_meta($user_id, 'pinky', $pinkys_iniciales);
}
add_action('user_register', 'agregar_pinkys_al_registrarse');

// Función para restablecer los pinkys semanalmente
function restablecer_pinkys_todos_usuarios()
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
add_action('restablecer_pinkys_semanal', 'restablecer_pinkys_todos_usuarios');

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
            <?php echo $GLOBALS['descargaicono']; ?>
        </button>
    </div>
<?php
    return ob_get_clean();
}
