<?
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
// Limitar el número de revisiones
//define('WP_POST_REVISIONS', 1);
// Deshabilitar autosaves
//define('AUTOSAVE_INTERVAL', 300 ); // Intervalo en segundos (5 minutos)
// O para deshabilitar completamente:

