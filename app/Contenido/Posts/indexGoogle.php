<?

// Función para controlar la indexación de social_post
function controlar_indexacion_social_post() {
    // Verificar si estamos en un post type social_post
    if (is_singular('social_post')) {
        // Obtener el ID del post actual
        $post_id = get_the_ID();
        
        // Obtener los valores de las metas
        $post_aut = get_post_meta($post_id, 'postAut', true);
        $verificado = get_post_meta($post_id, 'Verificado', true);
        
        // Si postAut es 1 y Verificado no es 1, añadir noindex
        if ($post_aut == '1' && $verificado != '1') {
            // Añadir meta robots noindex
            add_action('wp_head', function() {
                echo '<meta name="robots" content="noindex,follow" />';
            });
            
            // Modificar el header X-Robots-Tag
            add_filter('wp_headers', function($headers) {
                $headers['X-Robots-Tag'] = 'noindex,follow';
                return $headers;
            });
        }
    }
}
add_action('wp', 'controlar_indexacion_social_post');

// Opcional: Modificar el sitemap para excluir estos posts
function excluir_posts_del_sitemap($args, $post_type) {
    if ($post_type == 'social_post') {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => 'postAut',
                'value' => '1',
                'compare' => '!='
            ),
            array(
                'relation' => 'AND',
                array(
                    'key' => 'postAut',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'Verificado',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
    }
    return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'excluir_posts_del_sitemap', 10, 2);