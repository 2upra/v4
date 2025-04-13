<?php

// Refactor(Org): Funcion calcularPuntosIntereses movida a app/Services/Post/PostScoringService.php

// Refactor(Org): Funcion calcularPuntosFinales movida a app/Services/Post/PostScoringService.php

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

// Refactor(Org): Función datosParaAlgoritmo movida a app/Services/PostService.php
