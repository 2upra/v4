<?

#Los selectores sImportancia y sTipo ya abren los submenu que son los A1806241, al ejecutarse el script tiene que remplazar el texto ejemplo y poner el primer valor de los botones, esta info se guarda en variables globales, y si el usuario da click a otro boton, se cambia, dame esa parte del script en una funcion, js vanilla 


function formTarea()
{
    ob_start();
?>
    <div class="bloque tareasbloque">
        <input type="text" name="titulo" placeholder="Agregar nueva tarea" id="tituloTarea">

        <div class="selectorIcono sImportancia" id="sImportancia">
            <span class="icono">
                <?php echo $GLOBALS['importancia']; ?>baja
            </span>
        </div>

        <div class="A1806241" id="sImportancia-sImportancia">
            <div class="A1806242">
                <button value="baja">baja</button>
                <button value="media">media</button>
                <button value="alta">alta</button>
                <button value="importante">importante</button>
            </div>
        </div>

        <div class="selectorIcono sTipo" id="sTipo">
            <span class="icono"><?php echo $GLOBALS['tipoTarea']; ?>Una vez</span>
        </div>

        <div class="A1806241" id="sTipo-sTipo">
            <div class="A1806242">
                <button value="una vez">Una vez</button>
                <button value="habito">Hábito flexible</button>
                <button value="habito rigido">Hábito rígido</button>
                <button value="meta" style="display: none;">Meta</button>
            </div>
        </div>
    </div>

    <? echo formTareaEstilo(); ?>

<?php
    return ob_get_clean();
}

function formTareaEstilo()
{
    ob_start();
?>
    <style>
        span.icono p {
            font-size: 12px;
        }

        span.icono {
            display: flex;
            flex-direction: row;
            font-size: 11px;
            gap: 6px;
            padding: 0px 5px;
            border-radius: 100px;
            align-items: center;
            justify-content: center;
            width: max-content;
            opacity: 0.9;
            cursor: pointer;
        }

        .selectorIcono {
            padding: 10px 0px;
        }

        .bloque.tareasbloque svg {
            cursor: pointer;
        }

        .bloque.tareasbloque {
            display: flex;
            flex-direction: row;
            height: 40px;
            padding: 5px;
            align-items: center;
            padding-right: 20px;
            background: unset;
        }

        .tareasbloque input {
            background: none;
        }

        .LNVHED.no-tareas {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 100px;
        }
    </style>
<?php
    return ob_get_clean();
}


function crearTarea()
{
    $log = '';
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $tit = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
    $imp = isset($_POST['importancia']) ? sanitize_text_field($_POST['importancia']) : '';
    $tip = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'una vez';
    $frec = isset($_POST['frecuencia']) ? (int) sanitize_text_field($_POST['frecuencia']) : 1;

    if (empty($tit)) {
        wp_send_json_error('Título vacío.');
    }

    $impnum = 0;
    if ($imp === 'importante') {
        $impnum = 4;
    } elseif ($imp === 'alta') {
        $impnum = 3;
    } elseif ($imp === 'media') {
        $impnum = 2;
    } elseif ($imp === 'baja') {
        $impnum = 1;
    }

    $tipnum = 0;
    if ($tip === 'una vez') {
        $tipnum = 1;
    } elseif ($tip === 'habito') {
        $tipnum = 2;
    } elseif ($tip === 'meta') {
        $tipnum = 3;
    } elseif ($tip === 'habito rigido') {
        $tipnum = 4;
    }

    $fec = date('Y-m-d');
    $fecprox = date('Y-m-d', strtotime("+{$frec} days"));

    $args = array(
        'post_title' => $tit,
        'post_type' => 'tarea',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'meta_input' => array(
            'importancia' => $imp,
            'impnum' => $impnum,
            'tipo' => $tip,
            'tipnum' => $tipnum,
            'estado' => 'pendiente',
            'frecuencia' => $frec,
            'fecha' => $fec,
            'fechaProxima' => $fecprox,
        ),
    );

    $tareaId = wp_insert_post($args);

    if (is_wp_error($tareaId)) {
        $msg = $tareaId->get_error_message();
        $log .= "Error al crear tarea: $msg \n";
        guardarLog("crearTarea:  \n $log");
        wp_send_json_error($msg);
    }

    $log .= "Tarea creada con id $tareaId";
    guardarLog("crearTarea:  \n $log");
    wp_send_json_success(array('tareaId' => $tareaId));
}

add_action('wp_ajax_crearTarea', 'crearTarea');

function modificarTarea()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tit = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';

    if (empty($tit)) {
        wp_send_json_error('Título vacío.');
    }

    $tarea = get_post($id);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    $args = array(
        'ID' => $id,
        'post_title' => $tit
    );

    $res = wp_update_post($args, true);

    if (is_wp_error($res)) {
        $msg = $res->get_error_message();
        wp_send_json_error($msg);
    }

    wp_send_json_success();
}

add_action('wp_ajax_modificarTarea', 'modificarTarea');

/*

Tengo 2 tipos de habitos. Habitos flexibles y habitos rigidos. 

Hay 3 valores en los habitos.
Fecha: la fecha que se creo la tarea. 
fechaProxima: la fecha+frecuencia.
Y la frecuencia que es la cantidad de dias que se tiene que hacer el habito.

Los habitos flexible simplemente deben actualizar la fechaProxima a la fecha en que se completan, por ejemplo si hoy es día 7 y la frecuencia es de 7 dias, entonces la proxima fecha es el dia 14, no importa nada más, no importa si se completo antes, o si pasaron 20 días, la fecha proxima será hoy+frecuencia..

En los habitos rigidos en cambio si la fecha(meta) es 1 y la frecuencia es 7, entonces, eso quiere decir que la fechaproxima es 7, y hoy es 13, y completo la tarea, la fecha proxima será el 14. Porque no importa el día que la complete, siempre será, proxima+frecuencia.

Así de sencillo proxima+frecuencia para los habitos rigidos.
Y hoy+frecuencia para los habitos flexibles.

Formarmato de fecha en las metas: 2024-12-30 
metas involuncradas: fecha, fechaProxima, frecuencia (un valor numerico entre 1 a 365), y la meta tipo, con 3 valores posibles: habito flexible, habito rigido, tarea.


*/
function completarTarea()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : 'pendiente';

    $tarea = get_post($id);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    $tipo = get_post_meta($id, 'tipo', true);
    $log = "Funcion completarTarea(). \n ID: $id, tipo: $tipo. \n";

    if ($tipo == 'una vez') {
        $log .= "Se actualizo el estado de la tarea a $estado \n";
        update_post_meta($id, 'estado', $estado);
    } else if ($tipo == 'habito' || $tipo == 'habito rigido') {
        $fecha = get_post_meta($id, 'fecha', true);
        $fechaProxima = get_post_meta($id, 'fechaProxima', true);
        $frecuencia = intval(get_post_meta($id, 'frecuencia', true));
        $hoy = date('Y-m-d');


        $vecesCompletado = get_post_meta($id, 'vecesCompletado', true);
        if (empty($vecesCompletado)) {
            add_post_meta($id, 'vecesCompletado', 0, true);
            $vecesCompletado = 0;
        }
        $vecesCompletado++;

        // Manejar el registro de fechas de completado
        $fechasCompletado = get_post_meta($id, 'fechasCompletado', true);
        if (empty($fechasCompletado)) {
            $fechasCompletado = array();
        }

        // Agregar la fecha actual al array de fechas de completado
        $fechasCompletado[] = $hoy;

        update_post_meta($id, 'vecesCompletado', $vecesCompletado);
        update_post_meta($id, 'fechasCompletado', $fechasCompletado);

        if ($tipo == 'habito') {
            $nuevaFechaProxima = date('Y-m-d', strtotime($hoy . " + $frecuencia days"));
        } elseif ($tipo == 'habito rigido') {
            $nuevaFechaProxima = date('Y-m-d', strtotime($fechaProxima . " + $frecuencia days"));
        }

        $log .= "Se actualizo fechaProxima de $fechaProxima a $nuevaFechaProxima, y se agrego +1 a vecesCompletado (actualmente en $vecesCompletado), ademas se registraron las fechas de completado \n";
        update_post_meta($id, 'fechaProxima', $nuevaFechaProxima);
    }
    guardarLog($log);
    wp_send_json_success();
}

add_action('wp_ajax_completarTarea', 'completarTarea');


//si esto recibe una tarea que ya esta archivada, la pasa a estado pendiente
function archivarTarea()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tarea = get_post($id);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    $log = "Funcion archivarTarea(). \n  ID: $id. \n";

    $usu = get_current_user_id();
    $orden = get_user_meta($usu, 'ordenTareas', true);
    $estadoActual = get_post_meta($id, 'estado', true);

    if ($estadoActual == 'archivado') {
        update_post_meta($id, 'estado', 'pendiente');
        $log .= "Se cambio el estado de la tarea $id a pendiente.";
    } else {
        if (is_array($orden) && in_array($id, $orden)) {
            $pos = array_search($id, $orden);
            unset($orden[$pos]);
            $orden[] = $id;
            update_user_meta($usu, 'ordenTareas', $orden);
            $log .= "Se actualizo el orden de la tarea $id, moviendola al final. \n";
        }

        update_post_meta($id, 'estado', 'archivado');
        $log .= "Se actualizo el estado de la tarea $id a archivado.";
    }

    guardarLog($log);
    wp_send_json_success();
}

add_action('wp_ajax_archivarTarea', 'archivarTarea');

function cambiarPrioridad()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $tareaId = isset($_POST['tareaId']) ? intval($_POST['tareaId']) : 0;
    $prioridad = isset($_POST['prioridad']) ? sanitize_text_field($_POST['prioridad']) : '';

    $tarea = get_post($tareaId);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    if (!in_array($prioridad, ['baja', 'media', 'alta', 'importante'])) {
        wp_send_json_error('Prioridad inválida.');
    }

    $impnum = 0;
    if ($prioridad === 'importante') {
        $impnum = 4;
    } elseif ($prioridad === 'alta') {
        $impnum = 3;
    } elseif ($prioridad === 'media') {
        $impnum = 2;
    } elseif ($prioridad === 'baja') {
        $impnum = 1;
    }

    update_post_meta($tareaId, 'importancia', $prioridad);
    update_post_meta($tareaId, 'impnum', $impnum); // Guarda el valor numérico

    wp_send_json_success();
}

add_action('wp_ajax_cambiarPrioridad', 'cambiarPrioridad');

function cambiarFrecuencia()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $tareaId = isset($_POST['tareaId']) ? intval($_POST['tareaId']) : 0;
    $frec = isset($_POST['frecuencia']) ? intval($_POST['frecuencia']) : 0;

    $tarea = get_post($tareaId);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    if ($frec < 1 || $frec > 365) {
        wp_send_json_error('Frecuencia inválida.');
    }

    $fec = date('Y-m-d');
    $fecprox = date('Y-m-d', strtotime("+{$frec} days"));

    update_post_meta($tareaId, 'frecuencia', $frec);
    update_post_meta($tareaId, 'fechaProxima', $fecprox);

    $log = "Frecuencia de tarea actualizada correctamente. ID: $tareaId, Frecuencia: $frec \n Fecha proxima: $fecprox";
    guardarLog("cambiarFrecuencia:  \n $log");
    wp_send_json_success();
}

add_action('wp_ajax_cambiarFrecuencia', 'cambiarFrecuencia');



//separa la logica de actualizar sesion o estado pero manten todo funcionando igual

function actualizarOrdenTareas()
{
    $usu = get_current_user_id();
    $tareaMov = isset($_POST['tareaMovida']) ? intval($_POST['tareaMovida']) : null;
    $ordenNue = isset($_POST['ordenNuevo']) ? explode(',', $_POST['ordenNuevo']) : [];
    $ordenNue = array_map('intval', $ordenNue);
    $sesionArr = isset($_POST['sesionArriba']) ? strtolower(sanitize_text_field($_POST['sesionArriba'])) : null;
    $ordenTar = get_user_meta($usu, 'ordenTareas', true) ?: [];

    $log = "actualizarOrdenTareas: \n  Usuario ID: $usu, \n  Tarea movida: $tareaMov, \n  Nuevo orden: " . implode(',', $ordenNue) . ", \n  Orden actual: " . implode(',', $ordenTar) . ", \n  Sesion arriba: $sesionArr";

    if ($tareaMov !== null && !empty($ordenNue)) {
        $ordenTar = actualizarOrden($ordenTar, $tareaMov, $ordenNue);
        actualizarSesionEstado($tareaMov, $sesionArr);
        //guardarLog($log);
        wp_send_json_success(['ordenTareas' => $ordenTar]);
    } else {
        $log .= ", \n  Error: ";
        if ($tareaMov === null) {
            $log .= "tareaMovida es null";
        }
        if (empty($ordenNue)) {
            $log .= ($tareaMov === null ? ", " : "") . "ordenNuevo está vacío";
        }
        //guardarLog($log);
        wp_send_json_error(['error' => 'Falta información para actualizar el orden de tareas.'], 400);
    }
}

function actualizarOrden($ordenTar, $tareaMov, $ordenNue)
{
    $log = "actualizarOrden: ";
    $indiceVie = array_search($tareaMov, $ordenTar, true);
    if ($indiceVie !== false) {
        unset($ordenTar[$indiceVie]);
        $ordenTar = array_values($ordenTar);
        $log .= "\n  Tarea $tareaMov eliminada del índice antiguo: $indiceVie";
    } else {
        $log .= "\n  Tarea $tareaMov no encontrada en el orden actual";
    }

    $indiceNue = array_search($tareaMov, $ordenNue, true);
    $log .= ", \n  Nuevo índice para tarea $tareaMov: $indiceNue";

    array_splice($ordenTar, $indiceNue, 0, $tareaMov);
    $log .= ", \n  Tarea $tareaMov insertada en el nuevo orden";

    $usu = get_current_user_id();
    update_user_meta($usu, 'ordenTareas', $ordenTar);
    $log .= ", \n  Se actualizaron las IDs de ordenTareas para el usuario $usu, \n  Orden final guardado: " . implode(',', $ordenTar);

    //guardarLog($log);
    return $ordenTar;
}

function actualizarSesionEstado($tareaMov, $sesionArr) {
    $log = "actualizarSesionEstado: ";
    if ($sesionArr) {
        $estadoAct = strtolower(get_post_meta($tareaMov, 'estado', true));
        $sesionTarea = get_post_meta($tareaMov, 'sesion', true);

        $log .= "\n  Se recibió: '$sesionArr' para la tarea '$tareaMov'.";
        $log .= "\n  Estado actual de la tarea '$tareaMov' es '$estadoAct'.";
        $log .= "\n  Sesión actual de la tarea '$tareaMov' es '$sesionTarea'.";

        if (strtolower($sesionArr) === 'archivado' && $estadoAct !== 'archivado') {
            update_post_meta($tareaMov, 'estado', 'Archivado');
            $log .= "\n  Se actualizó el estado de la tarea '$tareaMov' a 'Archivado'.";
        } elseif (strtolower($sesionArr) !== 'archivado' && $estadoAct === 'archivado') {
            update_post_meta($tareaMov, 'estado', 'Pendiente');
            $log .= "\n  Se actualizó el estado de la tarea '$tareaMov' a 'Pendiente'.";
        } else {
            $log .= "\n  No se actualizó el estado de la tarea '$tareaMov' porque no era necesario.";
        }
        
        update_post_meta($tareaMov, 'sesion', $sesionArr);
        $log .= "\n  Se actualizó la sesión de la tarea '$tareaMov' a '$sesionArr'.";

        $estadoFin = strtolower(get_post_meta($tareaMov, 'estado', true));
        $sesionFin = get_post_meta($tareaMov, 'sesion', true);

        $log .= "\n  Estado final de la tarea '$tareaMov' es '$estadoFin'.";
        $log .= "\n  Sesión final de la tarea '$tareaMov' es '$sesionFin'.";
    } else {
        $log .= "\n  No se actualizó la sesión de la tarea '$tareaMov' porque \$sesionArr está vacío.";
    }
    guardarLog($log);
}

add_action('wp_ajax_actualizarOrdenTareas', 'actualizarOrdenTareas');


function borrarTareasCompletadas()
{
    if (isset($_POST['limpiar']) && $_POST['limpiar'] === 'true') {
        $usuarioActual = get_current_user_id();

        $args = array(
            'post_type'      => 'tarea',
            'author'         => $usuarioActual,
            'meta_query'     => array(
                array(
                    'key'   => 'estado',
                    'value' => 'completada',
                ),
            ),
            'posts_per_page' => -1,
        );

        $tareas = get_posts($args);

        if (empty($tareas)) {
            wp_send_json_error('No hay tareas completadas');
        } else {
            foreach ($tareas as $tarea) {
                wp_delete_post($tarea->ID, true);
            }
            wp_send_json_success('Tareas completadas borradas exitosamente');
        }
    } else {
        wp_send_json_error('No se solicitó limpiar');
    }
    wp_die();
}
add_action('wp_ajax_borrarTareasCompletadas', 'borrarTareasCompletadas');


function actualizarSesion()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $valAnt = isset($_POST['valorOriginal']) ? sanitize_text_field($_POST['valorOriginal']) : '';
    $valNue = isset($_POST['valorNuevo']) ? sanitize_text_field($_POST['valorNuevo']) : '';

    if (empty($valAnt) || empty($valNue)) {
        wp_send_json_error('Faltan datos.');
    }
    
    $log = "El usuario " . get_current_user_id() . " actualizo la sesion: $valAnt a: $valNue";

    $args = array(
        'post_type' => 'tarea',
        'posts_per_page' => -1,
        'author' => get_current_user_id(),
        'meta_query' => array(
            array(
                'key' => 'sesion',
                'value' => $valAnt,
                'compare' => '='
            )
        )
    );

    $tareas = get_posts($args);
    $cant = count($tareas);
    $log .= ", \n Se encontraron $cant tareas a modificar. ";

    if (empty($tareas)) {
        guardarLog("actualizarSesion:" . $log);
        wp_send_json_success('No se encontraron tareas para actualizar.');
    }

    foreach ($tareas as $tarea) {
        update_post_meta($tarea->ID, 'sesion', $valNue);
    }

    $log .= ", \n  Se actualizaron las sesiones de las tareas.";
    guardarLog("actualizarSesion:" . $log);
    wp_send_json_success();
}

add_action('wp_ajax_actualizarSesion', 'actualizarSesion');