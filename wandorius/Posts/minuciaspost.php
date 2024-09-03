<?php 

//STATUS DE LOS POSTS
function register_rejected_post_status() {
    register_post_status('rejected', array(
        'label'                     => _x('Rejected', 'post status'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>')
    ));
}
add_action('init', 'register_rejected_post_status');

add_action('init', 'register_pending_deletion_status');
function register_pending_deletion_status()
{
    register_post_status(
        'pending_deletion',
        array(
            'label' => _x('Pending Deletion', 'post'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Pending Deletion <span class="count">(%s)</span>', 'Pending Deletion <span class="count">(%s)</span>'),
        )
    );
}

//ESTE ES EL TIPO DE POST PARA LAS ROLAS INDIVIDUALES 
function create_social_post_type() {
    register_post_type('social_post', array(
        'labels' => array(
            'name' => __('Samples'),
            'singular_name' => __('Sample')
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'comments', 'custom-fields'), 
        'rewrite' => array('slug' => 'sample'),
    ));
}

add_action('init', 'create_social_post_type');

//Y ESTE SERA PAR LOS ALBUMS
function create_album_post_type() {
    register_post_type('albums', array(
        'labels' => array(
            'name' => __('Albums'),
            'singular_name' => __('Album')
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'comments', 'custom-fields'), 
        'rewrite' => array('slug' => 'album'),
    ));
}

add_action('init', 'create_album_post_type');


function create_story_post_type() {
    register_post_type('stories', array(
        'labels' => array(
            'name' => __('Momentos'),
            'singular_name' => __('Momento')
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => 'Momentos'),
        'menu_icon' => 'dashicons-camera', // Icono para el menú de administración
    ));
}

add_action('init', 'create_story_post_type');




/*
function get_full_post_details($post_id) {
    // Obtén el objeto de post
    $post = get_post($post_id);

    // Si el post existe
    if ($post) {
        // Título, autor, fecha, y contenido del post
        $title = get_the_title($post);
        $content = apply_filters('the_content', $post->post_content);
        $author = get_the_author_meta('display_name', $post->post_author);
        $date = get_the_date('F j, Y', $post);

        // Meta información del post
        $post_meta = get_post_meta($post_id);

        // Taxonomías (categorías, etiquetas, etc.)
        $taxonomies = get_object_taxonomies($post->post_type);
        $terms = [];
        foreach ($taxonomies as $taxonomy) {
            $terms[$taxonomy] = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'all']);
        }

        // Comienza a construir la salida
        $output = "<div class='post-details'>";
        $output .= "<h2>{$title}</h2>";
        $output .= "<p><strong>Autor:</strong> {$author}</p>";
        $output .= "<p><strong>Fecha:</strong> {$date}</p>";
        $output .= "<div><strong>Contenido:</strong> {$content}</div>";
        $output .= "<div><strong>Metadatos:</strong><ul>";

        // Muestra los metadatos en una lista
        foreach ($post_meta as $key => $value) {
            $output .= "<li><strong>{$key}:</strong> " . esc_html(implode(', ', $value)) . "</li>";
        }
        $output .= "</ul></div>";

        $output .= "<div><strong>Taxonomías:</strong><ul>";
        // Muestra las taxonomías en una lista
        foreach ($terms as $taxonomy => $taxonomy_terms) {
            $output .= "<li><strong>{$taxonomy}:</strong><ul>";
            foreach ($taxonomy_terms as $term) {
                $output .= "<li>" . esc_html($term->name) . "</li>";
            }
            $output .= "</ul></li>";
        }
        $output .= "</ul></div>";

        // Verifica si la función get_fields() existe (por si ACF está instalado)
        if (function_exists('get_fields')) {
            $custom_fields = get_fields($post_id);
            if ($custom_fields) {
                $output .= "<div><strong>Campos personalizados:</strong><ul>";
                foreach ($custom_fields as $field_name => $field_value) {
                    $output .= "<li><strong>{$field_name}:</strong> " . esc_html($field_value) . "</li>";
                }
                $output .= "</ul></div>";
            } else {
                $output .= "<div><strong>Campos personalizados:</strong> No se encontraron.</div>";
            }
        } else {
            $output .= "<div><strong>Campos personalizados:</strong> La función get_fields() no está disponible.</div>";
        }

        $output .= "</div>"; // Cierra el div de post-details
        return $output;
    } else {
        return "<p>Post no encontrado.</p>";
    }
}

// Shortcode para mostrar todos los detalles de múltiples posts
function show_full_posts_details($atts) {
    $atts = shortcode_atts(array(
        'ids' => '',
    ), $atts);

    $ids = explode(',', $atts['ids']);
    $output = '';

    foreach ($ids as $id) {
        $output .= "<div class='post-wrapper'>";
        $output .= get_full_post_details(trim($id));
        $output .= "</div><hr>"; // Línea divisoria entre posts
    }

    return $output;
}

add_shortcode('mostrar_full_posts_detalles', 'show_full_posts_details'); */
