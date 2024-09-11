<?php

// Registrar estado de publicación: Rechazado y Pendiente de Eliminación
function register_custom_post_statuses() {
    register_post_status('rejected', [
        'label' => _x('Rejected', 'post status'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>')
    ]);

    register_post_status('pending_deletion', [
        'label' => _x('Pending Deletion', 'post status'),
        'public' => false,
        'exclude_from_search' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Pending Deletion <span class="count">(%s)</span>', 'Pending Deletion <span class="count">(%s)</span>')
    ]);
}
add_action('init', 'register_custom_post_statuses');

// Registrar tipos de post: Samples, Álbums, Momentos y Colaboraciones
function register_custom_post_types() {
    $post_types = [
        'social_post' => ['Samples', 'Sample', 'sample', null],
        'albums' => ['Albums', 'Album', 'album', null],
        'stories' => ['Momentos', 'Momento', 'momentos', 'dashicons-camera'],
        'colab' => ['Colaboraciones', 'Colaboración', 'colab', null]
    ];

    foreach ($post_types as $key => $type) {
        $name = $type[0];
        $singular = $type[1];
        $slug = $type[2];
        $icon = isset($type[3]) ? $type[3] : null; // Asegurarse que $icon tiene un valor válido

        $args = [
            'labels' => [
                'name' => __($name),
                'singular_name' => __($singular)
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'comments', 'custom-fields'],
            'rewrite' => ['slug' => $slug],
            'show_in_rest' => true,
            'menu_icon' => $icon
        ];

        register_post_type($key, $args);
    }
}
add_action('init', 'register_custom_post_types');

// Registrar meta para publicaciones de Colaboración
function register_colab_meta() {
    register_post_meta('colab', 'para_colab', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
    ]);
}
add_action('init', 'register_colab_meta');
