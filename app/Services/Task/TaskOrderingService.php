<?php
# app/Services/Task/TaskOrderingService.php


function ordenamientoTareas($queryArgs, $usu, $args, $prioridad = false)
{
    $ordenarServidor = false;

    $ordenarServidor = false; // Esta variable no parece usarse activamente para cambiar la lógica.
    $log = "ordenamientoTareas usu: $usu";

    $ordenSecciones = get_user_meta($usu, 'ordenTareasSecciones', true);

    if (is_array($ordenSecciones) && !empty($ordenSecciones)) {
        $log .= ", usandoOrdenTareasSecciones";
        $ordenTaskIds = array_filter($ordenSecciones, 'is_numeric');
        $ordenTaskIds = array_map('intval', $ordenTaskIds);
        $ordenTaskIds = array_values($ordenTaskIds); // Re-indexar el array

        $log .= ", ordenTareasSeccionesMetaCant: " . count($ordenSecciones) . ", extraidosTaskIdsCant: " . count($ordenTaskIds);

        // Lógica de reconciliación existente usando $ordenTaskIds como base
        $todasTareasArgs = [
            'post_type'      => 'tarea',
            'author'         => $usu,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];
        $todasTareas = get_posts($todasTareasArgs);
        $log .= ", todasTareasEncontradasCant: " . count($todasTareas);

        $ordenValido = array_intersect($ordenTaskIds, $todasTareas);
        $log .= ", ordenValidoDeSeccionesCant: " . count($ordenValido);

        $faltantes = array_diff($todasTareas, $ordenValido);
        $log .= ", faltantesEnOrdenSeccionesCant: " . count($faltantes);

        $ordenFinal = array_merge($ordenValido, $faltantes);
        $log .= ", ordenConsolidadoDesdeSeccionesCant: " . count($ordenFinal);
    } else {
        $log .= ", fallbackAOrdenTareas";
        $orden = get_user_meta($usu, 'ordenTareas', true);
        if (!is_array($orden)) {
            $orden = [];
        }
        $log .= ", ordenMetaInicialCant: " . count($orden);

        $todasTareasArgs = [
            'post_type'      => 'tarea',
            'author'         => $usu,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];
        $todasTareas = get_posts($todasTareasArgs);
        $log .= ", todasTareasCant: " . count($todasTareas);

        $ordenValido = array_intersect($orden, $todasTareas);
        $log .= ", ordenValidoCant: " . count($ordenValido);

        $faltantes = array_diff($todasTareas, $ordenValido);
        $log .= ", faltantesEnOrdenCant: " . count($faltantes);

        $ordenFinal = array_merge($ordenValido, $faltantes);
        $log .= ", ordenConsolidadoCant: " . count($ordenFinal);
    }

    // Lógica de re-parenting de subtareas (común para ambos flujos)
    $ordenFinalReordenado = [];
    $subtareasOrdenadas = []; 

    foreach ($ordenFinal as $tareaId) {
        $tarea = get_post($tareaId);
        if (!$tarea) continue;

        if ($tarea->post_parent == 0) {
            if (in_array($tareaId, $ordenFinalReordenado)) continue;
            $ordenFinalReordenado[] = $tareaId;

            $subtareasArgs = [ // Definir argumentos para get_children
                'post_parent' => $tareaId,
                'post_type'   => 'tarea',
                'fields'      => 'ids',
                'posts_per_page' => -1,
                'orderby'     => 'menu_order', // Respetar el orden si existe
                'order'       => 'ASC',
            ];
            $subtareas = get_children($subtareasArgs);


            if (!empty($subtareas)) {
                $log .= ", padre $tareaId tieneSubtareas: " . implode(',', $subtareas);
                $subtareasExistentesEnOrden = array_intersect($ordenFinal, $subtareas);
                $subtareasNuevasOTras = array_diff($subtareas, $subtareasExistentesEnOrden);
                
                $subtareasParaAgregar = array_merge($subtareasExistentesEnOrden, $subtareasNuevasOTras);

                foreach ($subtareasParaAgregar as $subtareaId) {
                    if (!in_array($subtareaId, $ordenFinalReordenado)) {
                       $ordenFinalReordenado[] = $subtareaId;
                    }
                    $subtareasOrdenadas[] = $subtareaId;
                }
            }
        } else if (!in_array($tareaId, $subtareasOrdenadas)) {
            if (in_array($tareaId, $ordenFinalReordenado)) continue;
            $log .= ", subtareaHuerfanaOAdelantada $tareaId (padre {$tarea->post_parent})";
            $ordenFinalReordenado[] = $tareaId;
        }
    }
    $ordenFinalReordenado = array_values(array_unique($ordenFinalReordenado));
    $log .= ", ordenFinalReordenadoCant: " . count($ordenFinalReordenado);
    
    // Actualizar 'ordenTareas' con la lista de IDs de tareas reconciliada y reordenada.
    // Esto es importante si se usó 'ordenTareasSecciones' para asegurar que 'ordenTareas' sea coherente.
    $ordenTareasActual = get_user_meta($usu, 'ordenTareas', true) ?: [];
    if ($ordenFinalReordenado !== $ordenTareasActual || count($ordenFinalReordenado) !== count($ordenTareasActual)) {
        update_user_meta($usu, 'ordenTareas', $ordenFinalReordenado);
        $log .= ", ordenTareasMetaActualizado";
    } else {
        $log .= ", ordenTareasMetaNoCambio";
    }

    $queryArgs['post__in'] = !empty($ordenFinalReordenado) ? $ordenFinalReordenado : [0];
        $queryArgs['orderby'] = 'post__in';
    } else {
        // Esta rama no se ejecuta si $ordenarServidor siempre es false.
        // Si se implementara, debería retornar $queryArgs modificado o no.
        $log .= ", ordenamientoServidorActivoRetornandoOriginal";
        // return $queryArgs; // Comentado para que siempre se ejecute la lógica principal
    }

    guardarLog($log);
    return $queryArgs;
}


// Refactor(Org): Funcion ordenamientoTareasPorPrioridad() movida desde app/Services/TaskService.php
function ordenamientoTareasPorPrioridad($queryArgs, $usu)
{
    global $wpdb;
    $tareasPend = [];
    $tareasNoPend = [];
    $ordenActual = get_user_meta($usu, 'ordenTareas', true);
    $log = "ordenamientoTareasPorPrioridad usu: $usu, ";
    $todasTareasArgs = [
        'post_type'      => 'tarea',
        'author'         => $usu,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $todasTareas = get_posts($todasTareasArgs);

    if (empty($todasTareas) || !is_array($todasTareas)) {
        $log .= "noTareasParaUsuario";
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

    usort($tareasPendNoOrdenadas, function ($a, $b) { // $wpdb no es necesario aquí si impnum está en post_meta
        $impnumA = get_post_meta($a, 'impnum', true);
        $impnumB = get_post_meta($b, 'impnum', true);
        return (int)$impnumB - (int)$impnumA; // Castear a int por si son strings
    });

    $tareasPend = array_merge($tareasPendOrdenadas, $tareasPendNoOrdenadas);
    $tareasOrd = array_merge($tareasPend, $tareasNoPend);

    update_user_meta($usu, 'ordenTareas', $tareasOrd);
    $log .= "ordenTareasActualizadoCant: " . count($tareasOrd);
    guardarLog($log);

    $queryArgs['post__in'] = !empty($tareasOrd) ? $tareasOrd : [0]; // Evitar error con array vacío
    $queryArgs['orderby'] = 'post__in';
    unset($queryArgs['meta_key']); // Estos unsets aseguran que el orden por post__in prevalezca
    unset($queryArgs['meta_query']);
    // unset($queryArgs['order']); // Podría ser necesario mantener 'order' si 'orderby' es diferente de 'post__in' en otros contextos
    // unset($queryArgs['orderby']); // No hacer unset de orderby si ya lo hemos establecido a post__in

    return $queryArgs;
}




function actualizarOrden($ordenTar, $ordenNue) // $ordenTar (anterior) no se usa actualmente en esta función
{
    $usu = get_current_user_id();
    // Podrías loguear $ordenTar aquí si quisieras comparar el antes y el después en este log específico.
    $log = "actualizarOrden usu:$usu, ordenAnteriorCant:" . (is_array($ordenTar) ? count($ordenTar) : 'N/A');
    
    update_user_meta($usu, 'ordenTareas', $ordenNue);
    $log .= ", ordenNuevoGuardadoCant:" . count($ordenNue);

    guardarLog($log);
    return $ordenNue;
}



function actualizarOrdenTareasGrupo()
{
    $usu = get_current_user_id();
    $log = "actualizarOrdenTareasGrupo usu:$usu";

    // $tareasMovIdsInput = isset($_POST['tareasMovidas']) ? $_POST['tareasMovidas'] : null; // No longer primary input for order
    $ordenNueInput = isset($_POST['ordenNuevo']) ? $_POST['ordenNuevo'] : null; // This will be the mixed string "123,Work,456,Personal,789"

    $log .= ", ordenNueInputRaw:" . var_export($ordenNueInput, true);

    if (empty($ordenNueInput) || !is_string($ordenNueInput)) {
        $log .= ", error:ordenNueInputVacioONoEsString";
        guardarLog($log);
        wp_send_json_error(['error' => 'Datos de ordenNuevo insuficientes o en formato incorrecto.'], 400);
        return;
    }

    $ordenMixtoCrudo = explode(',', $ordenNueInput);
    $ordenMixtoProcesado = [];
    foreach ($ordenMixtoCrudo as $item) {
        if (is_numeric($item)) {
            $ordenMixtoProcesado[] = intval($item);
        } else {
            $ordenMixtoProcesado[] = sanitize_text_field(trim($item));
        }
    }
    
    $log .= ", ordenMixtoProcesadoCant:" . count($ordenMixtoProcesado) . ", items:" . implode('|', $ordenMixtoProcesado);

    // Save the mixed order to the new meta field
    update_user_meta($usu, 'ordenTareasSecciones', $ordenMixtoProcesado);
    $log .= ", ordenTareasSeccionesMetaActualizado";

    // New logic to update 'sesion' post meta for tasks
    $current_section_name = 'General'; // Default section if tasks appear before any divider
    $log .= ", iniciandoActualizacionSesionMeta";

    foreach ($ordenMixtoProcesado as $item) {
        if (is_string($item)) {
            $current_section_name = sanitize_text_field(trim($item));
            // Ensure section name is not empty, if it can be, use a default
            if (empty($current_section_name)) {
                $current_section_name = 'General'; // Fallback if a section string is empty
            }
            $log .= ", seccionActualCambiadaA:'$current_section_name'";
        } elseif (is_numeric($item)) {
            $task_id = intval($item);
            $existing_sesion = get_post_meta($task_id, 'sesion', true);
            // Normalize existing session: treat null, false, or empty string as 'General' for comparison,
            // but store the actual $current_section_name.
            $normalized_existing_sesion = ($existing_sesion === null || $existing_sesion === false || $existing_sesion === '') ? 'General' : $existing_sesion;

            // Only update if the new section is different from the existing one.
            // And ensure current_section_name is not empty (though it defaults to 'General' now).
            if ($normalized_existing_sesion !== $current_section_name) {
                update_post_meta($task_id, 'sesion', $current_section_name);
                $log .= ", tareaID:$task_id, sesionActualizadaDe:'$normalized_existing_sesion'A:'$current_section_name'";
            } else {
                $log .= ", tareaID:$task_id, sesionNoCambio:'$current_section_name'";
            }
        }
    }
    $log .= ", finActualizacionSesionMeta";

    // For compatibility, update 'ordenTareas' with only task IDs
    $taskIdsSolo = array_filter($ordenMixtoProcesado, 'is_numeric');
    $taskIdsSolo = array_map('intval', $taskIdsSolo);
    $taskIdsSolo = array_values($taskIdsSolo); // Re-index

    // $ordenTarMetaAnterior = get_user_meta($usu, 'ordenTareas', true) ?: []; // Not directly used by actualizarOrden anymore with this logic
    // actualizarOrden($ordenTarMetaAnterior, $taskIdsSolo); // Pass only task IDs to the old actualizarOrden
    update_user_meta($usu, 'ordenTareas', $taskIdsSolo); // Direct update for simplicity
    $log .= ", ordenTareasMetaActualizadoConTaskIdsCant:" . count($taskIdsSolo);
    
    // Verificar si el cambio fue efectivo (opcional, para log)
    $ordenSeccionesMetaPosterior = get_user_meta($usu, 'ordenTareasSecciones', true) ?: [];
    if ($ordenSeccionesMetaPosterior === $ordenMixtoProcesado) {
        $log .= ", exito:ordenTareasSeccionesMetaCoincide";
    } else {
        $log .= ", advertencia:ordenTareasSeccionesMetaNoCoincideTrasActualizar";
    }

    guardarLog($log);
    wp_send_json_success(['mensaje' => 'Orden de tareas y secciones actualizado.', 'ordenGuardadoSecciones' => $ordenMixtoProcesado, 'ordenGuardadoTareas' => $taskIdsSolo]);
}
add_action('wp_ajax_actualizarOrdenTareasGrupo', 'actualizarOrdenTareasGrupo');

function actualizarOrdenTareas()
{
    $usu = get_current_user_id();
    $tareaMov = isset($_POST['tareaMovida']) ? intval($_POST['tareaMovida']) : null;
    $ordenNueInput = isset($_POST['ordenNuevo']) ? $_POST['ordenNuevo'] : ""; // Default a string vacío para explode
    $ordenNue = array_map('intval', array_filter(explode(',', $ordenNueInput))); // array_filter para quitar vacíos si el string es ""

    $sesionArr = isset($_POST['sesionArriba']) ? strtolower(sanitize_text_field($_POST['sesionArriba'])) : null;
    $esSubtareaCliente = isset($_POST['subtarea']) ? $_POST['subtarea'] === 'true' : false;
    $padreCliente = isset($_POST['padre']) ? intval($_POST['padre']) : 0;

    $ordenTarAnt = get_user_meta($usu, 'ordenTareas', true) ?: [];

    $log = "actualizarOrdenTareas usu:$usu, tareaMov:$tareaMov, ordenNueRecibido:" . $ordenNueInput;
    $log .= ", ordenAntCant:" . count($ordenTarAnt) . ", sesionArr:$sesionArr, esSubtareaCliente:$esSubtareaCliente, padreCliente:$padreCliente";

    if ($tareaMov !== null && !empty($ordenNue)) {
        $logManejoSubtarea = "";
        if ($esSubtareaCliente) { // Cliente indica que $tareaMov debería ser subtarea de $padreCliente
            $logManejoSubtarea = manejarSubtarea($tareaMov, $padreCliente);
        } else { // Cliente indica que $tareaMov debería ser tarea principal
            $tareaObj = get_post($tareaMov);
            // Solo convertir a principal si actualmente tiene un padre
            if ($tareaObj && ($tareaObj->post_parent != 0 || get_post_meta($tareaMov, 'subtarea', true))) {
                $logManejoSubtarea = manejarSubtarea($tareaMov, 0); // 0 para convertir en tarea principal
            } else {
                $logManejoSubtarea = "tareaYaEsPrincipalONoRequiereCambioParent";
            }
        }
        $log .= ", manejoSubtareaLog:'$logManejoSubtarea'";
        
        $ordenTarActualizado = actualizarOrden($ordenTarAnt, $ordenNue);
        actualizarSeccionEstado($tareaMov, $sesionArr);
        
        $log .= ", ordenActualizadoExitoso";
        guardarLog($log);
        wp_send_json_success(['ordenTareas' => $ordenTarActualizado]);
    } else {
        $log .= ", error:datosInsuficientes (tareaMov:$tareaMov, ordenNueCant:" . count($ordenNue) . ")";
        guardarLog($log);
        wp_send_json_error(['error' => 'Falta información para actualizar el orden de tareas.'], 400);
    }
}
add_action('wp_ajax_actualizarOrdenTareas', 'actualizarOrdenTareas');


?>