<?

# Permite la descarga de un post.
function permitirDescarga($idPost)
{
    $resultado = update_post_meta($idPost, 'paraDescarga', true);
    if (false === $resultado) {
        error_log("permitirDescarga Error: Fallo al actualizar post meta para ID $idPost.");
    } elseif (0 === $resultado) {
        // No hubo error, pero el meta valor era el mismo y no se actualizó, o el post no existe.
        // Esto puede ser normal o indicar un problema según el caso.
        error_log("permitirDescarga Info: No se actualizó post meta para ID $idPost (podría no existir o valor sin cambios).");
    } else {
        error_log("permitirDescarga Info: Descarga permitida para post ID $idPost.");
    }
    return wp_send_json_success(['message' => 'Descarga permitida']);
}

# Comprueba el número de colaboraciones publicadas por un usuario.
function comprobarColaboracionesUsuario($idUsuario)
{
    $args = [
        'author'         => $idUsuario,
        'post_status'    => 'publish',
        'post_type'      => 'colab',
        'posts_per_page' => -1,
    ];

    $query = new WP_Query($args);
    $postsEncontrados = $query->found_posts;
    error_log("comprobarColaboracionesUsuario Info: Usuario ID $idUsuario tiene $postsEncontrados colaboraciones publicadas.");
    return $postsEncontrados;
}

# Cambia el estado de un post.
function cambiarEstado($idPost, $nuevoEstado)
{
    $post = get_post($idPost);
    if (!$post) {
        error_log("cambiarEstado Error: Post ID $idPost no encontrado.");
        return wp_send_json_error(['message' => 'Post no encontrado']);
    }

    $tipoPost = $post->post_type;
    $estadoAnterior = $post->post_status;

    // Inicio de la lógica específica para TAREAS y pending_deletion
    if ($tipoPost === 'tarea' && $nuevoEstado === 'pending_deletion') {
        error_log("cambiarEstado Info: Solicitud de borrado para TAREA ID $idPost (estado anterior: $estadoAnterior). Procediendo a borrar con sus subtareas.");
        
        // La acción 'eliminarpostrs' lleva a 'pending_deletion'.
        // Aquí, en lugar de solo cambiar el estado, eliminamos el post y sus hijos.
        $resultadoBorrado = wp_delete_post($idPost, true); // true para forzar borrado y eliminar hijos

        if (false === $resultadoBorrado || null === $resultadoBorrado) {
            error_log("cambiarEstado Error: wp_delete_post falló para TAREA ID $idPost.");
            return wp_send_json_error(['message' => 'Error al eliminar la tarea y sus subtareas.']);
        }
        
        error_log("cambiarEstado Info: TAREA ID $idPost y sus subtareas (si las tuvo) eliminadas permanentemente.");
        // Si el borrado fue exitoso, enviamos un mensaje acorde.
        // El 'new_status' podría ser algo como 'deleted' para reflejar la acción.
        return wp_send_json_success(['message' => 'Tarea y subtareas eliminadas.', 'new_status' => 'deleted_permanently', 'post_id' => $idPost]);
    }
    // Fin de la lógica específica para TAREAS

    // Lógica original para otros tipos de post o tareas que no van a 'pending_deletion'
    // (o si $tipoPost no es 'tarea' pero $nuevoEstado es 'pending_deletion')
    $datosActualizacion = ['ID' => $idPost, 'post_status' => $nuevoEstado]; // Usar un array para wp_update_post
    $resultado = wp_update_post($datosActualizacion, true); // Pasar true para obtener WP_Error en caso de fallo

    if (is_wp_error($resultado)) {
        error_log("cambiarEstado Error: Fallo al actualizar post ID $idPost a estado '$nuevoEstado'. Error: " . $resultado->get_error_message());
        return wp_send_json_error(['message' => 'Error al actualizar el post', 'error' => $resultado->get_error_message()]);
    }
    
    // wp_update_post devuelve 0 si no hay cambios o si el post no existe, ID del post si es exitoso.
    // La comprobación de is_wp_error es más robusta.
    // Si $resultado es 0 pero no es un WP_Error, y el estado sí debía cambiar, puede ser un problema.
    if (0 === $resultado && $estadoAnterior !== $nuevoEstado) { 
        error_log("cambiarEstado Advertencia: wp_update_post devolvió 0 para post ID $idPost intentando cambiar estado de '$estadoAnterior' a '$nuevoEstado'. Verificar si el post existe y es editable.");
        // Podrías considerar esto un error si se esperaba un cambio
        return wp_send_json_error(['message' => 'Error: No se pudo actualizar el estado del post (ID 0 devuelto).']);
    }
    if (0 === $resultado && $estadoAnterior === $nuevoEstado) {
        error_log("cambiarEstado Info: No hubo cambios en el estado del post ID $idPost (ya estaba en '$nuevoEstado').");
        // Devolver éxito, ya que el estado deseado ya está aplicado.
        return wp_send_json_success(['new_status' => $nuevoEstado, 'message' => 'El post ya se encontraba en el estado solicitado.']);
    }


    error_log("cambiarEstado Info: Post ID $idPost (tipo: $tipoPost) cambiado de estado '$estadoAnterior' a '$nuevoEstado'.");
    return wp_send_json_success(['new_status' => $nuevoEstado, 'post_id' => $idPost]);
}

# Maneja los cambios de estado de los posts a través de AJAX.
function cambioDeEstado()
{
    if (!isset($_POST['post_id'])) {
        error_log("cambioDeEstado Error: Falta 'post_id' en la solicitud.");
        return wp_send_json_error(['message' => 'Falta el ID del post']);
    }

    $idPost = intval($_POST['post_id']);
    // $_POST['action'] aquí es el nombre de la acción del hook wp_ajax_...
    $accion = isset($_POST['action']) ? sanitize_key($_POST['action']) : 'accion_desconocida';
    $currentStatus = isset($_POST['current_status']) ? sanitize_text_field($_POST['current_status']) : null;
    $idUsuarioActual = get_current_user_id();

    error_log("cambioDeEstado Info: Solicitud recibida. Post ID: $idPost, Acción: $accion, Usuario ID: $idUsuarioActual, Estado Actual (si aplica): $currentStatus.");

    if ($accion === 'aceptarcolab') { // 'aceptarcolab' ya es el resultado de sanitize_key('aceptarcolab')
        $colaboracionesPublicadas = comprobarColaboracionesUsuario($idUsuarioActual);
        error_log("cambioDeEstado Info (aceptarcolab): Usuario ID $idUsuarioActual tiene $colaboracionesPublicadas colaboraciones.");
        if ($colaboracionesPublicadas >= 3) {
            error_log("cambioDeEstado Alerta (aceptarcolab): Usuario ID $idUsuarioActual excedió límite de colaboraciones ($colaboracionesPublicadas >= 3).");
            return wp_send_json_error(['message' => 'Ya tienes 3 colaboraciones en curso. Debes finalizar una para aceptar otra.']);
        }
    }

    // Las claves aquí deben coincidir con el resultado de sanitize_key() aplicado al nombre del hook AJAX
    $estados = [
        'toggle_post_status'    => ($currentStatus == 'pending') ? 'publish' : 'pending',
        'reject_post'           => 'rejected',
        'request_post_deletion' => 'pending_deletion',
        'eliminarpostrs'        => 'pending_deletion', // CAMBIO: 'eliminarPostRs' a 'eliminarpostrs'
        'rechazarcolab'         => 'pending_deletion',
        'aceptarcolab'          => 'publish',
    ];

    // CAMBIO: 'permitirDescarga' a 'permitirdescarga' para que coincida con sanitize_key('permitirDescarga')
    if ($accion === 'permitirdescarga') {
        error_log("cambioDeEstado Info: Ejecutando permitirDescarga para post ID $idPost.");
        wp_send_json(permitirDescarga($idPost));
    } elseif (isset($estados[$accion])) {
        $nuevoEstado = $estados[$accion];
        error_log("cambioDeEstado Info: Ejecutando cambiarEstado para post ID $idPost a nuevo estado '$nuevoEstado' por acción '$accion'.");
        wp_send_json(cambiarEstado($idPost, $nuevoEstado));
    } else {
        error_log("cambioDeEstado Error: Acción '$accion' inválida para post ID $idPost.");
        return wp_send_json_error(['message' => 'Acción inválida']);
    }

    wp_die();
}

# Verifica un post por un administrador.
function verificarPost()
{
    if (!isset($_POST['post_id'])) {
        error_log("verificarPost Error: Falta 'post_id' en la solicitud.");
        return wp_send_json_error(['message' => 'Falta el ID del post']);
    }

    $idPost = intval($_POST['post_id']);
    $usuarioActual = wp_get_current_user();

    error_log("verificarPost Info: Solicitud para verificar post ID $idPost por usuario ID {$usuarioActual->ID} ({$usuarioActual->user_login}).");

    if (!user_can($usuarioActual, 'administrator')) {
        error_log("verificarPost Alerta: Usuario ID {$usuarioActual->ID} sin permisos de administrador para verificar post ID $idPost.");
        return wp_send_json_error(['message' => 'No tienes permisos para verificar este post']);
    }

    $resultado = update_post_meta($idPost, 'Verificado', true);
    if (false === $resultado) {
        error_log("verificarPost Error: Fallo al actualizar post meta 'Verificado' para ID $idPost.");
    } elseif (0 === $resultado) {
        error_log("verificarPost Info: No se actualizó post meta 'Verificado' para ID $idPost (podría no existir o valor sin cambios).");
    } else {
        error_log("verificarPost Info: Post ID $idPost verificado por usuario ID {$usuarioActual->ID}.");
    }

    return wp_send_json_success(['message' => 'Post verificado correctamente']);
    wp_die();
}

add_action('wp_ajax_verificarPost', 'verificarPost');
add_action('wp_ajax_permitirDescarga', 'cambioDeEstado');
add_action('wp_ajax_aceptarcolab', 'cambioDeEstado');
add_action('wp_ajax_rechazarcolab', 'cambioDeEstado');
add_action('wp_ajax_toggle_post_status', 'cambioDeEstado');
add_action('wp_ajax_reject_post', 'cambioDeEstado');
add_action('wp_ajax_request_post_deletion', 'cambioDeEstado');
add_action('wp_ajax_eliminarPostRs', 'cambioDeEstado');

// Refactor(Org): Funcion actualizarEstadoColab() y su hook movidos desde app/Content/Colab/logicColab.php
function actualizarEstadoColab($postId, $post_after, $post_before)
{
    if ($post_after->post_type === 'colab') {
        $idPostOrigen = get_post_meta($postId, 'colabPostOrigen', true);
        $idColaborador = get_post_meta($postId, 'colabColaborador', true);

        error_log("actualizarEstadoColab Info: Procesando post ID $postId (tipo: {$post_after->post_type}). Post Origen ID: $idPostOrigen, Colaborador ID: $idColaborador. Estado Antes: {$post_before->post_status}, Estado Después: {$post_after->post_status}.");

        if ($post_after->post_status !== 'publish' && $post_after->post_status !== 'pending') {
            $metasColabsExistentes = get_post_meta($idPostOrigen, 'colabs', true);

            if (!is_array($metasColabsExistentes)) { // Asegurarse de que es un array para array_search
                $metasColabsExistentes = [];
                error_log("actualizarEstadoColab Alerta: 'colabs' meta no era un array para post origen ID $idPostOrigen. Se inicializó como array vacío.");
            }

            if (!empty($idPostOrigen) && !empty($idColaborador)) { // Solo proceder si tenemos IDs válidos
                $key = array_search($idColaborador, $metasColabsExistentes);

                if ($key !== false) {
                    unset($metasColabsExistentes[$key]);
                    // Reindexar array para evitar problemas con JSON si es necesario (opcional, pero buena práctica)
                    $metasColabsExistentes = array_values($metasColabsExistentes);
                    $resultado = update_post_meta($idPostOrigen, 'colabs', $metasColabsExistentes);
                    if (!$resultado) {
                        error_log("actualizarEstadoColab Error: Fallo al actualizar meta 'colabs' para post origen ID $idPostOrigen tras remover colaborador ID $idColaborador.");
                    } else {
                        error_log("actualizarEstadoColab Info: Colaborador ID $idColaborador removido de 'colabs' para post origen ID $idPostOrigen.");
                    }
                } else {
                    error_log("actualizarEstadoColab Info: Colaborador ID $idColaborador no encontrado en 'colabs' de post origen ID $idPostOrigen para remover.");
                }
            } else {
                error_log("actualizarEstadoColab Alerta: idPostOrigen ($idPostOrigen) o idColaborador ($idColaborador) vacío para post ID $postId. No se actualizó meta 'colabs'.");
            }
        } else {
            error_log("actualizarEstadoColab Info: Post ID $postId (colab) cambió a '{$post_after->post_status}'. No se modificó meta 'colabs' del post origen.");
        }
    }
}
add_action('post_updated', 'actualizarEstadoColab', 10, 3);
