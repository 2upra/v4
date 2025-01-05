<?

function ordenamientoTareas($queryArgs, $usu, $args, $prioridad = false)
{
    $ordenarServidor = false;

    $orden = get_user_meta($usu, 'ordenTareas', true);
    $log = "Funcion ordenamientoTareas \n";

    if (!is_array($orden)) {
        $orden = [];
    }

    if (!$ordenarServidor) {
        $log .= "Iniciando proceso de actualización de orden (ordenamiento desactivado).\n";
        $todasTareasArgs = [
            'post_type'      => 'tarea',
            'author'         => $usu,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];
        $todasTareas = get_posts($todasTareasArgs);
        $log .= "Se obtuvieron todas las tareas del usuario $usu. Total: " . count($todasTareas) . ".\n";

        $ordenValido = array_intersect($orden, $todasTareas);
        $log .= "IDs de orden coincidentes con las tareas del usuario: " . count($ordenValido) . ".\n";

        $faltantes = array_diff($todasTareas, $ordenValido);
        $log .= "IDs de tareas faltantes en el orden actual: " . count($faltantes) . ".\n";

        $ordenFinal = array_merge($ordenValido, $faltantes);
        $log .= "Nuevo orden calculado. IDs: " . implode(', ', $ordenFinal) . ".\n";

        if ($ordenFinal !== $orden) {
            $log .= "Se actualizó el orden de las tareas.\n";
            $log .= "Se actualizaron las IDs de ordenTareas para el usuario $usu";
            update_user_meta($usu, 'ordenTareas', $ordenFinal);
        } else {
            $log .= "El orden actual coincide con el orden calculado. No se realizaron cambios.\n";
        }
        //guardarLog("ordenamientoTareas: \n" . $log);

        $queryArgs['post__in'] = $ordenFinal;
        $queryArgs['orderby'] = 'post__in';
    } else {
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
                $orden = array_merge($orden, $nuevasTareas);
                update_user_meta($usu, 'ordenTareas', $orden);
                $log .= "Se agregaron nuevas tareas al orden. \n";
            }

            $incompletas = [];
            $completadas = [];
            $archivadas = [];
            $gruposSesion = [];

            foreach ($orden as $id) {
                $post = get_post($id);
                $estado = strtolower(get_post_meta($id, 'estado', true));
                $sesion = strtolower(get_post_meta($id, 'sesion', true));

                if ($post && $post->post_status === 'publish') {
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
                } else if ($post && $estado === 'archivado') {
                    $archivadas[] = $id;
                } else if ($post) {
                    $log .= "La tarea con ID $id no está publicada. \n";
                } else {
                    $log .= "La tarea con ID $id no existe. \n";
                }
            }

            $ordenFinal = [];

            foreach ($gruposSesion as $sesion => $tareas) {
                $ordenFinal = array_merge($ordenFinal, $tareas);
            }

            $ordenFinal = array_merge($ordenFinal, $incompletas, $completadas, $archivadas);

            if ($ordenFinal !== $orden) {
                $log .= "Se actualizó el orden de las tareas. \n,  Se actualizaron las IDs de ordenTareas para el usuario $usu";
                update_user_meta($usu, 'ordenTareas', $ordenFinal);
            }

            $queryArgs['post__in'] = $ordenFinal;
            $queryArgs['orderby'] = 'post__in';
        }
    }

    //$log .= "Retornando \$queryArgs.";
    guardarLog($log);
    return $queryArgs;
}

//esto funciona mal, no tiene que diferenciar entre mayuscola o miniscula el estado, creo
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
        $estado = strtolower(get_post_meta($tareaId, 'estado', true));
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
    unset($queryArgs['orderby']);


    return $queryArgs;
}
