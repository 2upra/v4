<?php

// Refactor(Org): Funcion empezarColab() y su hook movidos desde app/Content/Colab/logicColab.php

function empezarColab()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
    }

    $postId = isset($_POST['postId']) ? intval($_POST['postId']) : null;
    $fileId = isset($_POST['fileId']) ? intval($_POST['fileId']) : null;
    $mensaje = isset($_POST['mensaje']) ? sanitize_textarea_field($_POST['mensaje']) : '';
    $fileUrl = isset($_POST['fileUrl']) ? esc_url_raw($_POST['fileUrl']) : '';

    if (!$postId) {
        guardarLog('No se ha proporcionado el ID de la publicación');
        wp_send_json_error(['message' => 'No se ha proporcionado el ID de la publicación']);
    }

    $original_post = get_post($postId);
    if (!$original_post) {
        guardarLog('Publicación no encontrada');
        wp_send_json_error(['message' => 'Publicación no encontrada']);
    }

    $current_user_id = get_current_user_id();
    if ($current_user_id === $original_post->post_author) {
        wp_send_json_error(['message' => 'No puedes colaborar contigo mismo.']);
    }

    // Verificar si el usuario actual ya ha iniciado una colaboración
    $existing_colabs = get_post_meta($postId, 'colabs', true) ?: [];
    if (in_array($current_user_id, $existing_colabs)) {
        wp_send_json_error(['message' => 'Ya existe una colaboración existente para esta publicación']);
    }

    $author_name = get_the_author_meta('display_name', $original_post->post_author);
    $collaborator_name = get_the_author_meta('display_name', $current_user_id);

    $newPostId = wp_insert_post([
        'post_author' => $original_post->post_author,
        'post_title' => "Colab entre $author_name y $collaborator_name",
        'post_type' => 'colab',
        'post_status' => 'pending',
        'meta_input' => [
            'colabPostOrigen' => $postId,
            'colabAutor' => $original_post->post_author,
            'colabColaborador' => $current_user_id,
            'colabMensaje' => $mensaje,
            'participantes' => [$original_post->post_author, $current_user_id]
        ],
    ]);

    if ($newPostId) {
        guardarLog("Colaboración creada con ID: $newPostId");

        global $wpdb;
        $tablaConversacion = $wpdb->prefix . 'conversacion';
        $tipo_conversacion = 2;
        $participantes_conversacion = json_encode([$original_post->post_author, $current_user_id]);
        $fecha_conversacion = current_time('mysql');

        $insert_conversacion = $wpdb->insert(
            $tablaConversacion,
            [
                'tipo' => $tipo_conversacion,
                'participantes' => $participantes_conversacion,
                'fecha' => $fecha_conversacion,
            ],
            [
                '%d',
                '%s',
                '%s',
            ]
        );

        if ($insert_conversacion) {
            $conversacion_id = $wpdb->insert_id;
            update_post_meta($newPostId, 'conversacion_id', $conversacion_id);
            update_post_meta($newPostId, 'participantes', [$original_post->post_author, $current_user_id]);

            guardarLog("Conversación creada con ID: $conversacion_id");
        } else {
            guardarLog('Error al crear la conversación en la base de datos');
            wp_send_json_error(['message' => 'Error al crear la conversación']);
        }

        $existing_colabs[] = $current_user_id;
        update_post_meta($postId, 'colabs', $existing_colabs);

        // Crear o actualizar el meta de participantes para el post original
        $participantes = get_post_meta($postId, 'participantes', true) ?: [];
        if (!in_array($current_user_id, $participantes)) {
            $participantes[] = $current_user_id;
            update_post_meta($postId, 'participantes', $participantes);
        }

        // Asociar el archivo desde el URL si ya existe
        // Note: Requires adjuntarArchivo() to be available globally or included.
        if (!empty($fileUrl)) {
            $attached = adjuntarArchivo($newPostId, $fileUrl);
            if (!$attached) {
                wp_send_json_error(['message' => 'No se pudo adjuntar el archivo correctamente.']);
            }
        }

        // Confirmar el archivo por ID si se proporciona
        // Note: Requires confirmarHashId() to be available globally or included.
        if ($fileId) {
            confirmarHashId($fileId);
            guardarLog("Archivo $fileId confirmado");
        }

        wp_send_json_success(['message' => 'Colaboración iniciada correctamente']);
    } else {
        guardarLog('Error al crear la colaboración');
        wp_send_json_error(['message' => 'Error al crear la colaboración']);
    }

    wp_die();
}

add_action('wp_ajax_empezarColab', 'empezarColab');

// Refactor(Exec): Función variablesColab() movida desde app/Content/Colab/partColab.php
function variablesColab($post_id = null)
{
    if ($post_id === null) {
        global $post;
        $post_id = $post->ID;
    }

    $current_user_id = get_current_user_id();
    $colabPostOrigen = get_post_meta($post_id, 'colabPostOrigen', true);
    $colabAutor = get_post_meta($post_id, 'colabAutor', true);
    $colabColaborador = get_post_meta($post_id, 'colabColaborador', true);
    $colabMensaje = get_post_meta($post_id, 'colabMensaje', true);
    $colabFileUrl = get_post_meta($post_id, 'colabFileUrl', true);
    $post_audio_lite = get_post_meta($post_id, 'post_audio_lite', true);
    $participantes = get_post_meta($post_id, 'participantes', true);
    $conversacion_id = get_post_meta($post_id, 'conversacion_id', true);

    $imagenPost = get_the_post_thumbnail_url($post_id, 'full');
    if (!$imagenPost) {
        $imagenPost = 'https://i0.wp.com/2upra.com/wp-content/uploads/2024/09/1ndoryu_1725478496.webp?quality=40&strip=all';
    }
    $imagenPostOp = img($imagenPost, 40, 'all');

    $postTitulo = get_the_title($post_id);

    return [
        'post_id' => $post_id,
        'conversacion_id' => $conversacion_id,
        'participantes' => $participantes,
        'post_audio_lite' => $post_audio_lite,
        'current_user_id' => $current_user_id,
        'colabPostOrigen' => $colabPostOrigen,
        'colabAutor' => $colabAutor,
        'colabColaborador' => $colabColaborador,
        'colabMensaje' => $colabMensaje,
        'colabFileUrl' => $colabFileUrl,
        'colabAutorName' => get_the_author_meta('display_name', $colabAutor),
        'colabColaboradorName' => get_the_author_meta('display_name', $colabColaborador),
        'colabColaboradorAvatar' => imagenPerfil($colabColaborador),
        'colabAutorAvatar' => imagenPerfil($colabAutor),
        'colabFecha' => get_the_date('', $post_id),
        'colab_status' => get_post_status($post_id),
        'imagenPostOp' => $imagenPostOp,
        'postTitulo' => $postTitulo, // Añadir el título del post
    ];
}
