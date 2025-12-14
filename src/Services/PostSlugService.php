<?php

/**
 * Servicio de gestión de slugs de posts
 * 
 * Maneja la actualización automática de títulos y slugs
 * basándose en el contenido del post.
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class PostSlugService
{
    private \Logger $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Inicializa los hooks del servicio
     * 
     * @return void
     */
    public static function inicializar(): void
    {
        add_action('post_updated', [self::class, 'actualizarTitulosYSlugs'], 10, 3);
    }

    /**
     * Registra un cambio de slug en el archivo de log
     * 
     * @param string $slugAnterior Slug anterior
     * @param int $postId ID del post
     * @param string $nuevoSlug Nuevo slug
     * @return void
     */
    public function registrarCambioSlug(string $slugAnterior, int $postId, string $nuevoSlug): void
    {
        $logFile = get_stylesheet_directory() . '/cambiosSlug.log';
        $date = current_time('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] Post ID: %d | Slug Anterior: %s | Nuevo Slug: %s\n",
            $date,
            $postId,
            $slugAnterior,
            $nuevoSlug
        );

        if (is_writable($logFile) || !file_exists($logFile)) {
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } else {
            $this->logger->warning('post', 'No se puede escribir en archivo de log de slugs', [
                'archivo' => $logFile
            ]);
        }
    }

    /**
     * Actualiza títulos y slugs de social_posts verificados
     * 
     * @param int $postId ID del post
     * @param \WP_Post $postAfter Post después de actualización
     * @param \WP_Post $postBefore Post antes de actualización
     * @return void
     */
    public static function actualizarTitulosYSlugs(int $postId, \WP_Post $postAfter, \WP_Post $postBefore): void
    {
        $servicio = new self();
        $servicio->procesarActualizacion($postId, $postAfter, $postBefore);
    }

    /**
     * Procesa la actualización de título y slug
     * 
     * @param int $postId ID del post
     * @param \WP_Post $postAfter Post después de actualización
     * @param \WP_Post $postBefore Post antes de actualización
     * @return void
     */
    public function procesarActualizacion(int $postId, \WP_Post $postAfter, \WP_Post $postBefore): void
    {
        /* Verificar tipo de post */
        if ('social_post' !== get_post_type($postId)) {
            return;
        }

        /* Verificar si el post está verificado */
        if ('1' !== get_post_meta($postId, 'Verificado', true)) {
            return;
        }

        /* Verificar si el contenido ha cambiado */
        if ($postBefore->post_content === $postAfter->post_content) {
            return;
        }

        /* Extraer contenido sin etiquetas HTML */
        $contenido = wp_strip_all_tags($postAfter->post_content);
        $nuevoTitulo = sanitize_text_field($contenido);
        $nuevoSlug = sanitize_title($contenido);

        /* Verificar que el contenido no esté vacío */
        if (empty($nuevoTitulo) || empty($nuevoSlug)) {
            return;
        }

        $slugActual = get_post_field('post_name', $postId);
        $nuevoSlugUnico = wp_unique_post_slug(
            $nuevoSlug,
            $postId,
            get_post_status($postId),
            get_post_type($postId),
            get_post_parent($postId)
        );

        $postData = [
            'ID' => $postId,
            'post_title' => $nuevoTitulo,
            'post_name' => $nuevoSlugUnico,
        ];

        $tituloActual = get_post_field('post_title', $postId);
        $actualizarTitulo = ($tituloActual !== $nuevoTitulo);
        $actualizarSlug = ($slugActual !== $nuevoSlugUnico);

        if ($actualizarTitulo || $actualizarSlug) {
            /* Remover la acción temporalmente para evitar bucle infinito */
            remove_action('post_updated', [self::class, 'actualizarTitulosYSlugs'], 10);

            $resultado = wp_update_post($postData, true);

            if (!is_wp_error($resultado)) {
                update_post_meta($postId, 'ultima_actualizacion_slug', current_time('mysql'));

                if ($actualizarSlug) {
                    $this->registrarCambioSlug($slugActual, $postId, $nuevoSlugUnico);
                }

                $this->logger->info('post', 'Título y slug actualizado', [
                    'postId' => $postId,
                    'nuevoTitulo' => $nuevoTitulo
                ]);
            }

            /* Volver a añadir la acción */
            add_action('post_updated', [self::class, 'actualizarTitulosYSlugs'], 10, 3);
        }
    }
}
