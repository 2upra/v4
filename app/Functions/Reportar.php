<?php

//que esto en vez de guardarlo en una tabla lo guarde en un post type reporte
function guardarReporte() {
    // 1. Verificar el nonce
    check_ajax_referer('guardar_reporte_nonce', 'nonce');

    $idUser = get_current_user_id();
    $idContenido = intval($_POST['post_id']);
    $tipoContenido = sanitize_text_field($_POST['tipoContenido']);
    $detalles = sanitize_textarea_field($_POST['detalles']);

    // Crear el título del reporte
    $user_name = get_userdata($idUser)->display_name;
    $post_title = "Reporte de " . $user_name;
    if ($tipoContenido === 'comentario') {
        // Intentar obtener el título del comentario (puede requerir lógica adicional si no es un post)
        // Por ahora, usamos el ID
        $post_title .= " sobre el comentario ID: " . $idContenido;
    } else {
        $post_title .= " sobre la publicación ID: " . $idContenido;
    }

    // Crear el post del reporte
    $reporte_id = wp_insert_post(array(
        'post_title'    => $post_title,
        'post_content'  => $detalles,
        'post_status'   => 'publish',  // O 'draft', 'pending' según tus necesidades
        'post_type'     => 'reporte',  // Asegúrate de que este post type esté registrado
        'post_author'   => $idUser,
    ));

    if (is_wp_error($reporte_id)) {
        wp_send_json_error('Error al crear el reporte: ' . $reporte_id->get_error_message());
        return;
    }

    // Guardar metadatos del reporte
    update_post_meta($reporte_id, 'idContenido', $idContenido);
    update_post_meta($reporte_id, 'tipoContenido', $tipoContenido);

    wp_send_json_success('Reporte guardado con ID: ' . $reporte_id);
}
add_action('wp_ajax_guardarReporte', 'guardarReporte');



