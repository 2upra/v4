<?



function permitirDescarga($post_id)
{
    update_post_meta($post_id, 'paraDescarga', true);
    return json_encode(['success' => true, 'message' => 'Descarga permitida']);
}

function comprobarColabsUsuario($user_id)
{
    // Query para obtener las colaboraciones publicadas del usuario
    $args = [
        'author'         => $user_id,
        'post_status'    => 'publish',
        'post_type'      => 'colab',
        'posts_per_page' => -1,
    ];

    $query = new WP_Query($args);
    return $query->found_posts;
}

function cambiarEstado($post_id, $new_status)
{
    $post = get_post($post_id);
    $post->post_status = $new_status;
    wp_update_post($post);
    return json_encode(['success' => true, 'new_status' => $new_status]);
}

function cambioDeEstado()
{
    if (!isset($_POST['post_id'])) {
        echo json_encode(['success' => false, 'message' => 'Post ID is missing']);
        wp_die();
    }

    $post_id = $_POST['post_id'];
    $action = $_POST['action'];
    $current_user_id = get_current_user_id();

    // Si la acción es aceptar colaboración, comprobar el número de colabs publicadas
    if ($action === 'aceptarcolab') {
        $colabsPublicadas = comprobarColabsUsuario($current_user_id);

        if ($colabsPublicadas >= 3) {
            echo json_encode(['success' => false, 'message' => 'Ya tienes 3 colaboraciones en curso. Debes finalizar una para aceptar otra.']);
            wp_die();
        }
    }

    $estados = [
        'toggle_post_status'    => ($_POST['current_status'] == 'pending') ? 'publish' : 'pending',
        'reject_post'           => 'rejected',
        'request_post_deletion' => 'pending_deletion',
        'eliminarPostRs'        => 'pending_deletion',
        'rechazarcolab'         => 'pending_deletion',
        'aceptarcolab'          => 'publish',
    ];

    if ($action === 'permitirDescarga') {
        echo permitirDescarga($post_id);
    } elseif (isset($estados[$action])) {
        $new_status = $estados[$action];
        echo cambiarEstado($post_id, $new_status);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

    wp_die();
}

function verificarPost()
{
    if (!isset($_POST['post_id'])) {
        echo json_encode(['success' => false, 'message' => 'Post ID is missing']);
        wp_die();
    }

    $post_id = $_POST['post_id'];
    $current_user = wp_get_current_user();

    // Verificar si el usuario es administrador
    if (!user_can($current_user, 'administrator')) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para verificar este post']);
        wp_die();
    }

    // Actualizar el meta 'Verificado' a true
    update_post_meta($post_id, 'Verificado', true);

    echo json_encode(['success' => true, 'message' => 'Post verificado correctamente']);
    wp_die();
}

//hay una nueva accion verificarPost que lo que tiene que hacer es simplemente agregar la meta de Verificado true en el post (comprobar si el usuario es admin para esta accion)
add_action('wp_ajax_verificarPost', 'verificarPost');
add_action('wp_ajax_permitirDescarga', 'cambioDeEstado');
add_action('wp_ajax_aceptarcolab', 'cambioDeEstado');
add_action('wp_ajax_rechazarcolab', 'cambioDeEstado');
add_action('wp_ajax_toggle_post_status', 'cambioDeEstado');
add_action('wp_ajax_reject_post', 'cambioDeEstado');
add_action('wp_ajax_request_post_deletion', 'cambioDeEstado');
add_action('wp_ajax_eliminarPostRs', 'cambioDeEstado');
add_action('wp_ajax_corregirTags', 'corregirTags');
add_action('wp_ajax_cambiarTitulo', 'cambiarTitulo');

function corregirTags()
{
    // Verificar si el usuario está logeado
    if (!is_user_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'No estás autorizado']);
        wp_die();
    }

    // Obtener información del usuario actual
    $current_user = wp_get_current_user();

    // Sanitizar los datos recibidos de la solicitud
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

    // Verificar si se recibió un ID de post válido
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de post no válido']);
        wp_die();
    }

    // Obtener el post y verificar si existe
    $post = get_post($post_id);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'El post no existe']);
        wp_die();
    }

    // Verificar si el usuario es el autor del post o es administrador
    if ($post->post_author != $current_user->ID && !current_user_can('administrator')) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este post']);
        wp_die();
    }

    rehacerJsonPost($post->ID, $descripcion);
    echo json_encode(['success' => true]);
    wp_die();
}

function cambiarDescripcion()
{
    // Verificar si el usuario está logeado
    if (!is_user_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'No estás autorizado']);
        wp_die();
    }
    // Obtener información del usuario actual
    $current_user = wp_get_current_user();

    // Sanitizar los datos recibidos de la solicitud
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

    // Verificar si se recibió un ID de post válido
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de post no válido']);
        wp_die();
    }

    // Obtener el post y verificar si existe
    $post = get_post($post_id);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'El post no existe']);
        wp_die();
    }

    // Verificar si el usuario es el autor del post o es administrador
    if ($post->post_author != $current_user->ID && !current_user_can('administrator')) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este post']);
        wp_die();
    }

    // Actualizar la descripción del post si todo es correcto
    $post->post_content = wp_kses_post($descripcion); // Sanitizar el contenido del post
    wp_update_post($post);
    rehacerDescripcionAccion($post->ID);
    echo json_encode(['success' => true]);
    wp_die();
}

add_action('wp_ajax_cambiarDescripcion', 'cambiarDescripcion');

function cambiarTitulo()
{
    // Verificar si el usuario está logeado
    if (!is_user_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'No estás autorizado']);
        wp_die();
    }

    // Obtener información del usuario actual
    $current_user = wp_get_current_user();

    // Sanitizar los datos recibidos de la solicitud
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';

    // Verificar si se recibió un ID de post válido
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de post no válido']);
        wp_die();
    }

    // Obtener el post y verificar si existe
    $post = get_post($post_id);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'El post no existe']);
        wp_die();
    }

    // Verificar si el usuario es el autor del post o es administrador
    if ($post->post_author != $current_user->ID && !current_user_can('administrator')) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este post']);
        wp_die();
    }

    // Actualizar el título del post si todo es correcto
    $post->post_title = $titulo; // Asignar el nuevo título
    wp_update_post($post);

    echo json_encode(['success' => true]); 
    wp_die();
}

add_action('wp_ajax_cambiar_imagen_post', 'cambiar_imagen_post_handler'); // Acción AJAX autenticada



function cambiar_imagen_post_handler() {
    // Verificar que el post_id y el archivo de imagen están presentes
    if (empty($_POST['post_id']) || empty($_FILES['imagen'])) {
        wp_send_json_error(['message' => 'Faltan datos necesarios.']);
    }

    $post_id = intval($_POST['post_id']);

    // Verificar que el post existe
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(['message' => 'El post no existe.']);
    }

    // Verificar que el usuario actual sea el autor del post
    if ((int) $post->post_author !== get_current_user_id()) {
        wp_send_json_error(['message' => 'No tienes permisos para cambiar la imagen de este post.']);
    }

    // Procesar la imagen subida
    $file = $_FILES['imagen'];

    // Validar y subir la imagen usando la API de WordPress
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $upload = wp_handle_upload($file, ['test_form' => false]);

    if (isset($upload['error']) || !isset($upload['file'])) {
        wp_send_json_error(['message' => 'Error al subir la imagen: ' . $upload['error']]);
    }

    $file_path = $upload['file'];
    $file_url = $upload['url'];

    // Crear un attachment en la biblioteca de medios
    $attachment_id = wp_insert_attachment([
        'guid'           => $file_url,
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name($file['name']),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $file_path, $post_id);

    if (is_wp_error($attachment_id) || !$attachment_id) {
        wp_send_json_error(['message' => 'Error al guardar la imagen en la biblioteca de medios.']);
    }

    // Generar los metadatos de la imagen (tamaños, etc.)
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    // Establecer la imagen destacada del post
    set_post_thumbnail($post_id, $attachment_id);

    // Devolver la URL de la nueva imagen para actualizar el frontend
    wp_send_json_success(['new_image_url' => $file_url]);
}
