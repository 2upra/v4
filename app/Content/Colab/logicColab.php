<?php

// Refactor(Org): Funcion botonColab() movida a app/View/Helpers/UIHelper.php
// Refactor(Org): Funcion empezarColab() y su hook movidos a app/Services/ColabService.php


function actualizarEstadoColab($postId, $post_after, $post_before)
{
    if ($post_after->post_type === 'colab') {
        $post_origen_id = get_post_meta($postId, 'colabPostOrigen', true);
        $colaborador_id = get_post_meta($postId, 'colabColaborador', true);

        if ($post_after->post_status !== 'publish' && $post_after->post_status !== 'pending') {
            $existing_colabs_meta = get_post_meta($post_origen_id, 'colabs', true);

            if (($key = array_search($colaborador_id, $existing_colabs_meta)) !== false) {
                unset($existing_colabs_meta[$key]);
                $result = update_post_meta($post_origen_id, 'colabs', $existing_colabs_meta);
                if (!$result) {
                    guardarLog("Error al actualizar los metadatos de colaboración para el post origen ID: $post_origen_id");
                }
            }
        }
    }
}
add_action('post_updated', 'actualizarEstadoColab', 10, 3);

?>
