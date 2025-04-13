<?php 

// Refactor(Exec): Funcion calcularPuntosParaPost movida a app/Services/Post/PostScoringService.php

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
