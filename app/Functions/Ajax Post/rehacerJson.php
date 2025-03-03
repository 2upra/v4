<?

function corregirTags() {
    //guardarLog("rehaceJson.php: Iniciando corrección de tags");

    if (!is_user_logged_in()) {
        //guardarLog("rehaceJson.php: Usuario no está logueado");
        echo json_encode(['success' => false, 'message' => 'No estás autorizado']);
        wp_die();
    }

    $usr = wp_get_current_user();
    $id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $desc = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';
    //guardarLog("rehaceJson.php: Usuario: {$usr->user_login}, Post ID: {$id}, Descripción: {$desc}");

    if ($id <= 0) {
        //guardarLog("rehaceJson.php: ID de post no válido");
        echo json_encode(['success' => false, 'message' => 'ID de post no válido']);
        wp_die();
    }

    $pst = get_post($id);
    if (!$pst) {
        //guardarLog("rehaceJson.php: Post no encontrado con ID: {$id}");
        echo json_encode(['success' => false, 'message' => 'El post no existe']);
        wp_die();
    }

    if ($pst->post_author != $usr->ID && !current_user_can('administrator')) {
        //guardarLog("rehaceJson.php: Usuario {$usr->user_login} no tiene permisos para editar el post {$id}");
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este post']);
        wp_die();
    }

    rehacerJsonPost($pst->ID, $desc);
    //guardarLog("rehaceJson.php: Finalizando corrección de tags para post {$id}");
    echo json_encode(['success' => true]);
    wp_die();
}

function rehacerJsonPost($id, $desc) {
    //guardarLog("rehaceJson.php: Iniciando rehacerJsonPost para post {$id}");
    $audioLite = get_post_meta($id, 'post_audio_lite', true);
    if ($audioLite) {
        $audio = get_attached_file($audioLite);
        if ($audio) {
            //guardarLog("rehaceJson.php: Archivo de audio encontrado para post {$id}");
            rehacerJson($id, $audio, $desc);
        } else {
            //guardarLog("rehaceJson.php: Archivo de audio no encontrado para post {$id}");
        }
    } else {
        //guardarLog("rehaceJson.php: No se encontró audio_lite para post {$id}");
    }
}

function rehacerJson($id, $audio, $desc) {
    //guardarLog("rehaceJson.php: Iniciando rehacerJson para post {$id}");
    $datos = get_post_meta($id, 'datosAlgoritmo', true);

    if (!$datos) {
        //guardarLog("rehaceJson.php: No se encontraron datos del algoritmo para post {$id}");
        return;
    }

    if (is_array($datos)) {
        $datos = json_encode($datos, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            //guardarLog("rehaceJson.php: Error al codificar datos del algoritmo a JSON para post {$id}");
            return;
        }
    } elseif (!is_string($datos)) {
        //guardarLog("rehaceJson.php: Los datos del algoritmo no son un array ni una cadena para post {$id}");
        return;
    }

    $datosActuales = json_decode($datos, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        //guardarLog("rehaceJson.php: Error al decodificar datos del algoritmo desde JSON para post {$id}");
        return;
    }

    $prmpt = "El usuario ya subió este audio, pero esta pidiendo corregir los tags o informacion del siguiente json"
        . " Este es el mensaje, usa la informacion para corregir el JSON: \"{$desc}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva indicacion del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
        . json_encode($datosActuales, JSON_UNESCAPED_UNICODE)
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. No cambies las cosas si el usuario no lo pidio, sigue sus instrucciones. Muchas veces el usuario no se explicará bien, hay que intuir que hay que ajustar del json, generalmente es para cambiar uno o dos tags. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes. Y en este caso, el nombre corto no sea tan corto, 3 a 5 palabras";

    $descMejorada = generarDescripcionIA($audio, $prmpt);
    //guardarLog("rehaceJson.php: Descripción mejorada generada para post {$id}");

    if (!$descMejorada) {
        //guardarLog("rehaceJson.php: No se pudo generar la descripción mejorada para post {$id}");
        return;
    }

    $descMejoradaLimpia = preg_replace('/```(?:json)?\n/', '', $descMejorada);
    $descMejoradaLimpia = preg_replace('/\n```/', '', $descMejoradaLimpia);
    $datosActualizados = json_decode($descMejoradaLimpia, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        //guardarLog("rehaceJson.php: Datos actualizados decodificados correctamente para post {$id}");
        update_post_meta($id, 'datosAlgoritmo', json_encode($datosActualizados, JSON_UNESCAPED_UNICODE));
        $fecha = current_time('mysql');
        update_post_meta($id, 'ultimoEdit', $fecha);
        update_post_meta($id, 'proIA', false);

        $nomCorto = "";
        if (isset($datosActualizados['nombre_corto']['en']) && is_string($datosActualizados['nombre_corto']['en'])) {
            $nomCorto = $datosActualizados['nombre_corto']['en'];
        } elseif (isset($datosActualizados['nombre_corto']['en']) && is_array($datosActualizados['nombre_corto']['en'])) {
            //guardarLog("rehaceJson.php: Error: nombre_corto en es un array para post {$id}, usando el primer elemento");
            $nomCorto = reset($datosActualizados['nombre_corto']['en']); 
        } else{
            //guardarLog("rehaceJson.php: Error: nombre_corto no existe o no es string para post {$id}");
        }
        
        if (!empty($nomCorto)) {
            //guardarLog("rehaceJson.php: Actualizando post {$id} con nuevo título: {$nomCorto}");
            $data = array(
                'ID'           => $id,
                'post_title'   => $nomCorto,
                'post_name'    => sanitize_title($nomCorto),
                'post_content' => $nomCorto,
            );
            $actualizado = wp_update_post($data);
            if (is_wp_error($actualizado)) {
                //guardarLog("rehaceJson.php: Error al actualizar el post {$id}: {$actualizado->get_error_message()}");
            } else {
                //guardarLog("rehaceJson.php: Post {$id} actualizado correctamente");
            }
        } else {
            //guardarLog("rehaceJson.php: El nombre corto está vacío para post {$id}");
        }
    } else {
        //guardarLog("rehaceJson.php: Error al decodificar la descripción mejorada para post {$id}");
    }
}