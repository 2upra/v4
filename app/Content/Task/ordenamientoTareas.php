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
        $log .= "Nuevo orden calculado antes de verificar subtareas. IDs: " . implode(', ', $ordenFinal) . ".\n";

        // Reordenar subtareas debajo de sus padres respetando el orden existente
        $ordenFinalReordenado = [];
        $subtareasOrdenadas = []; 

        foreach ($ordenFinal as $tareaId) {
            $tarea = get_post($tareaId);

            if ($tarea->post_parent == 0) { // Si es una tarea padre
                $ordenFinalReordenado[] = $tareaId;

                // Buscar subtareas de esta tarea
                $subtareas = get_children([
                    'post_parent' => $tareaId,
                    'post_type'   => 'tarea',
                    'fields'      => 'ids'
                ]);
                
                if (!empty($subtareas)) {
                    $log .= "Subtareas encontradas para la tarea $tareaId: " . implode(', ', $subtareas) . ".\n";

                    $subtareasExistentes = array_intersect($ordenFinal, $subtareas); //Subtareas en el orden actual
                    $log .= "Subtareas existentes para la tarea $tareaId: " . implode(', ', $subtareasExistentes) . ".\n";

                    foreach ($subtareasExistentes as $subtareaId) {
                         $ordenFinalReordenado[] = $subtareaId;
                         $subtareasOrdenadas[] = $subtareaId;
                         $log .= "Subtarea $subtareaId agregada al orden después de la tarea padre $tareaId.\n";
                    }

                    $subtareasFaltantes = array_diff($subtareas, $subtareasExistentes); //Subtareas nuevas o que no estan el orden actual
                    $log .= "Subtareas nuevas para la tarea $tareaId: " . implode(', ', $subtareasFaltantes) . ".\n";

                    foreach($subtareasFaltantes as $subtareaId){
                         $ordenFinalReordenado[] = $subtareaId;
                         $log .= "Subtarea nueva $subtareaId agregada al orden después de la tarea padre $tareaId.\n";
                    }

                }
            } else if (!in_array($tareaId, $subtareasOrdenadas)) { 
                $log .= "Tarea $tareaId es una subtarea huerfana. \n";
                $ordenFinalReordenado[] = $tareaId;
            }
        }
        $log .= "Orden final después de reordenar subtareas. IDs: " . implode(', ', $ordenFinalReordenado) . ".\n";

        if ($ordenFinalReordenado !== $orden) {
            $log .= "Se actualizó el orden de las tareas.\n";
            $log .= ", \n  Se actualizaron las IDs de ordenTareas para el usuario $usu";
            update_user_meta($usu, 'ordenTareas', $ordenFinalReordenado);
        } else {
            $log .= "El orden actual coincide con el orden calculado. No se realizaron cambios.\n";
        }

        $queryArgs['post__in'] = $ordenFinalReordenado;
        $queryArgs['orderby'] = 'post__in';
    } else {
        return $queryArgs;
    }

    guardarLog($log);
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
