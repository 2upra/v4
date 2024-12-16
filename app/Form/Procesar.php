<?

// Formulario principal

function subidaRs()
{
    guardarLog("Contenido de \$_POST en subidaRs: " . print_r($_POST, true));

    if (!is_user_logged_in()) {
        guardarLog('Error: Usuario no autorizado');
        wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
    }

    #Paso 1
    $postId = crearPost();
    if (is_wp_error($postId)) {
        guardarLog('Error al crear el post: ' . $postId->get_error_message());
        wp_send_json_error(['message' => 'Error al crear el post']);
    }
    #Paso 2
    actualizarMetaDatos($postId);
    #Paso 3
    datosParaAlgoritmo($postId);
    #Paso 4
    confirmarArchivos($postId);
    #Paso 5
    procesarURLs($postId);
    #Paso 6
    asignarTags($postId);
    
    wp_send_json_success(['message' => 'Post creado exitosamente']);
    wp_die();
}

add_action('wp_ajax_subidaRs', 'subidaRs');



