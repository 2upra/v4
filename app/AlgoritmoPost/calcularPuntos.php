<?php 

function calcularPuntosParaPost(
    $postId,
    $post_data,
    $datos,
    $esAdmin,
    $vistasPosts,
    $identifier,
    $similarTo,
    $actualTimestamp,
    $decaimientoF,
    $tipoUsuario = null
) {
    $autorId = $post_data->post_author;
    $postDate = $post_data->post_date;

    $postTimestamp = is_string($postDate) ? strtotime($postDate) : $postDate;

    $diasPubli = floor(($actualTimestamp - $postTimestamp) / (3600 * 24));
    $factorTiempo = $decaimientoF[$diasPubli] ?? getDecayFactor($diasPubli);

    // Calculate puntosUsuario
    $siguiendo = $datos['siguiendo'];
    $pUsuario = in_array($autorId, $siguiendo) ? 20 : 0;

    $pIntereses = calcularPuntosIntereses($postId, $datos);

    // Calculate puntosIdentifier
    $pIdentifier = 0;
    if (!empty($identifier)) {
        $pIdentifier = calcularPuntosIdentifier($postId, $identifier, $datos);
    }
    $pesoIdentifier = 1.0;
    $pIdentifier *= $pesoIdentifier;

    // Calculate puntosSimilarTo
    $pSimilarTo = 0;
    if (!empty($similarTo)) {
        $pSimilarTo = calcularPuntosSimilarTo($postId, $similarTo, $datos);
    }

    $likesPorPost = $datos['likes_by_post'];

    if (!is_array($likesPorPost)) {
        $likesPorPost = [];
        guardarLog("likesPorPost no era un array, se ha forzado a serlo, en calcularPuntosParaPost. postId: $postId");
    }

    $likesData = $likesPorPost[$postId] ?? ['like' => 0, 'favorito' => 0, 'no_me_gusta' => 0];
    $puntosLikes = 5 + $likesData['like'] + 10 * $likesData['favorito'] - ($likesData['no_me_gusta'] * 10);

    // Access meta data
    $meta_data = $datos['meta_data'];
    $metaVerificado = false;
    $metaPostAut = false;

    if (isset($meta_data[$postId]['Verificado'])) {
        $metaVerificado = ($meta_data[$postId]['Verificado'] === '1');
    }
    if (isset($meta_data[$postId]['postAut'])) {
        $metaPostAut = ($meta_data[$postId]['postAut'] === '1');
    }

    $meta_roles = $datos['meta_roles'];

    if (!isset($meta_roles[$postId]) || !is_array($meta_roles[$postId])) {
        $meta_roles[$postId] = ['artista' => false, 'fan' => false];
    }

    $pArtistaFan = 0;

    if (empty($similarTo)) {
        $postParaArtistas = !empty($meta_roles[$postId]['artista']);
        $postParaFans = !empty($meta_roles[$postId]['fan']);

        if ($tipoUsuario === 'Fan') {
            $pArtistaFan = $postParaFans ? 999 : 0;
        } elseif ($tipoUsuario === 'Artista') {
            $pArtistaFan = $postParaFans ? -50 : 0;
        }
    }

    // Calculate puntosFinal
    $pFinal = calcularPuntosFinales(
        $pUsuario,
        $pIntereses + $pSimilarTo + $pArtistaFan,
        $puntosLikes,
        $metaVerificado,
        $metaPostAut,
        $esAdmin
    );

    $pFinal += $pIdentifier;

    // Apply reduction based on views
    if (isset($vistasPosts[$postId])) {
        // Si el ID del post actual existe en el array $vistasPosts, se obtiene el numero de vistas.
        $v = $vistasPosts[$postId]['count'];

        // Se calcula la reduccion de puntos en funcion del numero de vistas.
        $rPuntos = $v * 10;

        // Se resta la reduccion de puntos al puntaje final.
        $pFinal -= $rPuntos;
    }

    // Adjust randomness outside tight loops if possible
    $aleatoriedad = mt_rand(0, 20);
    $ajusteExtra = mt_rand(-50, 50);
    $pFinal = ($pFinal * (1 + ($aleatoriedad / 100))) * $factorTiempo;
    $pFinal += $ajusteExtra;

    return $pFinal;
}

// Refactor(Org): Función movida desde app/Content/Logic/procesarIdeas.php
function asignarPuntuacionPorVistas($post_ids) {
    error_log("[asignarPuntuacionPorVistas] Iniciando asignación de puntuaciones para los posts: (oculto)");
    $user_id = get_current_user_id();
    error_log("[asignarPuntuacionPorVistas] Obteniendo vistas del usuario ID: " . $user_id);
    $vistas_usuario = get_user_meta($user_id, 'vistas_posts', true);
    $post_scores = [];
    
    if(!$vistas_usuario){
        $vistas_usuario = [];
        error_log("[asignarPuntuacionPorVistas] El usuario no tiene vistas guardadas, se inicializa array vacio");
    } else {
        error_log("[asignarPuntuacionPorVistas] Vistas del usuario obtenidas: (oculto)");
    }
    
    foreach ($post_ids as $post_id) {
        $score = 0;
        if (isset($vistas_usuario[$post_id])) {
            $score = 1 / (1 + $vistas_usuario[$post_id]['count']);
             error_log("[asignarPuntuacionPorVistas] Post ID: $post_id tiene vistas. Puntuación: " . $score);
        } else {
             $score = 2;
              error_log("[asignarPuntuacionPorVistas] Post ID: $post_id no tiene vistas. Puntuación: " . $score);
        }
        
        $post_scores[$post_id] = $score;
    }
    
    arsort($post_scores);
    error_log("[asignarPuntuacionPorVistas] Puntuaciones asignadas y ordenadas: (oculto)");
    return $post_scores;
}
