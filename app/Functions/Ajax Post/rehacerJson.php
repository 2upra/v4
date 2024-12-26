<?

function corregirTags()
{

    if (!is_user_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'No estás autorizado']);
        wp_die();
    }

    $current_user = wp_get_current_user();
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de post no válido']);
        wp_die();
    }


    $post = get_post($post_id);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'El post no existe']);
        wp_die();
    }

    if ($post->post_author != $current_user->ID && !current_user_can('administrator')) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este post']);
        wp_die();
    }

    rehacerJsonPost($post->ID, $descripcion);
    echo json_encode(['success' => true]);
    wp_die();
}

function rehacerJsonPost($post_id, $descripcion)
{
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);
    if ($audio_lite_id) {
        $archivo_audio = get_attached_file($audio_lite_id);
        if ($archivo_audio) {
            rehacerJson($post_id, $archivo_audio, $descripcion);
        }
    }
}
function rehacerJson($post_id, $archivo_audio, $descripcion)
{
    $datosAlgoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
    if (!$datosAlgoritmo) {
        return;
    }
    if (is_array($datosAlgoritmo)) {
        $datosAlgoritmo = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }
    } elseif (!is_string($datosAlgoritmo)) {
        return;
    }
    $datos_actuales = json_decode($datosAlgoritmo, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return;
    }
    $prompt = "El usuario ya subió este audio, pero esta pidiendo corregir los tags o informacion del siguiente json"
        . " Este es el mensaje, usa la informacion para corregir el JSON: \"{$descripcion}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva indicacion del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
        . json_encode($datos_actuales, JSON_UNESCAPED_UNICODE)
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. No cambies las cosas si el usuario no lo pidio, sigue sus instrucciones. Muchas veces el usuario no se explicará bien, hay que intuir que hay que ajustar del json, generalmente es para cambiar uno o dos tags. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes. Y en este caso, el nombre corto no sea tan corto, 3 a 5 palabras";
    $descripcion_mejorada = generarDescripcionIA($archivo_audio, $prompt);

    if (!$descripcion_mejorada) {
        return;
    }

    $descripcion_mejorada_limpia = preg_replace('/```(?:json)?\n/', '', $descripcion_mejorada);
    $descripcion_mejorada_limpia = preg_replace('/\n```/', '', $descripcion_mejorada_limpia);
    $datos_actualizados = json_decode($descripcion_mejorada_limpia, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_actualizados, JSON_UNESCAPED_UNICODE));
        $fecha_actual = current_time('mysql');
        update_post_meta($post_id, 'ultimoEdit', $fecha_actual);
        update_post_meta($post_id, 'proIA', false);

        $nombre_corto_en = $datos_actualizados['nombre_corto']['en'];

        if (!empty($nombre_corto_en)) {
            $post_data = array(
                'ID'           => $post_id,
                'post_title'   => $nombre_corto_en,
                'post_name'    => sanitize_title($nombre_corto_en), 
                'post_content' => $nombre_corto_en,
            );
            wp_update_post($post_data);
        }
    }
}