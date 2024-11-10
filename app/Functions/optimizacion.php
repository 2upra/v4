 <?

function desactivar_todos_soportes_bloques( $settings, $name ) {
    // Lista completa de soportes a desactivar
    $soportes_a_desactivar = array(
        'align',
        'alignWide',
        'anchor',
        'color',
        'customClassName',
        'html',
        'typography',
        'spacing',
        'border',
        'gradients',
        'responsive',
        'fontSize',
        'links',
        'inserter',
        'multiple',
        'reusable',
        'lock',
    );

    if ( isset( $settings['supports'] ) && is_array( $settings['supports'] ) ) {
        foreach ( $soportes_a_desactivar as $soporte ) {
            if ( isset( $settings['supports'][ $soporte ] ) ) {
                unset( $settings['supports'][ $soporte ] );
            }
        }
    }

    return $settings;
}
add_filter( 'block_type_metadata_settings', 'desactivar_todos_soportes_bloques', 10, 2 );

function desactivar_emojis() {
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'desactivar_emojis' );

function eliminar_scripts_y_estilos() {
    // Ejemplo: Eliminar el script de emoji (ya desactivado anteriormente)
    wp_dequeue_script( 'wp-emoji' );
    wp_dequeue_style( 'wp-emoji' );

    // Eliminar Gutenberg Block Library CSS
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' ); // WooCommerce

    // Eliminar Dashicons en el frontend si no se usan
    if ( ! is_admin() ) {
        wp_dequeue_style( 'dashicons' );
    }

    // Eliminar estilos de Gutenberg en el frontend
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' );

    // Eliminar estilos de la admin bar si no se usa
    if ( ! is_admin() ) {
        wp_dequeue_style( 'admin-bar' );
    }
}
add_action( 'wp_enqueue_scripts', 'eliminar_scripts_y_estilos', 100 );
add_action( 'admin_enqueue_scripts', 'eliminar_scripts_y_estilos', 100 );
add_filter( 'use_block_editor_for_post', '__return_false', 10 );

function desactivar_embeds() {
    // Deshabilitar scripts y estilos relacionados con embeds
    wp_dequeue_script( 'wp-embed' );
    
    // Eliminar acciones relacionadas con embeds
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    
    // Desactivar shortcodes oembed
    add_filter( 'embed_oembed_discover', '__return_false' );
    remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
}
add_action( 'init', 'desactivar_embeds', 9999 );

function eliminar_version_wp() {
    return '';
}
add_filter( 'the_generator', 'eliminar_version_wp' );

function desactivar_feeds() {
    wp_die( __('Las feeds RSS están deshabilitadas.') );
}

// Elimina las feeds principales
add_action('do_feed', 'desactivar_feeds', 1);
add_action('do_feed_rdf', 'desactivar_feeds', 1);
add_action('do_feed_rss', 'desactivar_feeds', 1);
add_action('do_feed_rss2', 'desactivar_feeds', 1);
add_action('do_feed_atom', 'desactivar_feeds', 1);

// Elimina la generación de enlaces en el encabezado
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);

// Limitar el número de revisiones
//define('WP_POST_REVISIONS', 1);

// Deshabilitar autosaves
//define('AUTOSAVE_INTERVAL', 300 ); // Intervalo en segundos (5 minutos)

// O para deshabilitar completamente:
function desactivar_autosave() {
    wp_deregister_script( 'autosave' );
}
add_action( 'wp_print_scripts', 'desactivar_autosave' );
/*
function eliminar_comentarios_support() {
    // Quitar soporte de comentarios de tipos de post
    foreach ( get_post_types() as $post_type ) {
        if ( post_type_supports( $post_type, 'comments' ) ) {
            remove_post_type_support( $post_type, 'comments' );
            remove_post_type_support( $post_type, 'trackbacks' );
        }
    }

    // Cerrar comentarios en tiempo real
    add_filter( 'comments_open', '__return_false', 20, 2 );
    add_filter( 'pings_open', '__return_false', 20, 2 );

    // Eliminar menú de comentarios
    remove_menu_page( 'edit-comments.php' );

    // Eliminar widgets de comentarios
    add_action( 'widgets_init', function(){
        unregister_widget( 'WP_Widget_Recent_Comments' );
    });

    // Eliminar enlaces de comentarios en el pie de página
    add_filter( 'wp_footer', function(){
        // Implementa según tu tema
    });
}
add_action( 'init', 'eliminar_comentarios_support' );
*/
function eliminar_widgets_innecesarios() {
    unregister_widget( 'WP_Widget_Calendar' );
    unregister_widget( 'WP_Widget_Meta' );
    unregister_widget( 'WP_Widget_Search' );
    // Añade otros widgets según necesites
}
add_action( 'widgets_init', 'eliminar_widgets_innecesarios', 11 );

function limpiar_footer_wordpress() {
    remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
    remove_action( 'wp_footer', 'wp_footer' );
}
add_action( 'init', 'limpiar_footer_wordpress' );

// Desactivar ajustes de discusión en la administración
function eliminar_ajustes_discusion() {
    remove_menu_page( 'options-discussion.php' );
}
add_action( 'admin_menu', 'eliminar_ajustes_discusion' );

*/