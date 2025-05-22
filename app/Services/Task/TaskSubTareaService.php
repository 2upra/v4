<?php


function crearSubtarea()
{
    $nombreFunc = 'crearSubtarea';
    $logAcumulado = "";

    if (!current_user_can('edit_posts')) {
        jsonTask(false, 'Sin permisos.', "$nombreFunc: Acceso denegado por falta de permisos.", $nombreFunc);
    }

    $idTar = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $esSubAccion = (isset($_POST['subtarea']) && $_POST['subtarea'] === 'true');
    $idPadProp = isset($_POST['padre']) ? intval($_POST['padre']) : 0;

    $logAcumulado = "$nombreFunc: idTar:$idTar, esSubAccion:$esSubAccion, idPadProp:$idPadProp.";

    if ($idTar <= 0) {
        jsonTask(false, 'ID de tarea inválido.', $logAcumulado . " ID tarea $idTar inválido.", $nombreFunc);
    }

    $tarMod = get_post($idTar);
    if (!$tarMod || $tarMod->post_type !== 'tarea') {
        jsonTask(false, 'Tarea a modificar no encontrada.', $logAcumulado . " Tarea a modificar $idTar no encontrada o no es tipo 'tarea'.", $nombreFunc);
    }

    $resManejo = '';

    if ($esSubAccion) {
        if ($idPadProp <= 0) {
            jsonTask(false, 'ID de tarea padre inválido.', $logAcumulado . " ID padre $idPadProp inválido para establecer subtarea.", $nombreFunc);
        }
        if ($idTar === $idPadProp) {
            jsonTask(false, 'Una tarea no puede ser subtarea de sí misma.', $logAcumulado . " Intento de hacer $idTar subtarea de sí misma.", $nombreFunc);
        }

        $resManejo = manejarSubtarea($idTar, $idPadProp);
        // El log de manejarSubtarea ya se guarda internamente en esa función.
        // Aquí concatenamos para el log general de crearSubtarea si jsonTask lo necesita.
        $logAcumulado .= " Resultado manejarSubtarea(establecer): [ver log de manejarSubtarea para $idTar,$idPadProp]";


        if (strpos($resManejo, 'Error:') === 0) {
            $msjUsr = substr($resManejo, strlen('Error: '));
            jsonTask(false, $msjUsr, $logAcumulado, $nombreFunc);
        } else {
            $msjUsrExito = 'Relación de subtarea actualizada correctamente.';
            if (strpos($resManejo, 'tareaYaEsSubtareaCorrectaDe') !== false) {
                $msjUsrExito = 'La tarea ya era subtarea del padre especificado. No se realizaron cambios.';
            }
            jsonTask(true, ['mensaje' => $msjUsrExito], $logAcumulado, $nombreFunc);
        }
    } else {
        $resManejo = manejarSubtarea($idTar, 0);
        $logAcumulado .= " Resultado manejarSubtarea(quitar): [ver log de manejarSubtarea para $idTar,0]";

        if (strpos($resManejo, 'Error:') === 0) {
            $msjUsr = substr($resManejo, strlen('Error: '));
            jsonTask(false, $msjUsr, $logAcumulado, $nombreFunc);
        } else {
            $msjUsrExito = 'Tarea convertida a principal correctamente.';
            if (strpos($resManejo, 'tareaYaEsPrincipal') !== false) {
                $msjUsrExito = 'La tarea ya era principal. No se realizaron cambios.';
            }
            jsonTask(true, ['mensaje' => $msjUsrExito], $logAcumulado, $nombreFunc);
        }
    }
}
add_action('wp_ajax_crearSubtarea', 'crearSubtarea');

function manejarSubtarea($id, $idPadrePropuesto)
{
    $log = "manejarSubtarea id:$id, idPadrePropuesto:$idPadrePropuesto";
    $idPadreFinal = 0; // Por defecto, si no hay padre, se convierte en tarea principal.

    if ($idPadrePropuesto) {
        if ($id == $idPadrePropuesto) {
            $log .= ", error: tareaNoPuedeSerSuPropioPadre";
            guardarLog($log);
            return 'Error: Una tarea no puede ser su propia padre.';
        }

        $tareaPadrePotencial = get_post($idPadrePropuesto);
        if (empty($tareaPadrePotencial) || $tareaPadrePotencial->post_type != 'tarea') {
            $log .= ", error: padrePropuestoNoEncontradoONoEsTarea";
            guardarLog($log);
            return 'Error: Tarea padre propuesta no encontrada o no es una tarea.';
        }

        // REGLA CLAVE: Si el padre propuesto es una subtarea, usar el padre de ESE padre (el "abuelo").
        if ($tareaPadrePotencial->post_parent != 0) {
            $idPadreFinal = $tareaPadrePotencial->post_parent;
            $log .= ", padrePropuestoEsSubtarea, padreFinalReal:$idPadreFinal";
        } else {
            $idPadreFinal = $idPadrePropuesto; // El padre propuesto es una tarea principal.
            $log .= ", padrePropuestoEsPrincipal, padreFinalReal:$idPadreFinal";
        }

        // Si después de la lógica anterior, el idPadreFinal es el mismo que el id de la tarea, error.
        if ($id == $idPadreFinal) {
            $log .= ", error: tareaNoPuedeSerSuPropioPadre (despues de resolver padre real)";
            guardarLog($log);
            return 'Error: Una tarea no puede ser su propia padre (después de resolver jerarquía).';
        }

        // Prevenir ciclos: $id no puede ser padre de $idPadreFinal.
        if (esPadreUnaSubtarea($idPadreFinal, $id)) {
            $log .= ", error: cicloDetectado ($idPadreFinal es descendiente de $id)";
            guardarLog($log);
            return 'Error: No se puede convertir la tarea en subtarea de una de sus propias subtareas (ciclo detectado).';
        }

        $tareaActual = get_post($id);
        // Si ya es subtarea del padre final correcto, no hacer nada.
        if ($tareaActual && $tareaActual->post_parent == $idPadreFinal && get_post_meta($id, 'subtarea', true) == $idPadreFinal) {
            $log .= ", tareaYaEsSubtareaCorrectaDe:$idPadreFinal";
            guardarLog($log);
            return $log; // O un mensaje más específico.
        }

        $res = wp_update_post(array(
            'ID' => $id,
            'post_parent' => $idPadreFinal
        ), true);

        if (is_wp_error($res)) {
            $log .= ", errorWpUpdatePost:" . $res->get_error_message();
            guardarLog($log);
            return 'Error al actualizar post_parent para subtarea: ' . $res->get_error_message();
        }

        update_post_meta($id, 'subtarea', $idPadreFinal);
        $log .= ", exito: tarea $id asignada como subtarea de $idPadreFinal";
    } else { // $idPadrePropuesto es 0 o nulo: convertir $id en tarea principal.
        $tareaActual = get_post($id);
        if ($tareaActual && $tareaActual->post_parent == 0 && !get_post_meta($id, 'subtarea', true)) {
            $log .= ", tareaYaEsPrincipal";
            guardarLog($log);
            return $log; // Ya es tarea principal, no se necesita acción.
        }

        $res = wp_update_post(array(
            'ID' => $id,
            'post_parent' => 0 // Convertir en tarea principal
        ), true);

        if (is_wp_error($res)) {
            $log .= ", errorWpUpdatePostAlEliminarSubtarea:" . $res->get_error_message();
            guardarLog($log);
            return 'Error al eliminar la relación de subtarea (actualizar post_parent a 0): ' . $res->get_error_message();
        }

        delete_post_meta($id, 'subtarea');
        $log .= ", exito: tarea $id convertidaAPrincipal";
    }
    guardarLog($log);
    return $log; // Devolver el log puede ser útil para el llamador.
}

function esPadreUnaSubtarea($idPosiblePadre, $idTareaQueSeMueve)
{
    // Verifica si $idPosiblePadre es un DESCENDIENTE de $idTareaQueSeMueve.
    // Si es así, $idTareaQueSeMueve no puede ser asignada como subtarea de $idPosiblePadre (crearía un ciclo).
    // Ejemplo: T1 es padre de T2. No podemos hacer T1 subtarea de T2.
    // Aquí, $idPosiblePadre sería T2, $idTareaQueSeMueve sería T1.
    // La función debe retornar true si T2 es descendiente de T1.

    if (empty($idPosiblePadre) || empty($idTareaQueSeMueve) || $idPosiblePadre == $idTareaQueSeMueve) {
        return false; // No hay ciclo si no hay padre, o si son la misma tarea (esto se maneja en manejarSubtarea).
    }

    $ancestro = get_post($idPosiblePadre);
    if (!$ancestro) return false; // $idPosiblePadre no es válido.

    $idAncestroActual = $ancestro->post_parent;

    while ($idAncestroActual != 0) { // Recorrer hacia arriba la jerarquía de $idPosiblePadre
        if ($idAncestroActual == $idTareaQueSeMueve) {
            return true; // $idTareaQueSeMueve es un ancestro de $idPosiblePadre, ergo $idPosiblePadre es su descendiente.
        }
        $ancestroPost = get_post($idAncestroActual);
        if (!$ancestroPost) break; // Cadena rota o ID de padre incorrecto.
        $idAncestroActual = $ancestroPost->post_parent;
    }
    return false; // No se encontró $idTareaQueSeMueve en los ancestros de $idPosiblePadre.
}

// Función de ayuda: Actualiza un metadato en una tarea y, opcionalmente, en sus hijas directas.
// Si la clave es 'sesion', aplica la lógica de desvinculación para la tarea principal ($idTar) si es una subtarea
// y su nueva sección difiere de la de su padre.
// Devuelve el estado de la actualización del padre y cuenta las hijas actualizadas.
function actTarHijosMet($idTar, $clave, $valor, &$cntHijAct = 0) {
    $res = update_post_meta($idTar, $clave, $valor);

    if ($clave === 'sesion') {
        $tarActual = get_post($idTar);
        // Verificar si $idTar es una subtarea (tiene un post_parent)
        if ($tarActual && $tarActual->post_type === 'tarea' && $tarActual->post_parent > 0) {
            $idPadreDeTarActual = $tarActual->post_parent;
            $sesPadreDeTarActual = get_post_meta($idPadreDeTarActual, 'sesion', true);

            // Normalizar secciones para comparación consistente ("General" para vacíos/nulos)
            $valNorm = (in_array(strtolower($valor), ['null', '', null], true) || empty($valor)) ? "General" : $valor;
            $sesPadreNorm = (in_array(strtolower($sesPadreDeTarActual), ['null', '', null], true) || empty($sesPadreDeTarActual)) ? "General" : $sesPadreDeTarActual;

            if (strtolower($valNorm) !== strtolower($sesPadreNorm)) {
                wp_update_post(['ID' => $idTar, 'post_parent' => 0]);
                delete_post_meta($idTar, 'subtarea');
                // Aquí $idTar se ha convertido en una tarea principal.
                // Si se guarda un log, se podría indicar esta desvinculación.
                // Ejemplo: guardarLog("actTarHijosMet: Tarea $idTar desvinculada de padre $idPadreDeTarActual por cambio de sección a '$valNorm' (padre tenía '$sesPadreNorm')");
            }
        }
    }

    $hijos = get_children([
        'post_parent' => $idTar, // Las hijas siguen asociadas a $idTar por su ID
        'post_type'   => 'tarea',
        'numberposts' => -1,
        'fields'      => 'ids'
    ]);

    if ($hijos) {
        foreach ($hijos as $idHijo) {
            // A las hijas se les asigna la misma clave y valor
            update_post_meta($idHijo, $clave, $valor);
            $cntHijAct++;
            // Nota: La lógica de desvinculación para $idHijo no se aplica aquí recursivamente.
            // Si $idHijo fuera una subtarea y necesitara ser desvinculada,
            // se requeriría una llamada a actTarHijosMet($idHijo, $clave, $valor)
            // o que la función que maneja $idHijo directamente aplique esta lógica.
            // En este flujo, las hijas heredan la sección de $idTar,
            // por lo que su sección coincidirá con la de su padre ($idTar),
            // y no se desvincularán de $idTar por esta regla.
        }
    }
    return $res;
}

// Función de ayuda: Actualiza el estado de una tarea y todas sus hijas directas.
// Esta es más específica que actTarHijosMet para el metadato 'estado'.
// No necesita cambios para la nueva lógica de sección.
function actEstTarHijos($idTar, $estNue, &$cntHijAct = 0) {
    update_post_meta($idTar, 'estado', $estNue);
    $hijos = get_children([
        'post_parent' => $idTar,
        'post_type'   => 'tarea',
        'fields'      => 'ids',
        'posts_per_page' => -1
    ]);
    if ($hijos) {
        foreach ($hijos as $idHijo) {
            update_post_meta($idHijo, 'estado', $estNue);
            $cntHijAct++;
        }
    }
}

// Las funciones actualizarSeccion y asignarSeccionMeta no necesitan cambios directos en su código
// porque llaman a actTarHijosMet, que ahora contiene la nueva lógica.