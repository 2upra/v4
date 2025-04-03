<?
// Refactor(Org): Funcion modalTipoUsuario() movida a app/View/Modals/TipoUsuarioModal.php
// Refactor(Org): Funcion modalGeneros() movida a app/View/Modals/GenerosModal.php


function guardarGenerosUsuario()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión para realizar esta acción.');
    }
    $generos = isset($_POST['generos']) ? explode(',', $_POST['generos']) : array();

    if (empty($generos) || !is_array($generos)) {
        wp_send_json_error('No se recibieron géneros seleccionados.');
    }

    $generos_sanitizados = array_map('sanitize_text_field', $generos);
    $userId = get_current_user_id();
    update_user_meta($userId, 'usuarioPreferencias', $generos_sanitizados);
    wp_send_json_success('Los géneros han sido guardados.');
}

add_action('wp_ajax_guardarGenerosUsuario', 'guardarGenerosUsuario');
