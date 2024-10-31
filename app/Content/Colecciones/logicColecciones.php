<?


function crearColeccion()
{
    if (!is_user_logged_in()) {
        return json_encode(['error' => 'Usuario no autenticado']);
    }

    // Verificar y sanear los datos recibidos
    $colecPostId = isset($_POST['colecPostId']) ? intval($_POST['colecPostId']) : 0;
    $colecSelecionado = isset($_POST['colecSelecionado']) ? sanitize_text_field($_POST['colecSelecionado']) : '';
    $imgColec = isset($_POST['imgColec']) ? esc_url_raw($_POST['imgColec']) : '';
    $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
    $imgColecId = isset($_POST['imgColecId']) ? sanitize_text_field($_POST['imgColecId']) : '';
    $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';

    // Validar título obligatorio
    if (empty($titulo)) {
        return json_encode(['error' => 'El título de la colección es obligatorio']);
    }

    // Verificar si se está usando un id o un nombre para la colección
    $coleccionId = 0;
    if (is_numeric($colecSelecionado)) {
        $coleccionId = intval($colecSelecionado);
    } else {
        // Buscar una colección existente por título y del usuario actual
        $query = new WP_Query([
            'title'          => $colecSelecionado,
            'post_type'      => 'colecciones',
            'post_status'    => 'publish',
            'author'         => get_current_user_id(),
            'posts_per_page' => 1,
        ]);

        if ($query->have_posts()) {
            $existing_colec = $query->posts[0];
            $coleccionId = $existing_colec->ID;
        }
        wp_reset_postdata();
    }

    // Si la colección ya existe, solo actualizar 'samples' y evitar duplicados
    if ($coleccionId) {
        $samples = get_post_meta($coleccionId, 'samples', true);
        $samples = $samples ? json_decode($samples, true) : [];
        if (!in_array($colecPostId, $samples)) {
            $samples[] = $colecPostId;
            update_post_meta($coleccionId, 'samples', json_encode($samples));
        }
    } else {
        // Crear la colección si no existe
        $coleccionId = wp_insert_post([
            'post_title'    => $titulo,
            'post_content'  => $descripcion,
            'post_type'     => 'colecciones',
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
        ]);

        if (!$coleccionId) {
            return json_encode(['error' => 'Error al crear la colección']);
        }

        // Establecer la imagen destacada si se proporciona la URL de la imagen
        if ($imgColec) {
            $image_id = subirImagenDesdeURL($imgColec, $coleccionId);
            if ($image_id) {
                set_post_thumbnail($coleccionId, $image_id);
            }
        }

        // Guardar el imgColecId en la meta si existe
        if (!empty($imgColecId)) {
            update_post_meta($coleccionId, 'imgColecId', $imgColecId);
        }

        // Inicializar la meta 'samples' con el postId proporcionado
        update_post_meta($coleccionId, 'samples', json_encode([$colecPostId]));
    }

    return json_encode(['success' => true, 'coleccionId' => $coleccionId]);
}


# Ajusta editar coleccion en consecuencia, esta desactualizada
function editarColeccion()
{
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

function agregarPostAColeccion()
{
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

function removerPostDeColeccion()
{
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

function eliminarColeccion()
{
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
