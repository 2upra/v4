<?php

/**
 * Controlador para gestión de posts
 * 
 * Maneja acciones AJAX relacionadas con posts como banear usuarios,
 * actualizar contenido, etc.
 *
 * @package Kamples\Controllers
 * @since 1.0.0
 */

namespace Kamples\Controllers;

class PostController
{
    private \Logger $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registra los hooks de AJAX
     * 
     * @return void
     */
    public static function registrarHooks(): void
    {
        add_action('wp_ajax_handle_user_modification', [self::class, 'handleUserModification']);
        add_action('wp_ajax_update_post_content', [self::class, 'updatePostContent']);
    }

    /**
     * Maneja la modificación de usuario (banear)
     * 
     * @return void
     */
    public static function handleUserModification(): void
    {
        $controller = new self();
        $controller->procesarModificacionUsuario();
    }

    /**
     * Procesa la modificación de usuario
     * 
     * @return void
     */
    public function procesarModificacionUsuario(): void
    {
        if (!current_user_can('administrator') || !isset($_POST['author_id'])) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
            return;
        }

        $authorId = intval($_POST['author_id']);
        $userData = get_userdata($authorId);

        if (!$userData || in_array('administrator', $userData->roles)) {
            wp_send_json_error('No se puede modificar este usuario.');
            return;
        }

        /* Obtener y eliminar todos los posts del usuario */
        $args = [
            'author' => $authorId,
            'posts_per_page' => -1,
            'post_type' => 'any',
            'post_status' => 'any'
        ];

        /** @var \WP_Post[] $userPosts */
        $userPosts = get_posts($args);
        $deletedCount = 0;

        foreach ($userPosts as $post) {
            if ($post instanceof \WP_Post) {
                wp_delete_post($post->ID, true);
                $deletedCount++;
            }
        }

        /* Cambiar el rol del usuario a 'sin_acceso' */
        $user = new \WP_User($authorId);
        $user->set_role('sin_acceso');

        $this->logger->info('post', 'Usuario baneado', [
            'userId' => $authorId,
            'postsEliminados' => $deletedCount
        ]);

        wp_send_json_success('Publicaciones eliminadas y usuario desactivado.');
    }

    /**
     * Actualiza contenido del post via AJAX
     * 
     * @return void
     */
    public static function updatePostContent(): void
    {
        $controller = new self();
        $controller->procesarActualizacionContenido();
    }

    /**
     * Procesa la actualización de contenido
     * 
     * @return void
     */
    public function procesarActualizacionContenido(): void
    {
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';

        if (!current_user_can('edit_post', $postId)) {
            wp_send_json_error(['message' => 'No tienes permiso para editar este post.']);
            return;
        }

        $postData = [
            'ID' => $postId,
            'post_content' => $content
        ];

        $updated = wp_update_post($postData);

        if (is_wp_error($updated)) {
            $this->logger->error('post', 'Error actualizando post', [
                'postId' => $postId,
                'error' => $updated->get_error_message()
            ]);
            wp_send_json_error(['message' => 'Error al actualizar el post.']);
            return;
        }

        wp_set_post_tags($postId, $tags);

        $this->logger->info('post', 'Post actualizado', ['postId' => $postId]);
        wp_send_json_success(['message' => 'Post y tags actualizados con éxito.']);
    }

    /**
     * Registra script de edición de posts
     * 
     * @return void
     */
    public static function encolarScriptEditarPost(): void
    {
        wp_register_script(
            'editar-post-js',
            get_template_directory_uri() . '/js/editarpost.js',
            ['jquery'],
            '1.0.16',
            true
        );

        wp_localize_script('editar-post-js', 'ajax_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);

        wp_enqueue_script('editar-post-js');
    }

    /**
     * Inicializa el controlador
     * 
     * @return void
     */
    public static function inicializar(): void
    {
        self::registrarHooks();
        add_action('wp_enqueue_scripts', [self::class, 'encolarScriptEditarPost']);
    }
}
