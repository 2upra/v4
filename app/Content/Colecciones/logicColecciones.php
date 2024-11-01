<?


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

function botonColeccion($postId)
{
    ob_start();
?>
    <div class="ZAQIBB botonColeccion">
        <button class="botonColeccionBtn" data-post_id="<? echo esc_attr($postId) ?>" data-nonce="<? echo wp_create_nonce('colec_nonce') ?>">
            <? echo $GLOBALS['iconoGuardar']; ?>
        </button>
    </div>

<?
}

function guardarSampleEnColec()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autorizado'));
        return;
    }

    $sample_id = isset($_POST['colecSampleId']) ? intval($_POST['colecSampleId']) : 0;
    $coleccion_id = isset($_POST['colecSelecionado']) ? $_POST['colecSelecionado'] : '';
    $privado = isset($_POST['privado']) ? intval($_POST['privado']) : 0;
    $current_user_id = get_current_user_id();

    if (!$sample_id || !$coleccion_id) {
        wp_send_json_error(array('message' => 'Datos inválidos'));
        return;
    }

    // Manejar colecciones especiales
    if ($coleccion_id === 'favoritos' || $coleccion_id === 'despues') {
        $coleccion_especial_id = get_user_meta($current_user_id, $coleccion_id . '_coleccion_id', true);

        if (!$coleccion_especial_id) {
            $titulo = ($coleccion_id === 'favoritos') ? 'Favoritos' : 'Usar más tarde';
            $imagen_url = ($coleccion_id === 'favoritos')
                ? 'https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg'
                : 'https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg';

            $coleccion_especial_id = wp_insert_post([
                'post_title'    => $titulo,
                'post_type'     => 'colecciones',
                'post_status'   => 'publish',
                'post_author'   => $current_user_id,
            ]);

            if (!is_wp_error($coleccion_especial_id)) {
                update_user_meta($current_user_id, $coleccion_id . '_coleccion_id', $coleccion_especial_id);
                $image_id = subirImagenDesdeURL($imagen_url, $coleccion_especial_id);
                if ($image_id) {
                    set_post_thumbnail($coleccion_especial_id, $image_id);
                }
            } else {
                wp_send_json_error(array('message' => 'Error al crear la colección especial'));
                return;
            }
        }
        $coleccion_id = $coleccion_especial_id;
    }

    // Verificar que la colección existe y pertenece al usuario
    $coleccion = get_post($coleccion_id);
    if (!$coleccion || $coleccion->post_author != $current_user_id) {
        wp_send_json_error(array('message' => 'No tienes permiso para modificar esta colección'));
        return;
    }

    // Si 'privado' es 1, actualizar la meta 'privado' en la colección
    if ($privado === 1) {
        update_post_meta($coleccion_id, 'privado', 1);
    } else {
        //delete_post_meta($coleccion_id, 'privado'); // Opcional: elimina la meta si no es privada
    }

    // Obtener y actualizar los samples
    $samples = get_post_meta($coleccion_id, 'samples', true);
    if (!is_array($samples)) {
        $samples = array();
    }

    if (in_array($sample_id, $samples)) {
        wp_send_json_error(array('message' => 'Este sample ya existe en la colección'));
        return;
    }

    $samples[] = $sample_id;
    $updated = update_post_meta($coleccion_id, 'samples', $samples);

    if ($updated) {
        wp_send_json_success(array(
            'message' => 'Sample agregado exitosamente',
            'samples' => $samples
        ));
    } else {
        wp_send_json_error(array('message' => 'Error al guardar el sample en la colección'));
    }
}


function crearColeccion()
{
    if (!is_user_logged_in()) {
        guardarLog("Error: Usuario no autenticado");
        wp_send_json_error(['error' => 'Usuario no autenticado']);
    }

    // Verificar y sanear los datos recibidos
    $colecSampleId = isset($_POST['colecSampleId']) ? intval($_POST['colecSampleId']) : 0;
    $imgColec = isset($_POST['imgColec']) ? $_POST['imgColec'] : '';
    // Si la imagen es http://null o null, establecerla como cadena vacía
    $imgColec = ($imgColec === 'http://null' || $imgColec === 'null') ? '' : esc_url_raw($imgColec);
    $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
    $imgColecId = isset($_POST['imgColecId']) ? sanitize_text_field($_POST['imgColecId']) : '';
    $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';

    guardarLog("Datos recibidos: colecSampleId=$colecSampleId, imgColec=$imgColec, titulo=$titulo, imgColecId=$imgColecId, descripcion=$descripcion");

    // Validar título obligatorio
    if (empty($titulo)) {
        guardarLog("Error: El título de la colección es obligatorio");
        wp_send_json_error(['error' => 'El título de la colección es obligatorio']);
    }

    // Comprobar cuántas colecciones tiene el usuario actualmente
    $user_id = get_current_user_id();
    $user_collections_count = new WP_Query([
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    guardarLog("Colecciones actuales del usuario $user_id: " . $user_collections_count->found_posts);

    // Verificar si el usuario ya tiene 50 colecciones
    if ($user_collections_count->found_posts >= 50) {
        guardarLog("Error: Límite de colecciones alcanzado para el usuario $user_id");
        wp_send_json_error(['error' => 'Has alcanzado el límite de 50 colecciones']);
    }

    // Crear la colección ya que no existe ninguna limitación
    $coleccionId = wp_insert_post([
        'post_title'    => $titulo,
        'post_content'  => $descripcion,
        'post_type'     => 'colecciones',
        'post_status'   => 'publish',
        'post_author'   => $user_id,
    ]);

    if (!$coleccionId) {
        guardarLog("Error: Error al crear la colección");
        wp_send_json_error(['message' => 'Error al crear la colección']);;
    }

    guardarLog("Colección creada exitosamente: ID $coleccionId");

    // Establecer la imagen destacada solo si hay una URL válida
    if (!empty($imgColec)) {
        $image_id = subirImagenDesdeURL($imgColec, $coleccionId);
        if ($image_id) {
            set_post_thumbnail($coleccionId, $image_id);
            guardarLog("Imagen destacada establecida con ID $image_id para la colección $coleccionId");
        }
    }

    // Guardar el imgColecId en la meta si existe y no es 'null'
    if (!empty($imgColecId) && $imgColecId !== 'null') {
        update_post_meta($coleccionId, 'imgColecId', $imgColecId);
        guardarLog("Meta imgColecId guardada con valor $imgColecId para la colección $coleccionId");
    }

    // Inicializar la meta 'samples' con el postId proporcionado
    update_post_meta($coleccionId, 'samples', json_encode([$colecSampleId]));
    guardarLog("Meta 'samples' inicializada con colecSampleId $colecSampleId para la colección $coleccionId");

    wp_send_json_success(['message' => 'Colección creada exitosamente']);
    wp_die();
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
add_action('wp_ajax_guardarSampleEnColec', 'guardarSampleEnColec');
add_action('wp_ajax_removerPostDeColeccion', 'removerPostDeColeccion');
