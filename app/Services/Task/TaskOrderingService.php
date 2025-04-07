<?php
// Refactor(Org): Funcion ordenamientoTareas() movida desde app/Services/TaskService.php
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
