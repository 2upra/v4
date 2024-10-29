<?

// Función para controlar la indexación de social_post
/*
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
    */
    /*
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
*/


// Título SEO Optimizado
function get_seo_title()
{
    $title = '';

    if (is_home() || is_front_page()) {
        $title = get_bloginfo('name') . ' | ' . get_bloginfo('description');
    } elseif (is_single()) {
        $title = get_the_title() . ' | ' . get_bloginfo('name');
    } elseif (is_page()) {
        $title = get_the_title() . ' | ' . get_bloginfo('name');
    } elseif (is_category()) {
        $title = single_cat_title('', false) . ' | ' . get_bloginfo('name');
    } elseif (is_archive()) {
        $title = get_the_archive_title() . ' | ' . get_bloginfo('name');
    } elseif (is_search()) {
        $title = 'Resultados para: ' . get_search_query() . ' | ' . get_bloginfo('name');
    } elseif (is_404()) {
        $title = 'Página no encontrada | ' . get_bloginfo('name');
    }

    return esc_html($title);
}


function optimizar_titulos_seo($title_parts) {
    if (is_single()) {
        // Personalizar longitud del título
        $title = get_the_title();
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
        
        // Estructura: Título del Post | Categoría | Nombre del Sitio
        $category = get_the_category();
        if ($category) {
            $title_parts['title'] = $title;
            $title_parts['page'] = $category[0]->name;
            $title_parts['tagline'] = get_bloginfo('name');
        }
    }
    
    return $title_parts;
}
add_filter('document_title_parts', 'optimizar_titulos_seo');

// Modificar el separador de títulos
function modificar_separador_titulo($sep) {
    return '|';
}
add_filter('document_title_separator', 'modificar_separador_titulo');

function optimizar_longitud_titulo($title) {
    // Limitar longitud a 60 caracteres
    if (strlen($title) > 60) {
        $title = substr($title, 0, 57) . '...';
    }
    return $title;
}
add_filter('the_title', 'optimizar_longitud_titulo');

// Estructura jerárquica de títulos
function asegurar_jerarquia_titulos($content) {
    // Asegurarse de que solo hay un H1
    $content = preg_replace('/<h1>(.*?)<\/h1>/i', '<h2>$1</h2>', $content);
    return $content;
}
add_filter('the_content', 'asegurar_jerarquia_titulos');

function optimizar_imagenes($content) {
    // Añadir atributos alt y title a imágenes
    $content = preg_replace('/<img(.*?)alt=[\'"](.*?)[\'"](.*?)>/i', '<img$1alt="'.get_the_title().' - $2"$3>', $content);
    return $content;
}
add_filter('the_content', 'optimizar_imagenes');

function auto_enlaces_internos($content) {
    $posts = get_posts(array(
        'numberposts' => 5,
        'orderby' => 'rand'
    ));
    
    foreach($posts as $post) {
        $content = str_replace($post->post_title, 
            '<a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a>', 
            $content);
    }
    return $content;
}
add_filter('the_content', 'auto_enlaces_internos');

function mostrar_breadcrumbs() {
    if (!is_front_page()) {
        echo '<div class="breadcrumbs">';
        echo '<a href="'.home_url().'">Inicio</a> » ';
        if (is_single()) {
            the_category(' » ');
            echo ' » ';
            the_title();
        } elseif (is_category()) {
            single_cat_title();
        }
        echo '</div>';
    }
}

function optimizar_recursos() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-embed');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
}
add_action('wp_enqueue_scripts', 'optimizar_recursos');

function crear_sitemap() {
    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => 'post'
    ));
    
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    foreach($posts as $post) {
        $sitemap .= '<url>';
        $sitemap .= '<loc>'.get_permalink($post->ID).'</loc>';
        $sitemap .= '<lastmod>'.get_the_modified_date('c', $post->ID).'</lastmod>';
        $sitemap .= '</url>';
    }
    
    $sitemap .= '</urlset>';
    
    file_put_contents(ABSPATH . 'sitemap.xml', $sitemap);
}
add_action('save_post', 'crear_sitemap');

function optimizar_headers($headers) {
    $headers['X-Content-Type-Options'] = 'nosniff';
    $headers['X-Frame-Options'] = 'SAMEORIGIN';
    $headers['X-XSS-Protection'] = '1; mode=block';
    return $headers;
}
add_filter('wp_headers', 'optimizar_headers');

// Añadir soporte para títulos SEO
add_theme_support('title-tag');

// Añadir soporte para miniaturas destacadas
add_theme_support('post-thumbnails');

// Añade esto en functions.php para cargar los scripts de manera optimizada
function optimize_scripts() {
    // Desregistrar jQuery y volver a registrarlo en el footer
    wp_deregister_script('jquery');
    wp_register_script('jquery', includes_url('/js/jquery/jquery.js'), false, null, true);
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'optimize_scripts');