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
