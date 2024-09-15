<?php


// Formulario principal
function subidaRs()
{
    guardarLog("Contenido de \$_POST en subidaRs: " . print_r($_POST, true));

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
    }
    $postId = crearPost();
    if (is_wp_error($postId)) {
        wp_send_json_error(['message' => 'Error al crear el post']);
    }

    actualizarMetaDatos($postId);
    confirmarArchivos($postId);
    procesarURLs($postId);
    asignarTags($postId);
    guardarLog('Post RS creado con ID: ' . $postId);
    wp_send_json_success(['message' => 'Post creado exitosamente']);
}

add_action('wp_ajax_subidaRs', 'subidaRs');




