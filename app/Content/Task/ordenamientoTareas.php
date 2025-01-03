<?

//aqui necesito que ordene las tareas bien, creo que falla al identificar sesiones o estado, no tiene que diferenciar entre minisculas o mayusculas, y la sescion archivado, es equivalante a el estado archivado
function ordenamientoTareas($queryArgs, $usu, $args)
{
    $orden = get_user_meta($usu, 'ordenTareas', true);
    $log = "Funcion ordenamientoTareas \n";
    if (!is_array($orden)) {
        $orden = [];
    }

    $todasTareasArgs = [
        'post_type'      => 'tarea',
        'author'         => $usu,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    $todasTareas = get_posts($todasTareasArgs);

    if (!empty($todasTareas) && is_array($todasTareas)) {
        $nuevasTareas = array_diff($todasTareas, $orden);

        if (!empty($nuevasTareas)) {
            $orden = array_merge($nuevasTareas, $orden);
        }

        $incompletas = [];
        $completadas = [];
        $archivadas = [];
        $gruposSesion = [];

        foreach ($orden as $id) {
            $post = get_post($id);

            if (!empty($post) && $post->post_status === 'publish') {
                $estado = strtolower(get_post_meta($id, 'estado', true));
                $sesion = strtolower(get_post_meta($id, 'sesion', true));

                if ($estado === 'completada') {
                    $completadas[] = $id;
                } elseif ($estado === 'archivado') {
                    $archivadas[] = $id;
                } else {
                    if (!empty($sesion)) {
                        $gruposSesion[$sesion][] = $id;
                    } else {
                        $incompletas[] = $id;
                    }
                }
            } else {
                if (!empty($post)) {
                    $estado = strtolower(get_post_meta($id, 'estado', true));
                    if ($estado === 'archivado') {
                        $archivadas[] = $id;
                    }
                }
                $log .= "La tarea con ID $id no existe o no está publicada. \n";
            }
        }

        $ordenIncompletas = [];
        foreach ($gruposSesion as $sesion => $tareas) {
            $ordenIncompletas = array_merge($ordenIncompletas, $tareas);
        }

        $ordenIncompletas = array_merge($ordenIncompletas, $incompletas);
        $ordenFinal = array_merge($ordenIncompletas, $completadas, $archivadas);

        if ($ordenFinal !== $orden) {
            $log .= "Se actualizó el orden de las tareas. \n";
            update_user_meta($usu, 'ordenTareas', $ordenFinal);
            $orden = $ordenFinal;
        }

        $queryArgs['post__in'] = $orden;
        $queryArgs['orderby'] = 'post__in';
        $log .= "Retornando \$queryArgs.";
        guardarLog($log);
    }

    return $queryArgs;
}


function ordenamientoTareasPorPrioridad($queryArgs, $usu)
{
    global $wpdb;
    $tareasPend = [];
    $tareasNoPend = [];
    $ordenActual = get_user_meta($usu, 'ordenTareas', true);
    $log = "ordenamientoTareasPorPrioridad, \n ";
    $todasTareasArgs = [
        'post_type'      => 'tarea',
        'author'         => $usu,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $todasTareas = get_posts($todasTareasArgs);

    if (empty($todasTareas) || !is_array($todasTareas)) {
        $log .= "No se encontraron tareas para el usuario $usu";
        guardarLog($log);
        return $queryArgs;
    }

    foreach ($todasTareas as $tareaId) {
        $estado = get_post_meta($tareaId, 'estado', true);
        if ($estado === 'pendiente') {
            $tareasPend[] = $tareaId;
        } else {
            $tareasNoPend[] = $tareaId;
        }
    }

    if (!is_array($ordenActual)) {
        $ordenActual = [];
    }

    $tareasPendOrdenadas = [];
    foreach ($ordenActual as $tareaId) {
        if (in_array($tareaId, $tareasPend)) {
            $tareasPendOrdenadas[] = $tareaId;
        }
    }

    $tareasPendNoOrdenadas = array_diff($tareasPend, $tareasPendOrdenadas);

    usort($tareasPendNoOrdenadas, function ($a, $b) use ($wpdb) {
        $impnumA = get_post_meta($a, 'impnum', true);
        $impnumB = get_post_meta($b, 'impnum', true);
        return $impnumB - $impnumA;
    });

    $tareasPend = array_merge($tareasPendOrdenadas, $tareasPendNoOrdenadas);

    $tareasOrd = array_merge($tareasPend, $tareasNoPend);

    update_user_meta($usu, 'ordenTareas', $tareasOrd);
    $log .= "Se actualizaron las IDs de ordenTareas para el usuario $usu";
    guardarLog($log);

    $queryArgs['post__in'] = $tareasOrd;
    $queryArgs['orderby'] = 'post__in';
    unset($queryArgs['meta_key']);
    unset($queryArgs['meta_query']);
    unset($queryArgs['order']);
    unset($queryArgs['meta_key']);
    unset($queryArgs['orderby']);

    return $queryArgs;
}
