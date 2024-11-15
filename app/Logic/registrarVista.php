<?

function guardarVista() {
    // Verificar que se haya pasado el ID del post
    if (isset($_POST['id_post'])) {
        $idPost = intval($_POST['id_post']);
        $userId = get_current_user_id(); // Obtener el ID del usuario actual

        if ($userId) {
            // Obtener las vistas del usuario almacenadas en su meta
            $vistasUsuario = get_user_meta($userId, 'vistas_posts', true);
            $vistasTotalesUsuario = get_user_meta($userId, 'vistas_totales_usuario', true); // Total de vistas del usuario

            // Si no tiene registros anteriores, inicializar el array
            if (!$vistasUsuario) {
                $vistasUsuario = array();
            }

            // Si no tiene un contador total de vistas, inicializarlo
            if (!$vistasTotalesUsuario) {
                $vistasTotalesUsuario = 0;
            }

            // Obtener la fecha actual
            $fechaActual = time(); // Timestamp actual

            // Limpiar vistas antiguas (más de 30 días)
            $vistasUsuario = limpiarVistasAntiguas($vistasUsuario, 7);

            // Incrementar o agregar la vista del post en la fecha actual
            if (isset($vistasUsuario[$idPost])) {
                $vistasUsuario[$idPost]['count']++; // Incrementar contador
                $vistasUsuario[$idPost]['last_view'] = $fechaActual; // Actualizar fecha de última vista
            } else {
                $vistasUsuario[$idPost] = array(
                    'count' => 1,
                    'last_view' => $fechaActual, // Registrar primera vista con la fecha actual
                );
            }

            // Incrementar el total de vistas del usuario
            $vistasTotalesUsuario++;

            // Guardar la información actualizada en la meta del usuario
            update_user_meta($userId, 'vistas_posts', $vistasUsuario);
            update_user_meta($userId, 'vistas_totales_usuario', $vistasTotalesUsuario);

            // Si el usuario ha alcanzado 5 vistas, reiniciamos el feed
            if ($vistasTotalesUsuario % 5 === 0) {
                reiniciarFeed($userId); // Reiniciar el feed del usuario
            }
        }

        // Obtener las vistas totales del post
        $vistaTotales = get_post_meta($idPost, 'vistas_totales', true);

        if (!$vistaTotales) {
            $vistaTotales = 0;
        }

        // Incrementar las vistas totales del post
        $vistaTotales++;

        // Guardar las vistas totales actualizadas en las post metas
        update_post_meta($idPost, 'vistas_totales', $vistaTotales);

        // Respuesta en formato JSON
        wp_send_json_success(array(
            'vistas_usuario' => $vistasUsuario[$idPost]['count'], // Vistas del usuario para este post
            'vistas_totales' => $vistaTotales // Vistas totales del post
        ));
    }

    // Finalizar la ejecución
    wp_die();
}

function obtenerVistasPosts($userId) {
    // Obtener la meta 'vistas_posts' del usuario
    $vistas_posts = get_user_meta($userId, 'vistas_posts', true);

    // Si no hay vistas almacenadas, devolver un array vacío
    if (empty($vistas_posts)) {
        return [];
    }
    $vistas_posts = limpiarVistasAntiguas($vistas_posts, 7); 

    return $vistas_posts;
}

// Registrar el handler de la acción AJAX para usuarios logueados y no logueados
add_action('wp_ajax_guardar_vistas', 'guardarVista');
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista');


// Función para limpiar vistas antiguas
function limpiarVistasAntiguas($vistas, $dias) {
    $fechaLimite = time() - (86400 * $dias); // Calcular la fecha límite (30 días)

    // Recorrer las vistas y eliminar las más antiguas de la fecha límite
    foreach ($vistas as $postId => $infoVista) {
        if ($infoVista['last_view'] < $fechaLimite) {
            unset($vistas[$postId]); // Eliminar la vista si es más antigua que la fecha límite
        }
    }

    return $vistas;
}
