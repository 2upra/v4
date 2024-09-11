<?php

























/*
// Función para manejar el "like" de una publicación
function handle_post_like() {
    if (!is_user_logged_in()) {
        echo 'not_logged_in';
        wp_die();
    }

    $user_id = get_current_user_id();
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : '';

    if (!check_ajax_referer('ajax-nonce', 'nonce', false) || empty($post_id)) {
        echo 'error';
        wp_die();
    }

    $likes = get_post_meta($post_id, '_post_likes', true) ?: array();
    $already_liked = in_array($user_id, $likes);

    if (!$already_liked) {
        $likes[] = $user_id;
        update_likes_meta($post_id, $likes);

        $post_author_id = get_post_field('post_author', $post_id);
        if ($post_author_id != $user_id) { 
            $liker_name = get_userdata($user_id)->display_name;
            $post_title = get_the_title($post_id);
            $post_url = get_permalink($post_id);
            $texto_notificacion = sprintf('%s le gustó tu publicación.', $liker_name, $post_url, $post_title);
            insertar_notificacion($post_author_id, $texto_notificacion, $post_url, $user_id);
        }
    } else {
        $likes = array_diff($likes, array($user_id));
        update_likes_meta($post_id, $likes);
    }

    echo count($likes) . ' Likes';
    wp_die();
}


function update_likes_meta($post_id, $likes) {
    update_post_meta($post_id, '_post_likes', $likes);
    update_post_meta($post_id, '_post_like_count', count($likes));
}

add_action('wp_ajax_nopriv_handle_post_like', 'handle_post_like');
add_action('wp_ajax_handle_post_like', 'handle_post_like');

// Función para mostrar el botón de "like" y el conteo
function show_like_button($post_id) {
    $user_id = get_current_user_id();
    $likes = get_post_meta($post_id, '_post_likes', true) ?: array();
    $like_count = is_array($likes) ? count($likes) : 0;
    $user_has_liked = in_array($user_id, $likes);
    $liked_class = $user_has_liked ? 'liked' : ''; 

    echo '<button class="post-like-button ' . esc_attr($liked_class) . '" data-post_id="' . esc_attr($post_id) . '"><i class="fa-heart fas"></i></button> ';
    echo '<span class="like-count">' . esc_html($like_count) . ' Likes</span>';
}

*/
//BOTON DE COMENTARIO CON ICONO 
add_filter('comment_form_submit_button', 'custom_comment_form_submit_button', 10, 2);
function custom_comment_form_submit_button($submit_button, $args) {
    return '<button type="submit" id="' . esc_attr($args['id_submit']) . '" class="' . esc_attr($args['class_submit']) . '"><i class="fa fa-paper-plane"></i> ' . esc_html($args['label_submit']) . '</button>';
}


//BOTON DE BORRAR POST 
add_action('wp_ajax_delete_post_by_user', 'delete_post_by_user');

function delete_post_by_user() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_post_nonce')) {
        wp_send_json_error('Nonce no válido.');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id && current_user_can('delete_post', $post_id)) {
        $deleted = wp_delete_post($post_id, true);

        if ($deleted) {
            wp_send_json_success('Publicación eliminada.');
        } else {
            wp_send_json_error('No se pudo eliminar la publicación.');
        }
    } else {
        wp_send_json_error('No tienes permisos para eliminar esta publicación.');
    }

    wp_die();
}

//PERMISOS PARA BORAR POST
function agregar_capacidades_personalizadas() {
    $role = get_role('artista');
    $role->add_cap('delete_posts');
    $role->add_cap('delete_published_posts'); 
}

add_action('init', 'agregar_capacidades_personalizadas');

//BOTON DE BORRAR COMENTARIO 
function delete_comment_by_user() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'borrar_comentario_nonce')) {
        error_log('Fallo en la verificación del nonce.');
        wp_send_json_error('Nonce no válido.');
        wp_die();
    } else {
    }

    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $comment = get_comment($comment_id);
    if (!$comment) {
        error_log('Comentario no encontrado.');
        wp_send_json_error('Comentario no encontrado.');
        wp_die();
    }

    if ($comment_id && (get_current_user_id() == $comment->user_id || current_user_can('moderate_comments'))) {
        $deleted = wp_delete_comment($comment_id, true);

        if ($deleted) {
            error_log('Comentario eliminado.');
            wp_send_json_success('Comentario eliminado.');
        } else {
            error_log('Fallo al eliminar el comentario.');
            wp_send_json_error('No se pudo eliminar el comentario.');
        }
    } else {
        error_log('Fallo de permisos para eliminar el comentario.');
        wp_send_json_error('No tienes permisos para eliminar este comentario.');
    }

    wp_die();
}
add_action('wp_ajax_delete_comment_by_user', 'delete_comment_by_user');

//ICONOS



add_action( 'init', 'create_sample_taxonomies', 0 );
function create_sample_taxonomies() {
    $labels_genre = array(
        'name'              => _x( 'Géneros', 'taxonomy general name', 'textdomain' ),
        'singular_name'     => _x( 'Género', 'taxonomy singular name', 'textdomain' ),
        'search_items'      => __( 'Buscar Géneros', 'textdomain' ),
        'all_items'         => __( 'Todos los Géneros', 'textdomain' ),
        'parent_item'       => __( 'Género Padre', 'textdomain' ),
        'parent_item_colon' => __( 'Género Padre:', 'textdomain' ),
        'edit_item'         => __( 'Editar Género', 'textdomain' ),
        'update_item'       => __( 'Actualizar Género', 'textdomain' ),
        'add_new_item'      => __( 'Añadir Nuevo Género', 'textdomain' ),
        'new_item_name'     => __( 'Nombre del Nuevo Género', 'textdomain' ),
        'menu_name'         => __( 'Género', 'textdomain' ),
    );

    // Etiquetas para la taxonomía 'Instrumento'
    $labels_instrument = array(
        'name'              => _x( 'Instrumentos', 'taxonomy general name', 'textdomain' ),
        'singular_name'     => _x( 'Instrumento', 'taxonomy singular name', 'textdomain' ),
        'search_items'      => __( 'Buscar Instrumentos', 'textdomain' ),
        'all_items'         => __( 'Todos los Instrumentos', 'textdomain' ),
        'parent_item'       => __( 'Instrumento Padre', 'textdomain' ),
        'parent_item_colon' => __( 'Instrumento Padre:', 'textdomain' ),
        'edit_item'         => __( 'Editar Instrumento', 'textdomain' ),
        'update_item'       => __( 'Actualizar Instrumento', 'textdomain' ),
        'add_new_item'      => __( 'Añadir Nuevo Instrumento', 'textdomain' ),
        'new_item_name'     => __( 'Nombre del Nuevo Instrumento', 'textdomain' ),
        'menu_name'         => __( 'Instrumento', 'textdomain' ),
    );

    // Argumentos para la taxonomía 'Género'
    $args_genre = array(
        'hierarchical'      => true,
        'labels'            => $labels_genre,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'genero' ),
    );


    // Argumentos para la taxonomía 'Instrumento'
    $args_instrument = array(
        'hierarchical'      => true,
        'labels'            => $labels_instrument,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'instrumento' ),
    );
    register_taxonomy( 'genero', array( 'social_post' ), $args_genre );
    register_taxonomy( 'instrumento', array( 'social_post' ), $args_instrument );
    
}
/*
add_action('admin_enqueue_scripts', 'ocultar_notificaciones_wp_admin');
function ocultar_notificaciones_wp_admin() {
    if (is_admin()) {
        echo '<style>
        .update-nag, .updated, .error, .is-dismissible { display: none !important; }
        </style>';
    }
}
*/
//EVITAR QUE LOS USUARIOS SE DESCONECTEN 
add_filter('auth_cookie_expiration', 'my_expiration_filter', 99, 3);
function my_expiration_filter($seconds, $user_id, $remember){
    $expiration = PHP_INT_MAX;
    return $expiration;
}

//CAMPOS DE USUARIO
function mostrar_campo_usuario($campo) {
  $usuario_actual = wp_get_current_user();
  if ( ! $usuario_actual->$campo ) {
    return __('');
}
return $usuario_actual->$campo;
}
add_shortcode( 'correo_usuario', function() {
  return mostrar_campo_usuario('user_email');
});

add_shortcode( 'current_name', function() {
  return mostrar_campo_usuario('display_name');
});

function handle_save_edited_comment() {
    check_ajax_referer('editar_comentario_nonce', 'nonce');

    if (!current_user_can('edit_comment', $_POST['comment_ID'])) {
        wp_send_json_error('No tienes permiso para editar este comentario.');
        wp_die();
    }
    $comment_id = intval($_POST['comment_ID']);
    $comment_content = wp_kses_post($_POST['comment_content']); 
    $commentarr = array(
        'comment_ID' => $comment_id,
        'comment_content' => $comment_content,
    );

    if(wp_update_comment($commentarr)) {
        wp_send_json_success('Comentario actualizado con éxito.');
    } else {
        wp_send_json_error('Error al actualizar el comentario.');
    }

    wp_die(); 
}

add_action('wp_ajax_save_edited_comment', 'handle_save_edited_comment'); 
