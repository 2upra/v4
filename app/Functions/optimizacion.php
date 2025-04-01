<?
add_action('init', function () {
    wp_deregister_script('heartbeat');
});

add_filter('use_block_editor_for_post', '__return_false', 10);

function desactivar_feeds()
{
    wp_die(__('Las feeds RSS están deshabilitadas.'));
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
function desactivar_autosave()
{
    wp_deregister_script('autosave');
}
add_action('wp_print_scripts', 'desactivar_autosave');
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
function eliminar_widgets_innecesarios()
{
    unregister_widget('WP_Widget_Calendar');
    unregister_widget('WP_Widget_Meta');
    unregister_widget('WP_Widget_Search');
    // Añade otros widgets según necesites
}
add_action('widgets_init', 'eliminar_widgets_innecesarios', 11);
function limpiar_footer_wordpress()
{
    remove_action('wp_footer', 'wp_admin_bar_render', 1000);
    remove_action('wp_footer', 'wp_footer');
}
add_action('init', 'limpiar_footer_wordpress');
// Desactivar ajustes de discusión en la administración
function eliminar_ajustes_discusion()
{
    remove_menu_page('options-discussion.php');
}
add_action('admin_menu', 'eliminar_ajustes_discusion');
