<?php

function tablaBloqueo() {
    global $wpdb;
    $tabla_bloqueo = $wpdb->prefix . 'bloqueo'; 
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $tabla_bloqueo (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        idUser bigint(20) unsigned NOT NULL,
        idBloqueado bigint(20) unsigned NOT NULL,
        fecha datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        KEY idUser (idUser),
        KEY idBloqueado (idBloqueado)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
tablaBloqueo();


function guardarBloqueo() {
    $idUsuario = get_current_user_id();
    $idBloqueado = intval($_POST['idBloqueado']);
    if ($idUsuario === $idBloqueado) {
        wp_send_json_error('No puedes bloquearte a ti mismo.');
    }
    
    if (user_can($idBloqueado, 'administrator')) {
        wp_send_json_error('No puedes bloquear a un administrador.');
    }

    global $wpdb;
    $tabla_bloqueo = $wpdb->prefix . 'bloqueo';
    $existe = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_bloqueo WHERE idUsuario = %d AND idBloqueado = %d",
        $idUsuario, $idBloqueado
    ));

    if ($existe) {
        wp_send_json_error('Ya has bloqueado a este usuario.');
    }

    $wpdb->insert(
        $tabla_bloqueo,
        array(
            'idUsuario' => $idUsuario,
            'idBloqueado' => $idBloqueado,
            'fecha' => current_time('mysql')
        ),
        array('%d', '%d', '%s')
    );

    if ($wpdb->insert_id) {
        wp_send_json_success('Usuario bloqueado exitosamente.');
    } else {
        wp_send_json_error('Error al bloquear al usuario.');
    }
}
add_action('wp_ajax_guardarBloqueo', 'guardarBloqueo');

function quitarBloqueo() {
    $idUsuario = get_current_user_id();
    $idBloqueado = intval($_POST['idBloqueado']);

    global $wpdb;
    $tabla_bloqueo = $wpdb->prefix . 'bloqueo';
    $resultado = $wpdb->delete(
        $tabla_bloqueo,
        array(
            'idUsuario' => $idUsuario,
            'idBloqueado' => $idBloqueado
        ),
        array('%d', '%d')
    );

    if ($resultado) {
        wp_send_json_success('Usuario desbloqueado exitosamente.');
    } else {
        wp_send_json_error('Error al desbloquear al usuario.');
    }
}
add_action('wp_ajax_quitarBloqueo', 'quitarBloqueo');

// Obtener el autor del post
function obtenerAutor() {
    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);

    if ($post) {
        wp_send_json_success(array('autor_id' => $post->post_author));
    } else {
        wp_send_json_error('Post no encontrado.');
    }
}
add_action('wp_ajax_obtenerAutor', 'obtenerAutor');