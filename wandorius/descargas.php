<?php

function generar_enlace_descarga($usuario_id, $audio_url) {
    $token = hash('sha256', $usuario_id . NONCE_SALT . time());
    update_user_meta($usuario_id, 'descarga_token', $token);

    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
        'audio_url' => base64_encode($audio_url)
    ], home_url());

    return $enlaceDescarga;
}

add_action('template_redirect', 'procesar_descarga_audio');

function procesar_descarga_audio() {
    if (isset($_GET['descarga_token'], $_GET['audio_url'])) {
        $usuario_id = get_current_user_id();
        $token = sanitize_text_field($_GET['descarga_token']);
        $audio_url = base64_decode(sanitize_text_field($_GET['audio_url']));
        $token_guardado = get_user_meta($usuario_id, 'descarga_token', true);
        
        if ($token === $token_guardado) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($audio_url).'"');
            readfile($audio_url);
            exit;
        } else {
            wp_die('El enlace de descarga no es válido o ha expirado.');
        }
    }
}

//Prueba