<?

function corregir_post_meta_postAut() {
    // Argumentos para la consulta de los posts de tipo 'social_post'
    $args = array(
        'post_type'      => 'social_post',  // Tipo de post a buscar
        'post_status'    => 'any',          // Puedes cambiar esto según tus necesidades (publicado, borrador, etc.)
        'posts_per_page' => -1,             // Todos los posts de tipo 'social_post'
        'fields'         => 'ids'           // Solo obtener los IDs de los posts
    );

    // Conseguir todos los posts de tipo 'social_post'
    $posts = get_posts($args);

    // Recorremos los posts
    foreach ($posts as $post_id) {
        // Obtener el valor actual de 'postAut'
        $post_aut_value = get_post_meta($post_id, 'postAut', true);

        // Si el valor es '1', lo actualizamos a true
        if ($post_aut_value === '1') {
            update_post_meta($post_id, 'postAut', true);
            echo "Meta actualizado para post ID: $post_id <br>";
        }
    }

    // Esto es opcional, es solo para asegurarse de que la función no se ejecute más de una vez.
    // Podrías eliminar esta función después de ejecutarla.
    remove_action('init', 'corregir_post_meta_postAut');
}

// Esta función se ejecutará en el hook 'init'
add_action('init', 'corregir_post_meta_postAut');

// Función genérica para manejar las solicitudes AJAX
function permitirDescarga($post_id)
{
    update_post_meta($post_id, 'paraDescarga', true);
    return json_encode(['success' => true, 'message' => 'Descarga permitida']);
}

function cambiarEstado($post_id, $new_status)
{
    $post = get_post($post_id);
    $post->post_status = $new_status;
    wp_update_post($post);
    return json_encode(['success' => true, 'new_status' => $new_status]);
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

// Register AJAX actions
add_action('wp_ajax_permitirDescarga', 'cambioDeEstado');
add_action('wp_ajax_aceptarcolab', 'cambioDeEstado');
add_action('wp_ajax_rechazarcolab', 'cambioDeEstado');
add_action('wp_ajax_toggle_post_status', 'cambioDeEstado');
add_action('wp_ajax_reject_post', 'cambioDeEstado');
add_action('wp_ajax_request_post_deletion', 'cambioDeEstado');
add_action('wp_ajax_eliminarPostRs', 'cambioDeEstado');

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
