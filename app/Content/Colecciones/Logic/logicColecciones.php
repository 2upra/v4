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

//para mejorar la logica, se puede simplificar crearColeccion para que guarde el sample usando guardarSampleEnColec, hay que mantener la capacidad ajax de ambas funciones
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
    $privado = isset($_POST['privado']) ? intval($_POST['privado']) : 0;

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
        wp_send_json_error(['message' => 'Error al crear la colección']);
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

    if ($privado === 1) {
        update_post_meta($coleccionId, 'privado', 1);
    } else {
        //delete_post_meta($coleccionId, 'privado'); // Opcional: elimina la meta si no es privada
    }

    // Guardar el imgColecId en la meta si existe y no es 'null'
    if (!empty($imgColecId) && $imgColecId !== 'null') {
        update_post_meta($coleccionId, 'imgColecId', $imgColecId);
        guardarLog("Meta imgColecId guardada con valor $imgColecId para la colección $coleccionId");
    }

    // Inicializar la meta 'samples' con el postId proporcionado usando la función auxiliar
    $resultado = añadirSampleEnColab($coleccionId, $colecSampleId, $user_id);

    if (!$resultado['success']) {
        guardarLog("Error al agregar el sample inicial: " . $resultado['message']);
        wp_delete_post($coleccionId, true); // Opcional: elimina la colección si no se puede agregar el sample
        wp_send_json_error(['message' => $resultado['message']]);
    }

    guardarLog("Meta 'samples' inicializada con colecSampleId $colecSampleId para la colección $coleccionId");

    wp_send_json_success(['message' => 'Colección creada exitosamente']);
    wp_die();
}

function guardarSampleEnColec()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Usuario no autorizado']);
        return;
    }

    $sample_id = isset($_POST['colecSampleId']) ? intval($_POST['colecSampleId']) : 0;
    $coleccion_id = isset($_POST['colecSelecionado']) ? $_POST['colecSelecionado'] : '';
    $current_user_id = get_current_user_id();

    if (!$sample_id || !$coleccion_id) {
        wp_send_json_error(['message' => 'Datos inválidos']);
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
                update_post_meta($coleccion_especial_id, coleccionEspecial, $titulo);
                $image_id = subirImagenDesdeURL($imagen_url, $coleccion_especial_id);
                if ($image_id) {
                    set_post_thumbnail($coleccion_especial_id, $image_id);
                }
            } else {
                wp_send_json_error(['message' => 'Error al crear la colección especial']);
                return;
            }
        }
        $coleccion_id = $coleccion_especial_id;
    }

    // Utilizar la función auxiliar para agregar el sample
    $resultado = añadirSampleEnColab($coleccion_id, $sample_id, $current_user_id);

    if ($resultado['success']) {
        wp_send_json_success([
            'message' => $resultado['message'],
            'samples' => $resultado['samples']
        ]);
    } else {
        wp_send_json_error(['message' => $resultado['message']]);
    }
}


function añadirSampleEnColab($collection_id, $sample_id, $user_id)
{
    $collection = get_post($collection_id);
    if (!$collection || $collection->post_author != $user_id) {
        return [
            'success' => false,
            'message' => 'No tienes permiso para modificar esta colección'
        ];
    }

    // Obtener los samples actuales en la colección
    $samples = get_post_meta($collection_id, 'samples', true);
    if (!is_array($samples)) {
        $samples = array();
    }

    // Verificar si el sample ya está en la colección
    if (in_array($sample_id, $samples)) {
        return [
            'success' => false,
            'message' => 'Este sample ya existe en la colección'
        ];
    }

    // Agregar el nuevo sample
    $samples[] = $sample_id;
    $updated = update_post_meta($collection_id, 'samples', $samples);

    if ($updated) {

        update_post_meta($collection_id, 'ultimaModificacion', current_time('mysql'));
        $samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true);
        if (!is_array($samplesGuardados)) {
            $samplesGuardados = array();
        }
        if (!isset($samplesGuardados[$sample_id])) {
            $samplesGuardados[$sample_id] = [];
        }
        $samplesGuardados[$sample_id][] = $collection_id;
        update_user_meta($user_id, 'samplesGuardados', $samplesGuardados);

        return [
            'success' => true,
            'message' => 'Sample agregado exitosamente',
            'samples' => $samples
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al guardar el sample en la colección'
        ];
    }
}



function botonColeccion($postId)
{
    ob_start();
?>
    <div class="ZAQIBB botonColeccion">
        <button class="botonColeccionBtn" aria-label="Guardar sonido" data-post_id="<? echo esc_attr($postId) ?>" data-nonce="<? echo wp_create_nonce('colec_nonce') ?>">
            <? echo $GLOBALS['iconoGuardar']; ?>
        </button>
    </div>

<?
}

function eliminarSampledeColec()
{
    // Verificar si el usuario está logueado
    if (!is_user_logged_in()) {
        wp_send_json_error(['error' => 'Usuario no autenticado']);
        return;
    }

    // Obtener los datos de la petición
    $coleccionId = isset($_POST['coleccion_id']) ? intval($_POST['coleccion_id']) : 0;
    $sample_id = isset($_POST['sample_id']) ? intval($_POST['sample_id']) : 0;
    $userId = get_current_user_id();
    $coleccion = get_post($coleccionId);

    // Verificar si la colección existe
    if (!$coleccion) {
        wp_send_json_error(['error' => 'Colección no encontrada']);
        return;
    }

    // Verificar que el usuario sea el propietario de la colección
    if ($coleccion->post_author != $userId) {
        wp_send_json_error(['error' => 'No tienes permisos para modificar esta colección']);
        return;
    }

    // Obtener la meta 'samples' actual
    $samples = get_post_meta($coleccionId, 'samples', true);
    if (!is_array($samples)) {
        $samples = [];
    }

    // Buscar y remover el sample_id de la colección
    $key = array_search($sample_id, $samples);
    if ($key !== false) {
        unset($samples[$key]); // Remover el sample del array
        $samples = array_values($samples); // Reindexar el array
        update_post_meta($coleccionId, 'samples', $samples); // Actualizar el meta

        // Eliminar el registro del sample en los metadatos del usuario
        $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
        if (isset($samplesGuardados[$sample_id])) {
            // Buscar y eliminar la colección específica del sample
            $index = array_search($coleccionId, $samplesGuardados[$sample_id]);
            if ($index !== false) {
                unset($samplesGuardados[$sample_id][$index]);
                $samplesGuardados[$sample_id] = array_values($samplesGuardados[$sample_id]); // Reindexar el array

                // Si no quedan colecciones para el sample, eliminar la entrada del sample en los metadatos
                if (empty($samplesGuardados[$sample_id])) {
                    unset($samplesGuardados[$sample_id]);
                }
            }
        }

        // Actualizar los metadatos del usuario
        update_user_meta($userId, 'samplesGuardados', $samplesGuardados);

        wp_send_json_success(['message' => 'Sample eliminado de colección']);
    } else {
        wp_send_json_error(['message' => 'No se encontró el sample en la colección']);
    }
}

add_action('wp_ajax_eliminarSampledeColec', 'eliminarSampledeColec');

function borrarColec()
{
    if (!is_user_logged_in()) {
        return json_encode(['error' => 'Usuario no autenticado']);
    }

    $coleccionId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $userId = get_current_user_id();
    $coleccion = get_post($coleccionId);

    if ($coleccion && $coleccion->post_author == $userId) {
        // Obtener todos los samples de la colección antes de eliminarla
        $samples = get_post_meta($coleccionId, 'samples', true);
        if (!is_array($samples)) {
            $samples = [];
        }

        // Eliminar la colección de los metadatos del usuario para cada sample
        $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
        foreach ($samples as $sample_id) {
            if (isset($samplesGuardados[$sample_id])) {
                // Buscar y eliminar la colección específica del sample
                $index = array_search($coleccionId, $samplesGuardados[$sample_id]);
                if ($index !== false) {
                    unset($samplesGuardados[$sample_id][$index]);
                    $samplesGuardados[$sample_id] = array_values($samplesGuardados[$sample_id]); // Reindexar el array

                    // Si no quedan colecciones para el sample, eliminar la entrada del sample en los metadatos
                    if (empty($samplesGuardados[$sample_id])) {
                        unset($samplesGuardados[$sample_id]);
                    }
                }
            }
        }

        // Actualizar los metadatos del usuario
        update_user_meta($userId, 'samplesGuardados', $samplesGuardados);

        // Eliminar la colección
        wp_delete_post($coleccionId, true);

        return json_encode(['success' => true]);
    } else {
        return json_encode(['error' => 'No tienes permisos para eliminar esta colección']);
    }
}

add_action('wp_ajax_crearColeccion', 'crearColeccion');
add_action('wp_ajax_editarColeccion', 'editarColeccion');
add_action('wp_ajax_borrarColec', 'borrarColec');
add_action('wp_ajax_guardarSampleEnColec', 'guardarSampleEnColec');

