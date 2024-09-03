<?php

function agregar_pinkys_al_usuario($usuario_id, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($usuario_id, 'pinky', true);
    $nuevas_monedas = $monedas_actuales + $cantidad;
    update_user_meta($usuario_id, 'pinky', $nuevas_monedas);
}

add_action('before_delete_post', 'restar_pinkys_al_usuario_por_eliminacion');

function restar_pinkys_al_usuario_por_eliminacion($post_id)
{
    $post = get_post($post_id);
    $usuario_id = $post->post_author;

    if ($usuario_id) {
        restar_pinkys_al_usuario($usuario_id, 1);
    }
}

function restar_pinkys_al_usuario($usuario_id, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($usuario_id, 'pinky', true);
    $nuevas_monedas = $monedas_actuales - $cantidad;

    update_user_meta($usuario_id, 'pinky', $nuevas_monedas);
}

add_action('wp_ajax_procesar_descarga', 'procesar_descarga_ajax_handler');


function mostrar_pinkys_usuario()
{
    if (is_user_logged_in()) {
        $usuario_id = get_current_user_id();
        $monedas = get_user_meta($usuario_id, 'pinky', true);
        return " " . $monedas . ' <svg data-testid="geist-icon" height="12" stroke-linejoin="round" viewBox="0 0 16 16" width="12" style="margin: 5px;margin-bottom: 7px;color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 14.5C11.5899 14.5 14.5 11.5899 14.5 8C14.5 4.41015 11.5899 1.5 8 1.5C4.41015 1.5 1.5 4.41015 1.5 8C1.5 11.5899 4.41015 14.5 8 14.5ZM8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM8.62499 3.375V4V4.375H9C10.1736 4.375 11.125 5.3264 11.125 6.5H9.875C9.875 6.01675 9.48325 5.625 9 5.625H8.62499V7.375H9C10.1736 7.375 11.125 8.3264 11.125 9.5C11.125 10.6736 10.1736 11.625 9 11.625H8.62499V12V12.625H7.37499V12V11.625H7C5.8264 11.625 4.875 10.6736 4.875 9.5H6.125C6.125 9.98325 6.51675 10.375 7 10.375H7.37499V8.625H7C5.8264 8.625 4.875 7.6736 4.875 6.5C4.875 5.3264 5.8264 4.375 7 4.375H7.37499V4V3.375H8.62499ZM7.37499 5.625H7C6.51675 5.625 6.125 6.01675 6.125 6.5C6.125 6.98325 6.51675 7.375 7 7.375H7.37499V5.625ZM8.62499 8.625V10.375H9C9.48325 10.375 9.875 9.98325 9.875 9.5C9.875 9.01675 9.48325 8.625 9 8.625H8.62499Z" fill="currentColor"></path></svg>';
    } else {
        return;
    }
}
add_shortcode('mostrar_pinkys', 'mostrar_pinkys_usuario');

function procesar_descarga_ajax_handler()
{
    check_ajax_referer('procesar_descarga_nonce', 'nonce');

    $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
    $enlace_descarga = isset($_POST['enlace_descarga']) ? esc_url($_POST['enlace_descarga']) : '';
    $pinky = get_user_meta($usuario_id, 'pinky', true);

    if ($pinky >= 1) {
        update_user_meta($usuario_id, 'pinky', --$pinky);

        insertar_notificacion($usuario_id, 'Has utilizado un Pinky para descargar. Haz click aquí para descargar.', $enlace_descarga, $usuario_id);
        wp_send_json_success();
    } else {
        insertar_notificacion($usuario_id, 'No tienes suficientes Pinkys para esta descarga.', 'https://2upra.com', $usuario_id);
        wp_send_json_error(['message' => 'No tienes suficientes Pinkys para esta descarga.']);
    }
}


function botonDescarga($post_id)
{
    ob_start();

    $allow_download = get_post_meta($post_id, 'allow_download', true);
    $usuario_id = get_current_user_id();
    $pinky = get_user_meta($usuario_id, 'pinky', true);

    if ($allow_download == '1') {
        $audio_id = get_post_meta(get_the_ID(), 'post_audio', true);
        $audio_url = wp_get_attachment_url($audio_id);

        if ($usuario_id) {
            $enlaceDescarga = generar_enlace_descarga($usuario_id, $audio_url);
?>
            <div class="ZAQIBB">
                <button onclick="return procesarDescarga('<?php echo esc_js($enlaceDescarga); ?>', '<?php echo esc_js($usuario_id); ?>')">
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


function encolar_pinky_cobro_script()
{
    wp_register_script('pinkycobro-js', get_template_directory_uri() . '/js/pinkycobro.js', array('jquery'), '1.2.6', true);
    wp_localize_script('pinkycobro-js', 'pinkyCobro', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('procesar_descarga_nonce'),
    ));
    wp_enqueue_script('pinkycobro-js');
}
add_action('wp_enqueue_scripts', 'encolar_pinky_cobro_script');



function ejecutar_actualizacion_saldo_pinky()
{
    verificar_y_actualizar_saldo_pinky();
}

add_action('wp', 'activar_cron_pinkys');
function activar_cron_pinkys()
{
    if (!wp_next_scheduled('restablecer_pinkys_semanal')) {
        wp_schedule_event(time(), 'weekly', 'restablecer_pinkys_semanal');
    }
}

add_action('restablecer_pinkys_semanal', 'restablecer_pinkys_todos_usuarios');


function restablecer_pinkys_todos_usuarios()
{
    $usuarios_query = new WP_User_Query(array(
        'fields' => 'ID',
    ));

    if (!empty($usuarios_query->results)) {
        foreach ($usuarios_query->results as $usuario_id) {
            $monedas_actuales = (int) get_user_meta($usuario_id, 'pinky', true);
            if ($monedas_actuales > 0 && $monedas_actuales < 10) {
                update_user_meta($usuario_id, 'pinky', 10);
            } elseif ($monedas_actuales <= 0) {
                $nuevas_monedas = $monedas_actuales + 10;
                update_user_meta($usuario_id, 'pinky', $nuevas_monedas);
            }
        }
    }
}

function agregar_pinkys_al_registrarse($user_id)
{
    $pinkys_iniciales = 10;
    update_user_meta($user_id, 'pinky', $pinkys_iniciales);
}

add_action('user_register', 'agregar_pinkys_al_registrarse');
