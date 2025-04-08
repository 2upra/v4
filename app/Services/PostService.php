<?php

#Maneja la subida de un post
function subidaRs()
{
    guardarLog("Contenido de \$_POST en subidaRs: " . print_r($_POST, true));

    if (!is_user_logged_in()) {
        guardarLog('Error: Usuario no autorizado');
        wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
    }

    // Refactor(Org): Función crearPost() movida a Post/PostCreationService.php
    // La función crearPost() ahora se encuentra en app/Services/Post/PostCreationService.php
    // Si necesitas usarla, asegúrate de que ese archivo esté incluido.
    // Ejemplo de llamada (asumiendo que PostCreationService.php está incluido):
    // $idPost = crearPost(); 

    $idPost = crearPost(); // Asegúrate de que PostCreationService.php esté incluido donde se llame a subidaRs
    if (is_wp_error($idPost)) {
        guardarLog('Error al crear el post: ' . $idPost->get_error_message());
        wp_send_json_error(['message' => 'Error al crear el post']);
    }
    actualizarMetaDatos($idPost);
    // Refactor(Org): Función datosParaAlgoritmo() movida a app/AlgoritmoPost/algoritmoPosts.php
    datosParaAlgoritmo($idPost); // Asegúrate de que algoritmoPosts.php esté incluido
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

#Asigna tags a un post
function asignarTags($idPost)
{
    if (!empty($_POST['tags'])) {
        $tagsString = sanitize_text_field($_POST['tags']);
        $tagsArreglo = array_map('trim', explode(',', $tagsString));
        $tagsArreglo = array_filter($tagsArreglo);

        if (!empty($tagsArreglo)) {
            $resultado = wp_set_post_tags($idPost, $tagsArreglo, false);

            if (is_wp_error($resultado)) {
                $mensajeError = str_replace("\n", " | ", $resultado->get_error_message());
                error_log("Error en asignarTags: Fallo al asignar tags para Post ID {$idPost}. Error: " . $mensajeError);
            } elseif (empty($resultado)) {
                error_log("Advertencia en asignarTags: wp_set_post_tags retornó vacío para Post ID {$idPost}. Tags: " . implode(', ', $tagsArreglo) . ".");
            } else {
                error_log("Tags asignados correctamente por asignarTags para Post ID {$idPost}: " . implode(', ', $tagsArreglo));
            }
        } else {
            error_log("Info en asignarTags: No se proporcionaron tags válidos para Post ID {$idPost} en el campo 'tags'.");
        }
    } else {
        error_log("Info en asignarTags: Campo 'tags' no presente o vacío para Post ID {$idPost}. No se asignaron tags.");
    }
}


#Actualiza los metadatos de un post
function actualizarMetaDatos($idPost)
{
    $camposMeta = [
        'paraColab'         => 'colab',
        'esExclusivo'       => 'exclusivo',
        'paraDescarga'      => 'descarga',
        'rola'              => 'music',
        'fan'               => 'fan',
        'artista'           => 'artista',
        'individual'        => 'individual',
        'multiple'          => 'multiple',
        'tienda'            => 'tienda',
        'momento'           => 'momento'
    ];

    foreach ($camposMeta as $claveMeta => $clavePost) {
        $valor = (isset($_POST[$clavePost]) && $_POST[$clavePost] == '1') ? 1 : 0;
        if (update_post_meta($idPost, $claveMeta, $valor) === false) {
        }
    }

    if (isset($_POST['nombreLanzamiento'])) {
        $nombreLanzamiento = sanitize_text_field($_POST['nombreLanzamiento']);
        if (update_post_meta($idPost, 'nombreLanzamiento', $nombreLanzamiento) === false) {
        }
    }

    if (isset($_POST['music']) && $_POST['music'] == '1') {
        registrarNombreRolas($idPost);
    }
    if (isset($_POST['tienda']) && $_POST['tienda'] == '1') {
        registrarPrecios($idPost);
    }
}

#Registra el nombre de las rolas
function registrarNombreRolas($idPost)
{
    for ($i = 1; $i <= 30; $i++) {
        $claveRola = 'nombreRola' . $i;
        if (isset($_POST[$claveRola])) {
            $nombreRola = sanitize_text_field($_POST[$claveRola]);
            if (update_post_meta($idPost, $claveRola, $nombreRola) === false) {
                error_log("Error en registrarNombreRolas: Fallo al actualizar el meta $claveRola para el post ID $idPost.");
            }
        }
    }
}

#Registra los precios de las rolas
function registrarPrecios($idPost)
{
    for ($i = 1; $i <= 30; $i++) {
        $clavePrecio = 'precioRola' . $i;
        if (isset($_POST[$clavePrecio])) {
            $precio = sanitize_text_field($_POST[$clavePrecio]);

            if (is_numeric($precio)) {
                if (update_post_meta($idPost, $clavePrecio, $precio) === false) {
                    error_log("Error en registrarPrecios: Fallo al actualizar el meta $clavePrecio para el post ID $idPost.");
                }
            } else {
                error_log("Error en registrarPrecios: El valor para $clavePrecio no es numerico. Post ID: $idPost, valor ingresado: " . $precio);
            }
        }
    }
}

// Refactor(Org): Función variablesPosts() movida a Post/PostDataService.php
// La función variablesPosts() ahora se encuentra en app/Services/Post/PostDataService.php
// Si necesitas usarla, asegúrate de que ese archivo esté incluido.
// Ejemplo de llamada (asumiendo que PostDataService.php está incluido):
// $vars = variablesPosts($postId);

