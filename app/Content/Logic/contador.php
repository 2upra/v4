<?

function contarPostsFiltrados() {
    // Verificar si el usuario tiene permisos para realizar la acción
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Acceso no autorizado.']);
        return;
    }

    $current_user_id = get_current_user_id();
    
    // Obtener el post type desde la petición Ajax
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'social_post';
    
    $query_args = [
        'post_type'      => $post_type, // Usar el post type recibido
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ];

    // Obtener parámetros enviados por AJAX
    $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];

    // Aplicar filtros específicos del usuario
    $query_args = aplicarFiltrosUsuario($query_args, $current_user_id);

    // Si hay una búsqueda activa, modificar los argumentos de la query
    if (!empty($search_query)) {
        $query_args = prefiltrarIdentifier($search_query, $query_args);
    }

    // Ejecutar la consulta para contar los posts
    $query = new WP_Query($query_args);
    $total_posts = $query->found_posts;

    // Enviar la respuesta en formato JSON
    wp_send_json_success(['total' => $total_posts]);
}

// Registrar las acciones AJAX
add_action('wp_ajax_contarPostsFiltrados', 'contarPostsFiltrados');
add_action('wp_ajax_nopriv_contarPostsFiltrados', 'contarPostsFiltrados');

