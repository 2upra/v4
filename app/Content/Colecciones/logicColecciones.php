<?


function crearColeccion() {
    if (!is_user_logged_in()) {
        return json_encode(['error' => 'Usuario no autenticado']);
    }

    // Verificar y sanear los datos recibidos
    $postId = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
    $nameColec = isset($_POST['nameColec']) ? sanitize_text_field($_POST['nameColec']) : '';
    $descriptionColec = isset($_POST['descriptionColec']) ? sanitize_textarea_field($_POST['descriptionColec']) : '';
    $tagsColec = isset($_POST['tagsColec']) ? array_map('sanitize_text_field', $_POST['tagsColec']) : [];
    $imageURL = isset($_POST['image']) ? esc_url_raw($_POST['image']) : '';

    // Validar nombre obligatorio
    if (empty($nameColec)) {
        return json_encode(['error' => 'El nombre de la colección es obligatorio']);
    }

    // Crear el post de tipo colección
    $coleccionId = wp_insert_post([
        'post_title'    => $nameColec,
        'post_content'  => $descriptionColec,
        'post_type'     => 'colecciones',
        'post_status'   => 'publish',
        'post_author'   => get_current_user_id(),
    ]);

    // Si se creó correctamente la colección
    if ($coleccionId) {
        // Establecer la imagen destacada si se proporcionó una URL
        if ($imageURL) {
            // Descargar la imagen y adjuntarla como imagen destacada
            $image_id = subirImagenDesdeURL($imageURL, $coleccionId);
            if ($image_id) {
                set_post_thumbnail($coleccionId, $image_id);
            }
        }

        // Guardar los tags en la meta 'tagsColec'
        if (!empty($tagsColec)) {
            update_post_meta($coleccionId, 'tagsColec', $tagsColec);
        }

        // Inicializar la meta 'samples' como un array JSON con el postId recibido
        $samples = [$postId];
        update_post_meta($coleccionId, 'samples', json_encode($samples));

        return json_encode(['success' => true, 'coleccionId' => $coleccionId]);
    } else {
        return json_encode(['error' => 'Error al crear la colección']);
    }
}

# Ajusta editar coleccion en consecuencia, esta desactualizada
function editarColeccion() {
    if (!is_user_logged_in()) {
        return json_encode(['error' => 'Usuario no autenticado']);
    }

    $coleccionId = isset($_POST['coleccionId']) ? intval($_POST['coleccionId']) : 0;
    $userId = get_current_user_id();
    $coleccion = get_post($coleccionId);

    if ($coleccion && $coleccion->post_author == $userId) {
        // Sanear los datos recibidos
        $nameColec = isset($_POST['nameColec']) ? sanitize_text_field($_POST['nameColec']) : '';
        $descriptionColec = isset($_POST['descriptionColec']) ? sanitize_textarea_field($_POST['descriptionColec']) : '';
        $tagsColec = isset($_POST['tagsColec']) ? array_map('sanitize_text_field', $_POST['tagsColec']) : [];
        $imageURL = isset($_POST['image']) ? esc_url_raw($_POST['image']) : '';

        // Actualizar el título y la descripción
        wp_update_post([
            'ID'           => $coleccionId,
            'post_title'   => $nameColec,
            'post_content' => $descriptionColec,
        ]);

        // Actualizar los tags en la meta 'tagsColec'
        if (!empty($tagsColec)) {
            update_post_meta($coleccionId, 'tagsColec', $tagsColec);
        } else {
            delete_post_meta($coleccionId, 'tagsColec');
        }

        // Actualizar la imagen destacada si se proporcionó una nueva URL
        if ($imageURL) {
            $image_id = subirImagenDesdeURL($imageURL, $coleccionId);
            if ($image_id) {
                set_post_thumbnail($coleccionId, $image_id);
            }
        }

        return json_encode(['success' => true]);
    } else {
        return json_encode(['error' => 'No tienes permisos para editar esta colección']);
    }
}

function agregarPostAColeccion() {
    if (!is_user_logged_in()) {
        return json_encode(['error' => 'Usuario no autenticado']);
    }

    $coleccionId = isset($_POST['coleccionId']) ? intval($_POST['coleccionId']) : 0;
    $nuevoPostId = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
    $userId = get_current_user_id();
    $coleccion = get_post($coleccionId);

    if (!$coleccion) {
        return json_encode(['error' => 'Colección no encontrada']);
    }

    if ($coleccion->post_author != $userId) {
        return json_encode(['error' => 'No tienes permisos para modificar esta colección']);
    }

    // Obtener la meta 'samples' actual
    $samples_json = get_post_meta($coleccionId, 'samples', true);
    $samples = !empty($samples_json) ? json_decode($samples_json, true) : [];

    if (!is_array($samples)) {
        $samples = [];
    }

    // Evitar duplicados
    if (!in_array($nuevoPostId, $samples)) {
        $samples[] = $nuevoPostId;
        update_post_meta($coleccionId, 'samples', json_encode($samples));
        return json_encode(['success' => true, 'samples' => $samples]);
    } else {
        return json_encode(['error' => 'El post ya está en la colección']);
    }
}

function removerPostDeColeccion() {
    if (!is_user_logged_in()) {
        return json_encode(['error' => 'Usuario no autenticado']);
    }

    $coleccionId = isset($_POST['coleccionId']) ? intval($_POST['coleccionId']) : 0;
    $postId = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
    $userId = get_current_user_id();
    $coleccion = get_post($coleccionId);

    if (!$coleccion) {
        return json_encode(['error' => 'Colección no encontrada']);
    }

    if ($coleccion->post_author != $userId) {
        return json_encode(['error' => 'No tienes permisos para modificar esta colección']);
    }

    // Obtener la meta 'samples' actual
    $samples_json = get_post_meta($coleccionId, 'samples', true);
    $samples = !empty($samples_json) ? json_decode($samples_json, true) : [];

    if (!is_array($samples)) {
        $samples = [];
    }

    // Buscar y remover el postId
    $key = array_search($postId, $samples);
    if ($key !== false) {
        unset($samples[$key]);
        $samples = array_values($samples); // Reindexar el array
        update_post_meta($coleccionId, 'samples', json_encode($samples));
        return json_encode(['success' => true, 'samples' => $samples]);
    } else {
        return json_encode(['error' => 'El post no se encuentra en la colección']);
    }
}

function eliminarColeccion() {
    if (!is_user_logged_in()) {
        return json_encode(['error' => 'Usuario no autenticado']);
    }

    $coleccionId = isset($_POST['coleccionId']) ? intval($_POST['coleccionId']) : 0;
    $userId = get_current_user_id();
    $coleccion = get_post($coleccionId);

    if ($coleccion && $coleccion->post_author == $userId) {
        wp_delete_post($coleccionId, true);
        return json_encode(['success' => true]);
    } else {
        return json_encode(['error' => 'No tienes permisos para eliminar esta colección']);
    }
}

add_action('wp_ajax_crearColeccion', 'crearColeccion');
add_action('wp_ajax_editarColeccion', 'editarColeccion');
add_action('wp_ajax_eliminarColeccion', 'eliminarColeccion');
add_action('wp_ajax_agregarPostAColeccion', 'agregarPostAColeccion');
add_action('wp_ajax_removerPostDeColeccion', 'removerPostDeColeccion');

