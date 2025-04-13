<?php

// Refactor(Org): Funcion calcularPuntosIntereses movida a app/Services/Post/PostScoringService.php

function calcularPuntosFinales($pUsuario, $pIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin)
{

    if ($esAdmin) {

        if (!$metaVerificado && $metaPostAut) {
            return ($pUsuario + $pIntereses + $puntosLikes) * 1;
        } elseif ($metaVerificado && !$metaPostAut) {
            return ($pUsuario + $pIntereses + $puntosLikes) * 1;
        }
    } else {
        if ($metaVerificado && $metaPostAut) {
            return ($pUsuario + $pIntereses + $puntosLikes) * 4;
        } elseif (!$metaVerificado && $metaPostAut) {
            return ($pUsuario + $pIntereses + $puntosLikes) * 1;
        }
    }

    return $pUsuario + $pIntereses + $puntosLikes;
}

// Funcion getDecayFactor movida a app/Utils/MathUtils.php

// Refactor(Org): Funciones calcularPuntosIdentifier, calcularPuntosSimilarTo, extractWordsFromDatosAlgoritmo, extractWordsFromContent movidas a app/Services/Post/PostScoringService.php

// Funcion procesarMetaValue movida a app/Utils/ArrayUtils.php (luego a app/Utils/MetaUtils.php)

// Funcion stemWord movida a app/Utils/StringUtils.php


#PASO 5

function obtenerYProcesarVistasPosts($userId)
{
    $vistas_posts = obtenerVistasPosts($userId);
    $vistasPosts = [];

    if (!empty($vistas_posts)) {
        foreach ($vistas_posts as $postId => $view_data) {
            $vistasPosts[$postId] = [
                'count'     => $view_data['count'],
                'last_view' => date('Y-m-d H:i:s', $view_data['last_view']),
            ];
        }
    }

    return $vistasPosts;
}

// Refactor(Org): Función movida desde app/Services/PostService.php
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
