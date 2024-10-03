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
    global $wpdb;
    $tabla_bloqueo = $wpdb->prefix . 'bloqueo';
    $usuario_actual = get_current_user_id();
    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    
    if (!$post) {
        wp_send_json_error('Post no encontrado.');
        return;
    }
    
    $autor_id = $post->post_author;
    $bloqueo_existente = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $tabla_bloqueo WHERE idUser = %d AND idBloqueado = %d",
        $usuario_actual,
        $autor_id
    ));
    
    if ($bloqueo_existente) {
        $wpdb->delete($tabla_bloqueo, array(
            'idUser' => $usuario_actual,
            'idBloqueado' => $autor_id
        ));
        wp_send_json_success('Usuario desbloqueado.');
    } else {
        $wpdb->insert($tabla_bloqueo, array(
            'idUser' => $usuario_actual,
            'idBloqueado' => $autor_id
        ));
        wp_send_json_success('Usuario bloqueado.');
    }
}
add_action('wp_ajax_guardarBloqueo', 'guardarBloqueo');

function quitarBloqueo() {
    global $wpdb;
    $tabla_bloqueo = $wpdb->prefix . 'bloqueo';
    $usuario_actual = get_current_user_id();
    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    
    if (!$post) {
        wp_send_json_error('Post no encontrado.');
        return;
    }
    
    $autor_id = $post->post_author;
    $resultado = $wpdb->delete($tabla_bloqueo, array(
        'idUser' => $usuario_actual,
        'idBloqueado' => $autor_id
    ));
    
    if ($resultado !== false) {
        wp_send_json_success('Bloqueo eliminado.');
    } else {
        wp_send_json_error('No se pudo eliminar el bloqueo.');
    }
}
add_action('wp_ajax_quitarBloqueo', 'quitarBloqueo');