<?php
# app/Services/Task/TaskOrderingService.php

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

                    foreach ($subtareasFaltantes as $subtareaId) {
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

// Refactor(Org): Funcion ordenamientoTareasPorPrioridad() movida desde app/Services/TaskService.php
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

// Refactor(Org): Funcion actualizarOrdenTareas() y hook AJAX movidos desde app/Services/TaskService.php
// Refactor(Org): Funciones helper (manejarSubtarea, esPadreUnaSubtarea, actualizarOrden, actualizarSesionEstado) movidas desde app/Services/TaskService.php

function manejarSubtarea($id, $idPadre)
{
    $log = '';
    if ($idPadre) {
        $tareaPadre = get_post($idPadre);
        if (empty($tareaPadre) || $tareaPadre->post_type != 'tarea') {
            return 'Error: Tarea padre no encontrada.';
        }

        // Verificar si la tarea padre es una subtarea de la tarea actual
        if (esPadreUnaSubtarea($idPadre, $id)) {
            return 'Error: No se puede convertir la tarea en subtarea de una de sus propias subtareas.';
        }

        $subtareaExistente = get_post_meta($id, 'subtarea', true);

        if (empty($subtareaExistente)) {
            $res = wp_update_post(array(
                'ID' => $id,
                'post_parent' => $idPadre
            ), true);

            if (is_wp_error($res)) {
                return 'Error al crear subtarea: ' . $res->get_error_message();
            }

            update_post_meta($id, 'subtarea', $idPadre);
            $log .= "Se creó la subtarea $id, tarea padre $idPadre. ";
        } else {
            $log .= "La subtarea $id ya existía como subtarea de $idPadre. No se realizaron cambios. ";
        }
    } else {
        // Eliminar subtarea
        $res = wp_update_post(array(
            'ID' => $id,
            'post_parent' => 0
        ), true);

        if (is_wp_error($res)) {
            return 'Error al eliminar subtarea: ' . $res->get_error_message();
        }

        delete_post_meta($id, 'subtarea');
        $log .= "Se eliminó la subtarea $id. ";
    }

    return $log;
}

function esPadreUnaSubtarea($idPadre, $id)
{
    $padreActual = $idPadre;
    while ($padreActual) {
        if ($padreActual == $id) {
            return true; // La tarea padre es una subtarea (directa o indirecta) de la tarea actual
        }
        $padreActual = get_post_meta($padreActual, 'subtarea', true);
    }
    return false; // La tarea padre no es una subtarea de la tarea actual
}

function actualizarOrden($ordenTar, $ordenNue)
{
    $log = "actualizarOrden: ";

    $usu = get_current_user_id();
    update_user_meta($usu, 'ordenTareas', $ordenNue);
    $log .= "\n  Se actualizó el orden de tareas para el usuario $usu a: " . implode(',', $ordenNue);

    guardarLog($log);
    return $ordenNue;
}


function actualizarSesionEstado($tareaMov, $sesionArr)
{
    $log = "actualizarSesionEstado: ";

    // Tratar 'null' como string
    $sesionArrString = is_null($sesionArr) ? "null" : $sesionArr;

    // Si $sesionArr es 'null' o null, usar "General"
    $sesionParaActualizar = ($sesionArrString === 'null') ? "General" : $sesionArr;

    $estadoAct = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesionTarea = get_post_meta($tareaMov, 'sesion', true);

    // Si $sesionTarea es null, 'null' o una cadena vacía, forzar a "General"
    if (empty($sesionTarea) || $sesionTarea === 'null') {
        $sesionTarea = "General";
    }

    $log .= "\n  Se recibió: '" . var_export($sesionArrString, true) . "' para la tarea '$tareaMov'.";
    $log .= "\n  Estado actual de la tarea '$tareaMov' es '$estadoAct'.";
    $log .= "\n  Sesión actual de la tarea '$tareaMov' es '" . var_export($sesionTarea, true) . "'.";

    // Obtener información sobre la tarea padre y las subtareas
    $tarea = get_post($tareaMov);
    $esSubtarea = !empty($tarea->post_parent);
    $tieneSubtareas = false;
    if (!$esSubtarea) {
        $hijas = get_children(array(
            'post_parent' => $tareaMov,
            'post_type'   => 'tarea',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        $tieneSubtareas = !empty($hijas);
    }

    // Si la sesión es "General", no se cambie el estado
    if (strtolower($sesionParaActualizar) !== 'general') {
        if (strtolower($sesionParaActualizar) === 'archivado' && $estadoAct !== 'archivado') {
            update_post_meta($tareaMov, 'estado', 'Archivado');
            $log .= "\n  Se actualizó el estado de la tarea '$tareaMov' a 'Archivado'.";

            // Si es una tarea padre, archivar también las subtareas
            if ($tieneSubtareas) {
                foreach ($hijas as $hija) {
                    update_post_meta($hija->ID, 'estado', 'Archivado');
                    $log .= "\n  Se actualizó el estado de la subtarea '{$hija->ID}' a 'Archivado'.";
                }
            }
        } elseif (strtolower($sesionParaActualizar) !== 'archivado' && $estadoAct === 'archivado') {
            update_post_meta($tareaMov, 'estado', 'Pendiente');
            $log .= "\n  Se actualizó el estado de la tarea '$tareaMov' a 'Pendiente'.";

            // Si es una tarea padre, desarchivar también las subtareas
            if ($tieneSubtareas) {
                foreach ($hijas as $hija) {
                    update_post_meta($hija->ID, 'estado', 'Pendiente');
                    $log .= "\n  Se actualizó el estado de la subtarea '{$hija->ID}' a 'Pendiente'.";
                }
            }
        } else {
            $log .= "\n  No se actualizó el estado de la tarea '$tareaMov' porque no era necesario.";
        }
    } else {
        $log .= "\n  La sesion es 'General', no se cambia el estado.";
    }

    // Si es una subtarea y se archiva, eliminar la relación de subtarea
    if ($esSubtarea && strtolower($sesionParaActualizar) === 'archivado') {
        wp_update_post(array(
            'ID' => $tareaMov,
            'post_parent' => 0
        ));
        delete_post_meta($tareaMov, 'subtarea');
        $log .= "\n  La tarea '$tareaMov' era una subtarea y se archivó, se eliminó la relación de subtarea.";
    }

    // Actualizar la sesión siempre que $sesionParaActualizar sea diferente a la actual
    if ($sesionParaActualizar !== $sesionTarea) {
        update_post_meta($tareaMov, 'sesion', $sesionParaActualizar);
        $log .= "\n  Se actualizó la sesión de la tarea '$tareaMov' a '$sesionParaActualizar'.";
    } else {
        $log .= "\n  No se actualizó la sesión de la tarea '$tareaMov' porque es la misma que la actual.";
    }

    $estadoFin = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesionFin = get_post_meta($tareaMov, 'sesion', true);

    // Si $sesionFin es null, 'null' o una cadena vacía, forzar a "General"
    if (empty($sesionFin) || $sesionFin === 'null') {
        $sesionFin = "General";
        update_post_meta($tareaMov, 'sesion', $sesionFin);
        $log .= "\n  Se corrigió la sesión final de la tarea '$tareaMov' a 'General'.";
    }

    $log .= "\n  Estado final de la tarea '$tareaMov' es '$estadoFin'.";
    $log .= "\n  Sesión final de la tarea '$tareaMov' es '$sesionFin'.";

    guardarLog($log);
}

function actualizarOrdenTareasGrupo()
{
    $usu = get_current_user_id();
    $log = "actualizarOrdenTareasGrupo: \n  Usuario ID: $usu";

    // Log crudo de $_POST para diagnóstico exhaustivo
    $log .= ", \n  Contenido CRUDO de \$_POST: " . print_r($_POST, true);

    $tareasMovIdsInput = isset($_POST['tareasMovidas']) ? $_POST['tareasMovidas'] : null;
    $ordenNueInput = isset($_POST['ordenNuevo']) ? $_POST['ordenNuevo'] : null;

    $log .= ", \n  tareasMovidas (input crudo desde \$_POST): " . var_export($tareasMovIdsInput, true);
    $log .= ", \n  ordenNuevo (input crudo desde \$_POST): " . var_export($ordenNueInput, true);

    $tareasMovIds = [];
    if (is_array($tareasMovIdsInput)) {
        // Si jQuery.ajax o similar ya lo convirtió a un array PHP
        $tareasMovIds = array_map('intval', $tareasMovIdsInput);
        $log .= ", \n  tareasMovidasInput era array, procesado como tal.";
    } elseif (is_string($tareasMovIdsInput) && !empty($tareasMovIdsInput)) {
        // Si llegó como un string, ej: "724,725,726"
        // O si llegó como un string de un solo número: "724"
        $tareasMovIds = array_map('intval', explode(',', $tareasMovIdsInput));
        $log .= ", \n  tareasMovidasInput era string, procesado con explode y intval.";
    }

    $ordenNue = [];
    if (is_array($ordenNueInput)) {
        $ordenNue = array_map('intval', $ordenNueInput);
        $log .= ", \n  ordenNueInput era array, procesado como tal.";
    } elseif (is_string($ordenNueInput) && !empty($ordenNueInput)) {
        $ordenNue = array_map('intval', explode(',', $ordenNueInput));
        $log .= ", \n  ordenNueInput era string, procesado con explode y intval.";
    }

    $log .= ", \n  Tareas movidas IDs (procesado FINAL): " . implode(',', $tareasMovIds);
    $log .= ", \n  Nuevo orden IDs (procesado FINAL): " . implode(',', $ordenNue);

    if (empty($tareasMovIds) || empty($ordenNue)) {
        $log .= ", \n  Error: Datos insuficientes después del procesamiento. tareasMovidas o ordenNuevo están vacíos.";
        guardarLog($log);
        wp_send_json_error(['error' => 'Datos insuficientes para actualizar el orden del grupo.'], 400);
        return;
    }

    // Es VITAL que $ordenNue sea el array COMPLETO de IDs de TODAS las tareas visibles para el usuario,
    // en el nuevo orden deseado. El JS parece estar haciendo esto correctamente:
    // const ordenNuevo = Array.from(listaMov.querySelectorAll('.draggable-element')).map(t => t.getAttribute('id-post'));

    $ordenTarMetaAnterior = get_user_meta($usu, 'ordenTareas', true) ?: [];
    $log .= ", \n  Orden en user_meta ANTES de actualizar: " . implode(',', $ordenTarMetaAnterior);

    actualizarOrden([], $ordenNue); // actualizarOrden actualiza el user_meta 'ordenTareas' con $ordenNue

    $ordenTarMetaPosterior = get_user_meta($usu, 'ordenTareas', true) ?: [];
    $log .= ", \n  Orden en user_meta DESPUÉS de actualizar: " . implode(',', $ordenTarMetaPosterior);

    if ($ordenTarMetaAnterior === $ordenTarMetaPosterior && count($ordenNue) > 1) {
        $log .= ", \n  ADVERTENCIA: El orden en user_meta no cambió después de la actualización, pero debería haberlo hecho si el orden nuevo era diferente.";
    } elseif (count($ordenTarMetaPosterior) !== count($ordenNue) && !empty($ordenNue)) {
        $log .= ", \n  ADVERTENCIA: La cantidad de elementos en el orden guardado (" . count($ordenTarMetaPosterior) . ") no coincide con la cantidad de elementos en el nuevo orden enviado (" . count($ordenNue) . ").";
    }


    $log .= ", \n  Llamada a actualizarOrden completada. Orden de tareas (user_meta) debería estar actualizado para el usuario $usu.";
    guardarLog($log);

    wp_send_json_success(['mensaje' => 'Orden de grupo de tareas actualizado correctamente.', 'ordenGuardado' => $ordenNue]);
}


add_action('wp_ajax_actualizarOrdenTareasGrupo', 'actualizarOrdenTareasGrupo');

function actualizarOrdenTareas()
{
    $usu = get_current_user_id();
    $tareaMov = isset($_POST['tareaMovida']) ? intval($_POST['tareaMovida']) : null;
    $ordenNue = isset($_POST['ordenNuevo']) ? explode(',', $_POST['ordenNuevo']) : [];
    $ordenNue = array_map('intval', $ordenNue);
    $sesionArr = isset($_POST['sesionArriba']) ? strtolower(sanitize_text_field($_POST['sesionArriba'])) : null;
    $ordenTar = get_user_meta($usu, 'ordenTareas', true) ?: [];
    $esSubtarea = isset($_POST['subtarea']) ? $_POST['subtarea'] === 'true' : false;
    $padre = isset($_POST['padre']) ? intval($_POST['padre']) : 0;

    $log = "actualizarOrdenTareas: \n  Usuario ID: $usu, \n  Tarea movida: $tareaMov, \n  Nuevo orden recibido: " . implode(',', $ordenNue) . ", \n  Orden antes de cambiar: " . implode(',', $ordenTar) . ", \n  Sesion arriba: $sesionArr";

    if ($tareaMov !== null && !empty($ordenNue)) {
        // Manejar creación o eliminación de subtareas
        if ($esSubtarea) {
            $log .= ", \n  " . manejarSubtarea($tareaMov, $padre);
        } else {
            // Si no es una subtarea, pero tiene el metadato 'subtarea', eliminarlo
            $subtareaExistente = get_post_meta($tareaMov, 'subtarea', true);
            if (!empty($subtareaExistente)) {
                $log .= ", \n  " . manejarSubtarea($tareaMov, 0);
            }
        }

        $ordenTar = actualizarOrden($ordenTar, $ordenNue);
        actualizarSesionEstado($tareaMov, $sesionArr);
        $log .= ", \n  Orden de tareas actualizado exitosamente para el usuario $usu";
        guardarLog($log);
        wp_send_json_success(['ordenTareas' => $ordenTar]);
    } else {
        $log .= ", \n  Error: ";
        if ($tareaMov === null) {
            $log .= "tareaMovida es null";
        }
        if (empty($ordenNue)) {
            $log .= ($tareaMov === null ? ", " : "") . "ordenNuevo está vacío";
        }
        guardarLog($log);
        wp_send_json_error(['error' => 'Falta información para actualizar el orden de tareas.'], 400);
    }
}

add_action('wp_ajax_actualizarOrdenTareas', 'actualizarOrdenTareas');
