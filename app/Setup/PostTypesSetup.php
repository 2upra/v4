<?php

/**
 * Archivo creado por refactorización.
 * Contiene funciones para registrar Custom Post Types y estados de post personalizados.
 */

// Refactor(Org): Moved from app/Setup/ThemeSetup.php
// Registrar estado de publicación: Rechazado y Pendiente de Eliminación
function register_custom_post_statuses()
{
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

// Refactor(Org): Moved from app/Setup/ThemeSetup.php
// Registrar tipos de post: Samples, Álbums, Momentos y Colaboraciones
function register_custom_post_types()
{
    $post_types = [
        'social_post' => ['Samples', 'Sample', 'sample', 'dashicons-images-alt2'],
        'albums' => ['Albums', 'Album', 'album', 'dashicons-format-audio'],
        'stories' => ['Momentos', 'Momento', 'momentos', 'dashicons-camera'],
        'colab' => ['Colaboraciones', 'Colaboración', 'colab', 'dashicons-share-alt2'],
        'colecciones' =>  ['Colecciones', 'Colección', 'colecciones', 'dashicons-book'],
        'notificaciones' => ['Notificaciones', 'Notificación', 'notificacion', 'dashicons-bell'],
        'comentarios' => ['Comentarios', 'Comentario', 'comentario', 'dashicons-admin-comments'],
        'reporte' => ['Reportes', 'Reporte', 'reporte', 'dashicons-flag'],
        'tarea' => ['Tareas', 'Tarea', 'tarea', 'dashicons-list-check'],
        'notas' => ['Notas', 'Nota', 'notas', 'dashicons-admin-notes'],

    ];


    foreach ($post_types as $key => $type) {
        $name = $type[0];
        $singular = $type[1];
        $slug = $type[2];
        $icon = isset($type[3]) ? $type[3] : null;

        $args = [
            'labels' => [
                'name' => __($name),
                'singular_name' => __($singular)
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => $slug],
            'show_in_rest' => true,
            'menu_icon' => $icon
        ];

        register_post_type($key, $args);
    }
}

// Refactor(Org): Moved hooks from app/Setup/ThemeSetup.php
add_action('init', 'register_custom_post_statuses');
add_action('init', 'register_custom_post_types');

?>
