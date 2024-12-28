<?

#Los selectores sImportancia y sTipo ya abren los submenu que son los A1806241, al ejecutarse el script tiene que remplazar el texto ejemplo y poner el primer valor de los botones, esta info se guarda en variables globales, y si el usuario da click a otro boton, se cambia, dame esa parte del script en una funcion, js vanilla 


function formTarea()
{
    ob_start();
?>
    <div class="bloque tareasbloque">
        <input type="text" name="titulo" placeholder="Agregar nueva tarea" id="tituloTarea">

        <div class="selectorIcono sImportancia" id="sImportancia">
            <span class="icono"><?php echo $GLOBALS['importancia']; ?>Poca</span>
        </div>

        <div class="A1806241" id="sImportancia-sImportancia">
            <div class="A1806242">
                <button value="poca">Poca</button>
                <button value="media">Media</button>
                <button value="alta">Alta</button>
                <button value="urgente">Urgente</button>
            </div>
        </div>

        <div class="selectorIcono sTipo" id="sTipo">
            <span class="icono"><?php echo $GLOBALS['tipoTarea']; ?>Una vez</span>
        </div>

        <div class="A1806241" id="sTipo-sTipo">
            <div class="A1806242">
                <button value="una vez">Una vez</button>
                <button value="habito">Hábito</button>
                <button value="meta">Meta</button>
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
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }
    $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
    $importancia = isset($_POST['importancia']) ? sanitize_text_field($_POST['importancia']) : '';
    $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
    if (empty($titulo)) {
        wp_send_json_error('Título vacío.');
    }
    $args = array(
        'post_title' => $titulo,
        'post_type' => 'tarea',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'meta_input' => array(
            'importancia' => $importancia,
            'tipo' => $tipo,
            'estado' => 'pendiente',
        ),
    );
    $tareaId = wp_insert_post($args);

    if (is_wp_error($tareaId)) {
        $msg = $tareaId->get_error_message();
        guardarLog("Error al crear tarea: $msg");
        wp_send_json_error($msg);
    }
    guardarLog("Tarea creada id: $tareaId");
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
        guardarLog("modificarTarea - Error: No se encontró la tarea con ID $id");
        wp_send_json_error('Tarea no encontrada.');
    }

    $args = array(
        'ID' => $id,
        'post_title' => $tit
    );

    $res = wp_update_post($args, true);

    if (is_wp_error($res)) {
        $msg = $res->get_error_message();
        guardarLog("modificarTarea - Error al modificar tarea: $msg");
        wp_send_json_error($msg);
    }

    guardarLog("modificarTarea - Tarea modificada id: $id");
    wp_send_json_success();
}

add_action('wp_ajax_modificarTarea', 'modificarTarea');

function completarTarea() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : 'pendiente';

    $tarea = get_post($id);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        guardarLog("completarTarea - Error: No se encontró la tarea con ID $id");
        wp_send_json_error('Tarea no encontrada.');
    }

    update_post_meta($id, 'estado', $estado);

    guardarLog("completarTarea - Tarea $id actualizada a estado: $estado");
    wp_send_json_success();
}

add_action('wp_ajax_completarTarea', 'completarTarea');
