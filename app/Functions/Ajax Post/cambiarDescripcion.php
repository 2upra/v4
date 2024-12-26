<?

# Rehace el json de los samples, su titulo, en base a la información que recibe del usuario. 
# Paso 1
function cambiarDescripcion()
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
    
    $post->post_content = wp_kses_post($descripcion);
    wp_update_post($post);
    rehacerDescripcionAccion($post->ID);
    echo json_encode(['success' => true]);
    wp_die();
}


#Pase 2
function rehacerDescripcionAccion($post_id)
{
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);
    if ($audio_lite_id) {
        $archivo_audio = get_attached_file($audio_lite_id);
        if ($archivo_audio) {
            rehacerDescripcionAudio($post_id, $archivo_audio);
        }
    }
}


#Paso 3
function rehacerDescripcionAudio($post_id, $archivo_audio)
{
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        return;
    }
    $datosAlgoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
    if (!$datosAlgoritmo) {
        return;
    }
    $datos_actuales = json_decode($datosAlgoritmo, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return;
    }
    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción porque hay un dato incorrecto o un fallo en el json por ejemplo, no es un sample sino un one shot y viceversa, corrije cualquier cosa."
        . " Ten muy en cuenta la descripción nueva, es para corregir el JSON: \"{$post_content}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva descripción del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
        . json_encode($datos_actuales, JSON_UNESCAPED_UNICODE)
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes.";
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
    }
}

