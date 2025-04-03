<?php

// Permite cambiar la descripción de un post a través de una solicitud AJAX.
function cambiarDescripcion()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No autorizado']);
        return;
    }

    $usuarioActual = wp_get_current_user();
    $idPost = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

    if ($idPost <= 0) {
        wp_send_json_error(['message' => 'ID de post no válido']);
        return;
    }

    $post = get_post($idPost);
    if (!$post) {
        wp_send_json_error(['message' => 'El post no existe']);
        return;
    }

    if ($post->post_author != $usuarioActual->ID && !current_user_can('administrator')) {
        wp_send_json_error(['message' => 'No tienes permisos para editar este post']);
        return;
    }

    $post->post_content = wp_kses_post($descripcion);
    wp_update_post($post);
    rehacerDescripcionAccion($post->ID);

    wp_send_json_success();
}

// Activa la re-generación de la descripción del audio si existe un audio adjunto.
function rehacerDescripcionAccion($idPost)
{
    $audioLiteId = get_post_meta($idPost, 'post_audio_lite', true);
    if ($audioLiteId) {
        $archivoAudio = get_attached_file($audioLiteId);
        if ($archivoAudio) {
            rehacerDescripcionAudio($idPost, $archivoAudio);
        }
    }
}

// Regenera la descripción del audio utilizando IA basada en el contenido del post.
function rehacerDescripcionAudio($idPost, $archivoAudio)
{
    $contenidoPost = get_post_field('post_content', $idPost);
    if (!$contenidoPost) {
        return;
    }

    $datosAlgoritmo = get_post_meta($idPost, 'datosAlgoritmo', true);
    if (!$datosAlgoritmo) {
        return;
    }

    $datosActuales = json_decode($datosAlgoritmo, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return;
    }

    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción porque hay un dato incorrecto o un fallo en el json por ejemplo, no es un sample sino un one shot y viceversa, corrije cualquier cosa."
        . " Ten muy en cuenta la descripción nueva, es para corregir el JSON: \"{$contenidoPost}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva descripción del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
        . json_encode($datosActuales, JSON_UNESCAPED_UNICODE)
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes.";

    $descripcionMejorada = generarDescripcionIA($archivoAudio, $prompt);

    if (!$descripcionMejorada) {
        return;
    }

    $descripcionLimpia = preg_replace('/```(?:json)?\\n/', '', $descripcionMejorada);
    $descripcionLimpia = preg_replace('/\\n```/', '', $descripcionLimpia);
    $datosActualizados = json_decode($descripcionLimpia, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        update_post_meta($idPost, 'datosAlgoritmo', json_encode($datosActualizados, JSON_UNESCAPED_UNICODE));
        $fechaActual = current_time('mysql');
        update_post_meta($idPost, 'ultimoEdit', $fechaActual);
        update_post_meta($idPost, 'proIA', false);
    }
}

// Permite corregir los tags de un post a través de una solicitud AJAX.
function corregirTags()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No autorizado']);
        return;
    }

    $usuario = wp_get_current_user();
    $idPost = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

    if ($idPost <= 0) {
        wp_send_json_error(['message' => 'ID de post no válido']);
        return;
    }

    $post = get_post($idPost);
    if (!$post) {
        wp_send_json_error(['message' => 'El post no existe']);
        return;
    }

    if ($post->post_author != $usuario->ID && !current_user_can('administrator')) {
        wp_send_json_error(['message' => 'No tienes permisos para editar este post']);
        return;
    }

    rehacerJsonPost($post->ID, $descripcion);

    wp_send_json_success();
}

add_action('wp_ajax_corregirTags', 'corregirTags');

// Inicia el proceso para rehacer el JSON de un post.
function rehacerJsonPost($idPost, $descripcion)
{
    $audioLite = get_post_meta($idPost, 'post_audio_lite', true);
    if ($audioLite) {
        $audio = get_attached_file($audioLite);
        if ($audio) {
            rehacerJson($idPost, $audio, $descripcion);
        }
    }
}

// Re-genera el JSON utilizando IA basándose en el audio y la descripción proporcionada.
function rehacerJson($idPost, $audio, $descripcion)
{
    $datos = get_post_meta($idPost, 'datosAlgoritmo', true);

    if (!$datos) {
        return;
    }

    if (is_array($datos)) {
        $datos = json_encode($datos, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }
    } elseif (!is_string($datos)) {
        return;
    }

    $datosActuales = json_decode($datos, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return;
    }

    $prompt = "El usuario ya subió este audio, pero esta pidiendo corregir los tags o informacion del siguiente json"
        . " Este es el mensaje, usa la informacion para corregir el JSON: \"{$descripcion}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva indicacion del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
        . json_encode($datosActuales, JSON_UNESCAPED_UNICODE)
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. No cambies las cosas si el usuario no lo pidio, sigue sus instrucciones. Muchas veces el usuario no se explicará bien, hay que intuir que hay que ajustar del json, generalmente es para cambiar uno o dos tags. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes. Y en este caso, el nombre corto no sea tan corto, 3 a 5 palabras";

    $descripcionMejorada = generarDescripcionIA($audio, $prompt);

    if (!$descripcionMejorada) {
        return;
    }

    $descripcionLimpia = preg_replace('/```(?:json)?\n/', '', $descripcionMejorada);
    $descripcionLimpia = preg_replace('/\n```/', '', $descripcionLimpia);
    $datosActualizados = json_decode($descripcionLimpia, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        update_post_meta($idPost, 'datosAlgoritmo', json_encode($datosActualizados, JSON_UNESCAPED_UNICODE));
        $fecha = current_time('mysql');
        update_post_meta($idPost, 'ultimoEdit', $fecha);
        update_post_meta($idPost, 'proIA', false);

        $nombreCorto = "";
        if (isset($datosActualizados['nombre_corto']['en']) && is_string($datosActualizados['nombre_corto']['en'])) {
            $nombreCorto = $datosActualizados['nombre_corto']['en'];
        } elseif (isset($datosActualizados['nombre_corto']['en']) && is_array($datosActualizados['nombre_corto']['en'])) {
            $nombreCorto = reset($datosActualizados['nombre_corto']['en']);
        }

        if (!empty($nombreCorto)) {
            $data = array(
                'ID'           => $idPost,
                'post_title'   => $nombreCorto,
                'post_name'    => sanitize_title($nombreCorto),
                'post_content' => $nombreCorto,
            );
            $actualizado = wp_update_post($data);
        }
    }
}