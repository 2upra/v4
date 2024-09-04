<?php









//REDIRIGIR TAGS 
function redirect_tag_urls_to_custom_search() {
    if (strpos($_SERVER['REQUEST_URI'], '/tag/') !== false) {
        $url_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        $tag_index = array_search('tag', $url_parts);
        if ($tag_index !== false && isset($url_parts[$tag_index + 1])) {
            $tag_text = $url_parts[$tag_index + 1];
            $search_url = 'https://2upra.com/samples/?search=' . urlencode($tag_text);
            wp_redirect($search_url);
            exit;
        }
    }
}
add_action('template_redirect', 'redirect_tag_urls_to_custom_search');


add_filter( 'wp_mail', 'disable_wp_mail' );
function disable_wp_mail( $args ) {
    return []; 
}

//DESACTIVAR BARRA ADMIN 
function desactivar_barra_admin_para_admin() {
  if (current_user_can('administrator')) {
    add_filter('show_admin_bar', '__return_false');
}
}
add_action('after_setup_theme', 'desactivar_barra_admin_para_admin');


/*
add_filter('wp_handle_upload_prefilter', 'check_duplicate_files_before_upload');
function check_duplicate_files_before_upload($file) {
    $filename = $file['name'];
    $wp_upload_dir = wp_upload_dir();
    $filepath = $wp_upload_dir['path'] . '/' . $filename;

    if (file_exists($filepath)) {
        $file['error'] = "Un archivo con el mismo nombre ya existe en la biblioteca de medios.";
    }

    return $file;
}
*/
//BORRA EL ARCHIVO CUANDO SE BORRA EL POST
function eliminar_adjuntos_cuando_post_se_borre( $post_id ) {
    $adjuntos = get_attached_media( '', $post_id );

    foreach ($adjuntos as $adjunto) {
        wp_delete_attachment( $adjunto->ID, true );
    }
}
add_action( 'before_delete_post', 'eliminar_adjuntos_cuando_post_se_borre' );





function listar_suscripciones_y_suscriptores_usuario() {
    $lista_suscripciones = '';

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $subscription_ids = get_user_meta($user_id, 'subscription_ids', true);
        $offering_user_ids = get_user_meta($user_id, 'offering_user_ids', true);
        $fechas_suscripcion = get_user_meta($user_id, 'fechas_suscripcion', true);

        $lista_suscripciones .= '<div class="contenedor-suscripciones">';
        if (!empty($subscription_ids)) {
            $lista_suscripciones .= '<div class="lista-suscripciones activas">';
            foreach ($subscription_ids as $index => $id_suscripcion) {
                $offering_user_id = $offering_user_ids[$index];
                $user_info = get_userdata($offering_user_id);
                $fecha_suscripcion = $fechas_suscripcion[$index];
                $url_perfil = 'https://2upra.com/' . $user_info->user_login;
                $imagen_perfil = obtener_url_imagen_perfil_o_defecto($offering_user_id);

                $lista_suscripciones .= '<div class="item-suscripcion activas">';
                $lista_suscripciones .= '<a href="' . esc_url($url_perfil) . '"><img src="' . esc_url($imagen_perfil) . '" alt="Perfil de ' . esc_attr($user_info->display_name) . '" class="imagen-perfil-sus"/></a>';
                $lista_suscripciones .= '<div class="info-suscripcion"><span class="nombre-suscripcion">' . esc_html($user_info->display_name) . '</span> (ID de Suscripción: ' . esc_html($id_suscripcion) . ') - ' . esc_html($fecha_suscripcion) . '</div>';
                $lista_suscripciones .= '</div>';
            }
            $lista_suscripciones .= '</div>';
        } else {
            $lista_suscripciones .= '<p class="sin-suscripciones">No tienes suscripciones activas.</p>';
        }
        $lista_suscripciones .= '</div>';

        return $lista_suscripciones;
    } else {
        return 'Necesitas estar logueado para ver tus suscripciones.';
    }
}
add_shortcode('listar_suscripciones_y_suscriptores', 'listar_suscripciones_y_suscriptores_usuario');


function listar_usuarios_suscritos_a_ti() {
    $lista_suscritos = '';

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $usuarios_suscritos = [];

        $all_users_ids = get_users(['fields' => 'ID']);

        $lista_suscritos .= '<div class="contenedor-suscripciones a-ti">';
        if (!empty($all_users_ids)) {
            $lista_suscritos .= '<div class="lista-suscripciones">';
            foreach ($all_users_ids as $user_id_single) {
                $offering_user_ids = get_user_meta($user_id_single, 'offering_user_ids', true);
                if (is_array($offering_user_ids) && in_array($user_id, $offering_user_ids)) {
                    $user_info = get_userdata($user_id_single);
                    $url_perfil = 'https://2upra.com/' . $user_info->user_login;
                    $imagen_perfil = obtener_url_imagen_perfil_o_defecto($user_id_single);

                    $lista_suscritos .= '<div class="item-suscripcion a-ti">';
                    $lista_suscritos .= '<a href="' . esc_url($url_perfil) . '"><img src="' . esc_url($imagen_perfil) . '" alt="Perfil de ' . esc_attr($user_info->display_name) . '" class="imagen-perfil-sus a-ti"/></a>';
                    $lista_suscritos .= '<div class="info-suscripcion"><span class="nombre-suscripcion">' . esc_html($user_info->display_name) . '</span></div>';
                    $lista_suscritos .= '</div>';
                }
            }
            $lista_suscritos .= '</div>';
        } else {
            $lista_suscritos .= '<p class="sin-suscritos">Ningún usuario está suscrito a ti.</p>';
        }
        $lista_suscritos .= '</div>';
    } else {
        $lista_suscritos = 'Necesitas estar logueado para ver tus suscriptores.';
    }

    return $lista_suscritos;
}
add_shortcode('listar_usuarios_suscritos', 'listar_usuarios_suscritos_a_ti');


function listar_historial_suscripciones_cancelaciones() {
    if (!is_user_logged_in()) {
        return 'Necesitas estar logueado para ver este historial.';
    }

    $lista_historial = '<div class="contenedor-suscripciones">';

    $users = get_users();
    foreach ($users as $user) {
        $subscriber_user_info = get_userdata($user->ID); // Obtener información del usuario suscriptor
        $subscription_ids = get_user_meta($user->ID, 'subscription_ids', true);
        $offering_user_ids = get_user_meta($user->ID, 'offering_user_ids', true);
        $fechas_suscripcion = get_user_meta($user->ID, 'fechas_suscripcion', true);

        if (!empty($subscription_ids) && is_array($subscription_ids)) {
            $lista_historial .= '<div class="lista-suscripciones historial">';
            foreach ($subscription_ids as $index => $id_suscripcion) {
                $offering_user_id = $offering_user_ids[$index];
                $offering_user_info = get_userdata($offering_user_id); // Obtener información del usuario al que se suscribe
                $fecha_suscripcion = $fechas_suscripcion[$index];

                // URLs e imágenes de perfil
                $url_perfil_subscriber = 'https://2upra.com/' . $subscriber_user_info->user_login;
                $imagen_perfil_subscriber = obtener_url_imagen_perfil_o_defecto($user->ID);
                $url_perfil_offering = 'https://2upra.com/' . $offering_user_info->user_login;
                $imagen_perfil_offering = obtener_url_imagen_perfil_o_defecto($offering_user_id);

                $lista_historial .= '<div class="item-suscripcion historial">';
                // Imagen del usuario suscriptor
                $lista_historial .= '<a href="' . esc_url($url_perfil_subscriber) . '"><img src="' . esc_url($imagen_perfil_subscriber) . '" alt="Perfil de ' . esc_attr($subscriber_user_info->display_name) . '" class="imagen-perfil-sus"/></a>';
                // Información de la suscripción
                $lista_historial .= '<div class="info-suscripcion">' . esc_html($subscriber_user_info->display_name) . ' suscrito a ' . esc_html($offering_user_info->display_name) . ' - ' . esc_html($fecha_suscripcion) . '</div>';
                // Imagen del usuario ofrecido al final
                $lista_historial .= '<a href="' . esc_url($url_perfil_offering) . '"><img src="' . esc_url($imagen_perfil_offering) . '" alt="Perfil de ' . esc_attr($offering_user_info->display_name) . '" class="imagen-perfil-sus"/></a>';
                $lista_historial .= '</div>';
            }
            $lista_historial .= '</div>';
        }
    }

    $lista_historial .= '</div>';

    return $lista_historial;
}
add_shortcode('listar_historial_suscripciones_cancelaciones', 'listar_historial_suscripciones_cancelaciones');

//EVITAR CONSULTA REPETITIVA DE ESA COSA
function get_attachment_id_by_url($url) {
    global $wpdb;
    $cache_key = 'attachment_id_' . md5($url);
    $post_id = wp_cache_get($cache_key, 'my_custom_cache_group');

    if ($post_id === false) {
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
            $url
        ));
        wp_cache_set($cache_key, $post_id, 'my_custom_cache_group', 3600); // Cache for 1 hour
    }
    return $post_id;
}

function ocultar_elementos_barra_admin_con_css() {
    echo '
    <style type="text/css">
        #wp-admin-bar-wp-logo,
        #wp-admin-bar-customize,
        #wp-admin-bar-updates,
        #wp-admin-bar-comments,
        #wp-admin-bar-new-content,
        #wp-admin-bar-wpseo-menu,
        #wp-admin-bar-edit { display: none !important; }
    </style>
    ';
}
add_action('admin_head', 'ocultar_elementos_barra_admin_con_css');
add_action('wp_head', 'ocultar_elementos_barra_admin_con_css');

function personalizar_estilos_wp_admin_bar_admin() {
    echo '<style type="text/css">
        #wpadminbar {
    direction: ltr;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 200 !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
    line-height: 2.46153846 !important;
    height: 32px !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    min-width: 600px !important;
    z-index: 99999 !important;
    background: #000000 !important;
}
</style>';
}
add_action('admin_head', 'personalizar_estilos_wp_admin_bar_admin');

function personalizar_estilos_wp_admin_bar_front() {
    echo '<style type="text/css">
        #wpadminbar {
    direction: ltr;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 200 !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
    line-height: 2.46153846 !important;
    height: 32px !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    min-width: 600px !important;
    z-index: 99999 !important;
    background: #000000 !important;
}
</style>';
}
add_action('wp_head', 'personalizar_estilos_wp_admin_bar_front');


function registrar_tipo_publicacion_colab() {
    $labels = array(
        'name'                  => _x('Colaboraciones', 'Post type general name', 'textdomain'),
        'singular_name'         => _x('Colaboración', 'Post type singular name', 'textdomain'),
        'menu_name'             => _x('Colaboraciones', 'Admin Menu text', 'textdomain'),
        'name_admin_bar'        => _x('Colaboración', 'Add New on Toolbar', 'textdomain'),
        'add_new'               => __('Añadir nueva', 'textdomain'),
        'add_new_item'          => __('Añadir nueva colaboración', 'textdomain'),
        'new_item'              => __('Nueva colaboración', 'textdomain'),
        'edit_item'             => __('Editar colaboración', 'textdomain'),
        'view_item'             => __('Ver colaboración', 'textdomain'),
        'all_items'             => __('Todas las colaboraciones', 'textdomain'),
        'search_items'          => __('Buscar colaboraciones', 'textdomain'),
        'parent_item_colon'     => __('Colaboración padre:', 'textdomain'),
        'not_found'             => __('No se encontraron colaboraciones.', 'textdomain'),
        'not_found_in_trash'    => __('No se encontraron colaboraciones en la papelera.', 'textdomain'),
        'featured_image'        => _x('Imagen destacada de la colaboración', 'Overrides the “Featured Image” phrase', 'textdomain'),
        'set_featured_image'    => _x('Establecer imagen destacada', 'Overrides the “Set featured image” phrase', 'textdomain'),
        'remove_featured_image' => _x('Quitar imagen destacada', 'Overrides the “Remove featured image” phrase', 'textdomain'),
        'use_featured_image'    => _x('Usar como imagen destacada', 'Overrides the “Use as featured image” phrase', 'textdomain'),
        'archives'              => _x('Archivo de colaboraciones', 'The post type archive label used in nav menus', 'textdomain'),
        'insert_into_item'      => _x('Insertar en colaboración', 'Overrides the “Insert into post”/“Insert into page” phrase', 'textdomain'),
        'uploaded_to_this_item' => _x('Subido a esta colaboración', 'Overrides the “Uploaded to this post”/“Uploaded to this page” phrase', 'textdomain'),
        'filter_items_list'     => _x('Filtrar lista de colaboraciones', 'Overrides the “Filter posts list”/“Filter pages list” phrase', 'textdomain'),
        'items_list_navigation' => _x('Navegación de lista de colaboraciones', 'Overrides the “Posts list navigation”/“Pages list navigation” phrase', 'textdomain'),
        'items_list'            => _x('Lista de colaboraciones', 'Overrides the “Posts list”/“Pages list” phrase', 'textdomain'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'colab'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'custom-fields','thumbnail', 'excerpt', 'comments'),
        'show_in_rest'       => true,  // Habilita el soporte del editor de bloques Gutenberg.
    );

    register_post_type('colab', $args);
}
add_action('init', 'registrar_tipo_publicacion_colab');

function registrar_campos_personalizados_colab() {
    register_post_meta('colab', 'para_colab', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
    ]);
}
add_action('init', 'registrar_campos_personalizados_colab');

//
function registrar_cpt_ventas() {
    $args = array(
        'public' => true,
        'label'  => 'Ventas',
        'supports' => array('title', 'editor', 'comments', 'thumbnail', 'custom-fields'),
        
    );
    register_post_type('ventas', $args);
}
add_action('init', 'registrar_cpt_ventas');


//PARA DEPURACION DE USUARIO EN EL FRONT 
function mostrar_metadatos_usuario_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'user_id' => get_current_user_id(), 
    ), $atts, 'mostrar_metadatos_usuario' );
    $user_meta = get_user_meta( $atts['user_id'] );
    ob_start();

    if ( !empty($user_meta) ) {
        echo '<ul>';
        foreach ( $user_meta as $key => $values ) { 
            echo "<li><strong>$key:</strong> ";
            foreach ( $values as $value ) { 
                echo "$value ";
            }
            echo "</li>";
        }
        echo '</ul>';
    } else {
        echo 'No hay metadatos disponibles para este usuario.';
    }
    return ob_get_clean();
}
add_shortcode( 'mostrar_metadatos_usuario', 'mostrar_metadatos_usuario_shortcode' );














//REDIGIR COMENTARIO
/*
function redirect_after_comment($location) {
    return $_SERVER["HTTP_REFERER"];
}
add_filter('comment_post_redirect', 'redirect_after_comment');
*/
function aplicar_css_tipo_post_especifico() {
    if (is_singular('social_post')) { 
        echo '<style type="text/css">
        .form-submit {
            margin-top: 0px;
        }
        .post-date {
            margin-top: 0px;
            font-size: 12px;
        }
        .nombre-usuario {
            margin-top: 5px;
        }
            #main, .layout-boxed-mode #main, .layout-boxed-mode.avada-footer-fx-sticky .above-footer-wrapper, .layout-boxed-mode.avada-footer-fx-sticky-with-parallax-bg-image .above-footer-wrapper, .layout-wide-mode #main, .layout-wide-mode #wrapper, body, html, html body.custom-background {
        background-color: #000000 !important;
    }
    .social-post {
        margin-top: 125px !important;
    }

    p{
        margin: 0px !important;
    }

    .comments-container {
        background-color: #080808;
        color: #fff;
        margin: 0px auto 0px;
        padding: 20px 30px 5px 20px;
        width: 600px;
        border-radius: 0;
    }

    .comment-respond {
        width: 600px;
        display: flex;
    }

    </style>';
}
}
add_action('wp_head', 'aplicar_css_tipo_post_especifico');

function aplicar_css_ventas() {
    if (is_singular('ventas')) { 
        echo '<style type="text/css">

            #main, .layout-boxed-mode #main, .layout-boxed-mode.avada-footer-fx-sticky .above-footer-wrapper, .layout-boxed-mode.avada-footer-fx-sticky-with-parallax-bg-image .above-footer-wrapper, .layout-wide-mode #main, .layout-wide-mode #wrapper, body, html, html body.custom-background {
        background-color: #000000 !important;
    }
    .venta-item {
        margin-top: 150px !important;
    }

    p{
        margin: 0px !important;
    }

    .name-buyer, .name-seller {
        line-height: 17px !important;
        padding-top: 2px !important;
        padding-left: 10px !important;
        font-size: 12px !important;
        color: #b3b3b3 !important;
    }


    </style>';
}
}
add_action('wp_head', 'aplicar_css_ventas');

function otorgar_capacidad_modificar_notificaciones() {
    global $wp_roles;
    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }
    $todos_los_roles = $wp_roles->get_names(); 
    foreach ($todos_los_roles as $rol_nombre => $rol_display_name) {
        $rol = get_role($rol_nombre);
        $rol->add_cap('modificar_notificaciones');
    }
}
otorgar_capacidad_modificar_notificaciones();

function otorgar_capacidades_a_usuarios() {
    $role = get_role('artista');
    $role->add_cap('edit_posts');
    $role->add_cap('publish_posts');
    /*$role->add_cap('edit_pages');
    $role->add_cap('publish_pages');*/
    $role->add_cap('edit_comments');
}
add_action('init', 'otorgar_capacidades_a_usuarios');


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
