<?php

/**
 * Registro de Custom Post Types y Estados
 * 
 * Centraliza el registro de todos los tipos de post personalizados
 * y estados de publicación del tema.
 *
 * @package Kamples\Core
 * @since 1.0.0
 */

namespace Kamples\Core;

class PostTypes
{
    /**
     * Tipos de post a registrar
     * 
     * @var array
     */
    private static array $postTypes = [
        'social_post' => ['Samples', 'Sample', 'sample', 'dashicons-images-alt2'],
        'albums' => ['Albums', 'Album', 'album', 'dashicons-format-audio'],
        'stories' => ['Momentos', 'Momento', 'momentos', 'dashicons-camera'],
        'colab' => ['Colaboraciones', 'Colaboración', 'colab', 'dashicons-share-alt2'],
        'colecciones' => ['Colecciones', 'Colección', 'colecciones', 'dashicons-book'],
        'notificaciones' => ['Notificaciones', 'Notificación', 'notificacion', 'dashicons-bell'],
        'comentarios' => ['Comentarios', 'Comentario', 'comentario', 'dashicons-admin-comments'],
        'reporte' => ['Reportes', 'Reporte', 'reporte', 'dashicons-flag'],
        'tarea' => ['Tareas', 'Tarea', 'tarea', 'dashicons-list-check'],
        'notas' => ['Notas', 'Nota', 'notas', 'dashicons-admin-notes'],
    ];

    /**
     * Inicializa los hooks para registrar CPTs y estados
     * 
     * @return void
     */
    public static function inicializar(): void
    {
        add_action('init', [self::class, 'registrarEstadosPersonalizados']);
        add_action('init', [self::class, 'registrarTiposDePost']);
        add_action('init', [self::class, 'registrarMetaColab']);
        add_action('save_post', [self::class, 'regenerarSitemapColecciones'], 10, 3);

        /* Evitar que WP genere títulos por defecto */
        remove_action('wp_head', '_wp_render_title_tag', 1);
    }

    /**
     * Registra estados de publicación personalizados
     * 
     * @return void
     */
    public static function registrarEstadosPersonalizados(): void
    {
        register_post_status('rejected', [
            'label' => _x('Rejected', 'post status'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Rejected <span class="count">(%s)</span>',
                'Rejected <span class="count">(%s)</span>'
            )
        ]);

        register_post_status('pending_deletion', [
            'label' => _x('Pending Deletion', 'post status'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Pending Deletion <span class="count">(%s)</span>',
                'Pending Deletion <span class="count">(%s)</span>'
            )
        ]);
    }

    /**
     * Registra los tipos de post personalizados
     * 
     * @return void
     */
    public static function registrarTiposDePost(): void
    {
        foreach (self::$postTypes as $key => $type) {
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

    /**
     * Registra meta para publicaciones de Colaboración
     * 
     * @return void
     */
    public static function registrarMetaColab(): void
    {
        register_post_meta('colab', 'paraColab', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
        ]);
    }

    /**
     * Regenera sitemap de colecciones cuando se guarda una
     * 
     * @param int $postId ID del post
     * @param \WP_Post $post Objeto del post
     * @param bool $update Si es actualización
     * @return void
     */
    public static function regenerarSitemapColecciones(int $postId, \WP_Post $post, bool $update): void
    {
        if ('colecciones' !== $post->post_type || 'publish' !== $post->post_status) {
            return;
        }

        $sitemapUrl = get_permalink(get_page_by_path('sitemapcolec'));

        if ($sitemapUrl) {
            wp_remote_get($sitemapUrl);
        }
    }

    /**
     * Obtiene la lista de tipos de post registrados
     * 
     * @return array
     */
    public static function obtenerTiposDePost(): array
    {
        return self::$postTypes;
    }
}
