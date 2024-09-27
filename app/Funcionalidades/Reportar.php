<?php

// Crear la tabla si no existe (ya está en tu código)
function tablaReportes() {
    global $wpdb;
    $nombre_tabla = $wpdb->prefix . 'tablaReportes';
    
    if($wpdb->get_var("SHOW TABLES LIKE '$nombre_tabla'") != $nombre_tabla) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $nombre_tabla (
            idReporte BIGINT(20) NOT NULL AUTO_INCREMENT,
            idUser BIGINT(20) NOT NULL,
            idContenido BIGINT(20) NOT NULL,
            tipoContenido VARCHAR(255) NOT NULL, 
            detalles TEXT NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            metadatos LONGTEXT,
            PRIMARY KEY (idReporte)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
tablaReportes();

// Función para guardar reporte
function guardarReporte() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'tablaReportes';

    $idUser = get_current_user_id();
    $idContenido = intval($_POST['idContenido']);
    $tipoContenido = sanitize_text_field($_POST['tipoContenido']);
    $detalles = sanitize_textarea_field($_POST['detalles']);

    $wpdb->insert($tabla, [
        'idUser' => $idUser,
        'idContenido' => $idContenido,
        'tipoContenido' => $tipoContenido,
        'detalles' => $detalles,
        'metadatos' => maybe_serialize($_SERVER) 
    ]);

    wp_send_json_success('Reporte guardado');
}
add_action('wp_ajax_guardarReporte', 'guardarReporte');


function eliminarReporte() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'tablaReportes';
    $idReporte = intval($_POST['idReporte']);

    $wpdb->delete($tabla, ['idReporte' => $idReporte]);

    wp_send_json_success('Reporte eliminado');
}
add_action('wp_ajax_eliminarReport', 'eliminarReporte');


function verReportes() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'tablaReportes';
    $reportes = $wpdb->get_results("SELECT * FROM $tabla");

    wp_send_json_success($reportes);
}
add_action('wp_ajax_verReportes', 'verReportes');

