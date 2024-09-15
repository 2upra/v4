<?php

//Falta update_post_meta($post_id, 'additional_search_data', $additional_data_json);
// Formulario principal

function subidaRs()
{
    guardarLog("Contenido de \$_POST en subidaRs: " . print_r($_POST, true));

    if (!is_user_logged_in()) {
        guardarLog('Error: Usuario no autorizado');
        wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
    }
    
    guardarLog('Usuario autorizado, procediendo a crear el post.');

    $postId = crearPost();
    if (is_wp_error($postId)) {
        guardarLog('Error al crear el post: ' . $postId->get_error_message());
        wp_send_json_error(['message' => 'Error al crear el post']);
    }

    guardarLog('Post creado con ID: ' . $postId . '. Actualizando meta datos.');
    //FUNCIONA
    actualizarMetaDatos($postId);
    guardarLog('Meta datos actualizados.');
    //FUNCIONA
    confirmarArchivos($postId);
    guardarLog('Archivos confirmados.');
    //FUNCIONA
    procesarURLs($postId);
    guardarLog('URLs procesadas.');
    asignarTags($postId);
    guardarLog('Tags asignadas.');

    guardarLog('Post RS creado con ID: ' . $postId);

    update_post_meta($postId, '_post_puntuacion_final', 100);
    guardarLog('Final completado');

    wp_send_json_success(['message' => 'Post creado exitosamente']);
    wp_die();
}

add_action('wp_ajax_subidaRs', 'subidaRs');



