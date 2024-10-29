<?

function cambiar_tipo_usuario_callback()
{
    $user_id = get_current_user_id();
    $tipo = $_POST['tipo'];

    if ($tipo === 'fan') {
        $estado_actual = get_user_meta($user_id, 'fan', true);
        update_user_meta($user_id, 'fan', !$estado_actual);
    }

    echo !$estado_actual;
    wp_die();
}

add_action('wp_ajax_cambiar_tipo_usuario', 'cambiar_tipo_usuario_callback');
add_action('wp_ajax_nopriv_cambiar_tipo_usuario', 'cambiar_tipo_usuario_callback');

