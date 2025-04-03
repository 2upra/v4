<?php

// Funcion guardarVista() movida desde app/Utils/AnalyticsUtils.php

/**
 * Guarda la vista de un post para un usuario y actualiza las vistas totales del post.
 * Se activa mediante AJAX.
 */
function guardarVista()
{
    if (isset($_POST['id_post'])) {
        $idPost = intval($_POST['id_post']);
        $userId = get_current_user_id();

        // Lógica para usuarios logueados
        if ($userId) {
            $vistasUsuario = get_user_meta($userId, 'vistas_posts', true);
            $vistasTotalesUsuario = get_user_meta($userId, 'vistas_totales_usuario', true);

            if (!$vistasUsuario) {
                $vistasUsuario = array();
            }

            if (!$vistasTotalesUsuario) {
                $vistasTotalesUsuario = 0;
            }

            $fechaActual = time();

            // Actualiza o crea la entrada de vista para este post
            if (isset($vistasUsuario[$idPost])) {
                $vistasUsuario[$idPost]['count']++;
                $vistasUsuario[$idPost]['last_view'] = $fechaActual;
            } else {
                $vistasUsuario[$idPost] = array(
                    'count' => 1,
                    'last_view' => $fechaActual,
                );
            }

            $vistasTotalesUsuario++;

            // Guarda los metadatos del usuario
            update_user_meta($userId, 'vistas_posts', $vistasUsuario);
            update_user_meta($userId, 'vistas_totales_usuario', $vistasTotalesUsuario);

            // Reinicia el feed si se alcanza un múltiplo de 6 vistas totales del usuario
            // Nota: La función reiniciarFeed() debe estar definida globalmente o incluida.
            if (function_exists('reiniciarFeed') && $vistasTotalesUsuario % 6 === 0) {
                reiniciarFeed($userId);
            }
        }

        // Lógica para vistas totales del post (independiente del usuario)
        $vistaTotales = get_post_meta($idPost, 'vistas_totales', true);

        if (!$vistaTotales) {
            $vistaTotales = 0;
        }

        $vistaTotales++;

        // Guarda las vistas totales del post
        update_post_meta($idPost, 'vistas_totales', $vistaTotales);

        // Prepara la respuesta JSON
        $responseData = array(
            'vistas_totales' => $vistaTotales
        );
        if ($userId && isset($vistasUsuario[$idPost])) {
             $responseData['vistas_usuario'] = $vistasUsuario[$idPost]['count'];
        }


        wp_send_json_success($responseData);
    }

    // Termina la ejecución de WordPress para peticiones AJAX
    wp_die();
}

// TODO: Mover o actualizar los add_action('wp_ajax_guardar_vistas', ...) 
// TODO: y add_action('wp_ajax_nopriv_guardar_vistas', ...) 
// TODO: para que apunten a esta función en su nueva ubicación.
