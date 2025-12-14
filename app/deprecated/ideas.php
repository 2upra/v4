<?php

/**
 * Wrappers deprecados para funciones de ideas.
 * 
 * Este archivo proporciona compatibilidad con código legacy.
 * Todas las funciones aquí están DEPRECADAS y serán eliminadas en futuras versiones.
 * 
 * @deprecated Usar Kamples\Services\IdeaService en su lugar
 * @since 1.0.0
 */

use Kamples\Services\IdeaService;

/**
 * Maneja la obtención de ideas para una colección.
 *
 * @deprecated Usar IdeaService::obtenerInstancia()->manejar()
 * @param array $args Argumentos (debe incluir 'colec' y 'post_type')
 * @param int $paged Número de página
 * @return array|false Query args configurados o false si falla
 */
function manejarIdea($args, $paged)
{
    $ideaService = IdeaService::obtenerInstancia();
    return $ideaService->manejar($args, (int)$paged);
}

/**
 * Procesa las ideas para una colección.
 *
 * @deprecated Usar IdeaService::obtenerInstancia()->procesar()
 * @param array $args Argumentos de la query
 * @param int $paged Número de página
 * @return array|false Query args o false si falla
 */
function procesarIdeas($args, $paged)
{
    $ideaService = IdeaService::obtenerInstancia();
    return $ideaService->procesar($args, (int)$paged);
}

/**
 * Asigna puntuación a los posts basándose en las vistas del usuario.
 *
 * @deprecated Función interna de IdeaService
 * @param array $post_ids IDs de los posts
 * @return array Posts con puntuación [post_id => score]
 */
function asignarPuntuacionPorVistas($post_ids)
{
    /* 
     * Esta función era privada en la refactorización.
     * Se mantiene para compatibilidad pero usa la lógica original.
     */
    $user_id = get_current_user_id();
    $vistas_usuario = get_user_meta($user_id, 'vistas_posts', true);
    $post_scores = [];

    if (!$vistas_usuario || !is_array($vistas_usuario)) {
        $vistas_usuario = [];
    }

    foreach ($post_ids as $post_id) {
        if (isset($vistas_usuario[$post_id])) {
            $score = 1 / (1 + $vistas_usuario[$post_id]['count']);
        } else {
            $score = 2;
        }

        $post_scores[$post_id] = $score;
    }

    arsort($post_scores);
    return $post_scores;
}
