<?php

function subidaRs()
{
    guardarLog("Contenido de \\$_POST en subidaRs: " . print_r($_POST, true));

    if (!is_user_logged_in()) {
        guardarLog('Error: Usuario no autorizado');
        wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
    }

    $idPost = crearPost();
    if (is_wp_error($idPost)) {
        guardarLog('Error al crear el post: ' . $idPost->get_error_message());
        wp_send_json_error(['message' => 'Error al crear el post']);
    }
    actualizarMetaDatos($idPost);
    datosParaAlgoritmo($idPost);
    confirmarArchivos($idPost);
    procesarURLs($idPost);
    asignarTags($idPost);

    wp_send_json_success(['message' => 'Post creado exitosamente']);

    if (isset($_POST['multiple']) && $_POST['multiple'] == '1') {
        multiplesPost($idPost);
    }
    wp_die();
}

add_action('wp_ajax_subidaRs', 'subidaRs');
