<?php
# app/Services/Task/TaskOrderingService.php


function ordenamientoTareas($queryArgs, $usu, $args, $prioridad = false)
{
    $ordenarServidor = false;

    $orden = get_user_meta($usu, 'ordenTareas', true);
    $log = "ordenamientoTareas usu: $usu";

    if (!is_array($orden)) {
        $orden = [];
    }

    if (!$ordenarServidor) {
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

        $ordenFinalReordenado = [];
        $subtareasOrdenadas = []; // Para rastrear las subtareas ya colocadas por su padre

        foreach ($ordenFinal as $tareaId) {
            $tarea = get_post($tareaId);
            if (!$tarea) continue; // Por si la tarea fue eliminada mientras tanto

            // Si es una tarea padre (o una subtarea que ya fue procesada por su padre y está en $subtareasOrdenadas)
            if ($tarea->post_parent == 0) {
                if (in_array($tareaId, $ordenFinalReordenado)) continue; // Ya añadida
                $ordenFinalReordenado[] = $tareaId;

                $subtareas = get_children([
                    'post_parent' => $tareaId,
                    'post_type'   => 'tarea',
                    'fields'      => 'ids',
                    'posts_per_page' => -1, // Asegurar traer todas las subtareas
                ]);

                if (!empty($subtareas)) {
                    $log .= ", padre $tareaId tieneSubtareas: " . implode(',', $subtareas);
                    // Subtareas que ya estaban en el orden del usuario, mantener su orden relativo
                    $subtareasExistentesEnOrden = array_intersect($ordenFinal, $subtareas);
                    // Subtareas que no estaban en el orden (nuevas o descolocadas)
                    $subtareasNuevasOTras = array_diff($subtareas, $subtareasExistentesEnOrden);
                    
                    $subtareasParaAgregar = array_merge($subtareasExistentesEnOrden, $subtareasNuevasOTras);

                    foreach ($subtareasParaAgregar as $subtareaId) {
                        if (!in_array($subtareaId, $ordenFinalReordenado)) { // Evitar duplicados
                           $ordenFinalReordenado[] = $subtareaId;
                        }
                        $subtareasOrdenadas[] = $subtareaId; // Marcar como procesada
                    }
                }
            } else if (!in_array($tareaId, $subtareasOrdenadas)) {
                // Es una subtarea, pero no fue procesada por su padre (quizás el padre no está en $ordenFinal o viene después).
                // O es una subtarea "huérfana" (padre eliminado o no accesible).
                // La añadimos para que no se pierda, aunque podría no estar junto a su padre si este se procesa después.
                // Esta situación debería minimizarse si el orden es generalmente coherente.
                if (in_array($tareaId, $ordenFinalReordenado)) continue; // Ya añadida de alguna forma
                $log .= ", subtareaHuerfanaOAdelantada $tareaId (padre {$tarea->post_parent})";
                $ordenFinalReordenado[] = $tareaId;
            }
        }
        // Asegurarse de que no haya duplicados al final
        $ordenFinalReordenado = array_values(array_unique($ordenFinalReordenado));
        $log .= ", ordenFinalReordenadoCant: " . count($ordenFinalReordenado);
        
        if ($ordenFinalReordenado !== $orden || count($ordenFinalReordenado) !== count($orden)) { // Comparar también cantidad
            update_user_meta($usu, 'ordenTareas', $ordenFinalReordenado);
            $log .= ", ordenMetaActualizado";
        } else {
            $log .= ", ordenMetaNoCambio";
        }

        $queryArgs['post__in'] = !empty($ordenFinalReordenado) ? $ordenFinalReordenado : [0]; // Evitar error con array vacío
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

    $tareasMovIdsInput = isset($_POST['tareasMovidas']) ? $_POST['tareasMovidas'] : null;
    $ordenNueInput = isset($_POST['ordenNuevo']) ? $_POST['ordenNuevo'] : null;

    $log .= ", tareasMovInput:" . var_export($tareasMovIdsInput, true);
    $log .= ", ordenNueInput:" . var_export($ordenNueInput, true);

    $tareasMovIds = [];
    if (is_array($tareasMovIdsInput)) {
        $tareasMovIds = array_map('intval', $tareasMovIdsInput);
    } elseif (is_string($tareasMovIdsInput) && !empty($tareasMovIdsInput)) {
        $tareasMovIds = array_map('intval', explode(',', $tareasMovIdsInput));
    }

    $ordenNue = [];
    if (is_array($ordenNueInput)) {
        $ordenNue = array_map('intval', $ordenNueInput);
    } elseif (is_string($ordenNueInput) && !empty($ordenNueInput)) {
        $ordenNue = array_map('intval', explode(',', $ordenNueInput));
    }

    $log .= ", tareasMovFinal:" . implode(',', $tareasMovIds) . ", ordenNueFinal:" . implode(',', $ordenNue);

    if (empty($tareasMovIds) || empty($ordenNue)) {
        $log .= ", error:datosInsuficientes";
        guardarLog($log);
        wp_send_json_error(['error' => 'Datos insuficientes para actualizar el orden del grupo.'], 400);
        return;
    }
    
    $ordenTarMetaAnterior = get_user_meta($usu, 'ordenTareas', true) ?: [];
    actualizarOrden($ordenTarMetaAnterior, $ordenNue); // $ordenTarMetaAnterior se pasa pero no se usa dentro de actualizarOrden
    
    // Verificar si el cambio fue efectivo (opcional, para log)
    $ordenTarMetaPosterior = get_user_meta($usu, 'ordenTareas', true) ?: [];
    if ($ordenTarMetaPosterior === $ordenNue) {
        $log .= ", exito:ordenMetaCoincideConOrdenNuevo";
    } else {
        $log .= ", advertencia:ordenMetaNoCoincideTrasActualizar";
        $log .= ", metaPosterior: " . implode(',', $ordenTarMetaPosterior);
    }

    guardarLog($log);
    wp_send_json_success(['mensaje' => 'Orden de grupo de tareas actualizado.', 'ordenGuardado' => $ordenNue]);
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