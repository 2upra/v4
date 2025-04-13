<?php


// Refactor(Org): Función asignarTags() movida a app/Services/Post/PostContentService.php


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

// Refactor(Org): Función variablesPosts() movida a app/Services/Post/PostDataService.php

// Refactor(Org): Funciones de consulta de posts (publicaciones, configuracionQueryArgs, preOrdenamiento, obtenerColeccionesParaMomento, ordenamientoColecciones, ordenamiento, aplicarFiltrosUsuario, prefiltrarIdentifier, procesarPublicaciones, obtenerUserId) movidas a app/Services/Post/PostQueryService.php

// Refactor(Org): Función movida desde app/AlgoritmoPost/algoritmoPosts.php
#Prepara los datos para el algoritmo
function datosParaAlgoritmo($idPost)
{
    $textoNormal = isset($_POST['textoNormal']) ? trim($_POST['textoNormal']) : '';
    $tagsString = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
    $tags = !empty($tagsString) ? array_map('trim', explode(',', $tagsString)) : [];

    $idAutor = get_post_field('post_author', $idPost);
    $datosAutor = get_userdata($idAutor);

    $nombreUsuario = $datosAutor ? $datosAutor->user_login : 'desconocido';
    $nombreMostrar = $datosAutor ? $datosAutor->display_name : 'Desconocido';

    $datosAlgoritmo = [
        'tags' => $tags,
        'texto' => $textoNormal,
        'autor' => [
            'id' => $idAutor,
            'usuario' => $nombreUsuario,
            'nombre' => $nombreMostrar,
        ],
    ];

    $datosAlgoritmoJson = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE);

    if ($datosAlgoritmoJson === false) {
        $mensajeErrorJson = str_replace("\n", " | ", json_last_error_msg());
        error_log("Error en datosParaAlgoritmo: Fallo al codificar JSON para el post ID: " . $idPost . ". Error: " . $mensajeErrorJson);
    } else {
        if (update_post_meta($idPost, 'datosAlgoritmo', $datosAlgoritmoJson) === false) {
            error_log("Error en datosParaAlgoritmo: Fallo al actualizar meta datosAlgoritmo para el post ID " . $idPost);
        }
    }
}
