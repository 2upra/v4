<?php

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

    actualizarMetaDatos($postId);
    guardarLog('Meta datos actualizados.');
    datosParaAlgoritmo($postId);
    guardarLog('Datos para algoritmo procesados.');

    confirmarArchivos($postId);
    guardarLog('Archivos confirmados.');

    procesarURLs($postId);
    guardarLog('URLs procesadas.');

    asignarTags($postId);
    guardarLog('Tags asignadas.');

    guardarLog('Post RS creado con ID: ' . $postId);

    guardarLog('Final completado');

    wp_send_json_success(['message' => 'Post creado exitosamente']);
    wp_die();
}

add_action('wp_ajax_subidaRs', 'subidaRs');



