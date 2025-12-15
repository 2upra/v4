<?php

/**
 * Servicio para consultas de colaboraciones.
 * 
 * Maneja la obtención de datos, variables y resúmenes
 * de colaboraciones existentes.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColabQueryService
{
    private $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtener el botón de colaboración para un post.
     * 
     * @param int  $postId ID del post.
     * @param bool $colab  Si el post permite colaboraciones.
     * @return string HTML del botón o string vacío.
     */
    public function obtenerBotonColab(int $postId, bool $colab): string
    {
        if (!$colab) {
            return '';
        }

        $iconoColab = $GLOBALS['iconocolab'] ?? '';

        return sprintf(
            '<div class="XFFPOX"><button class="ZYSVVV" data-post-id="%d">%s</button></div>',
            $postId,
            $iconoColab
        );
    }

    /**
     * Obtener variables de una colaboración.
     * 
     * @param int|null $postId ID del post (usa global $post si null).
     * @return array Variables de la colaboración.
     */
    public function obtenerVariablesColab(?int $postId = null): array
    {
        if ($postId === null) {
            global $post;
            $postId = $post->ID ?? 0;
        }

        $currentUserId     = get_current_user_id();
        $colabPostOrigen   = (int) get_post_meta($postId, 'colabPostOrigen', true);
        $colabAutor        = (int) get_post_meta($postId, 'colabAutor', true);
        $colabColaborador  = (int) get_post_meta($postId, 'colabColaborador', true);
        $colabMensaje      = get_post_meta($postId, 'colabMensaje', true);
        $colabFileUrl      = get_post_meta($postId, 'colabFileUrl', true);
        $postAudioLite     = get_post_meta($postId, 'post_audio_lite', true);
        $participantes     = get_post_meta($postId, 'participantes', true);
        $conversacionId    = (int) get_post_meta($postId, 'conversacion_id', true);

        /* Imagen del post */
        $imagenPost = get_the_post_thumbnail_url($postId, 'full');
        if (!$imagenPost) {
            $imagenPost = site_url('/wp-content/uploads/2024/09/1ndoryu_1725478496.webp');
        }
        $imagenPostOp = function_exists('img') ? img($imagenPost, 40, 'all') : $imagenPost;

        $postTitulo = get_the_title($postId);

        return [
            'post_id'                 => $postId,
            'conversacion_id'         => $conversacionId,
            'participantes'           => $participantes,
            'post_audio_lite'         => $postAudioLite,
            'current_user_id'         => $currentUserId,
            'colabPostOrigen'         => $colabPostOrigen,
            'colabAutor'              => $colabAutor,
            'colabColaborador'        => $colabColaborador,
            'colabMensaje'            => $colabMensaje,
            'colabFileUrl'            => $colabFileUrl,
            'colabAutorName'          => get_the_author_meta('display_name', $colabAutor),
            'colabColaboradorName'    => get_the_author_meta('display_name', $colabColaborador),
            'colabColaboradorAvatar'  => function_exists('imagenPerfil') ? imagenPerfil($colabColaborador) : '',
            'colabAutorAvatar'        => function_exists('imagenPerfil') ? imagenPerfil($colabAutor) : '',
            'colabFecha'              => get_the_date('', $postId),
            'colab_status'            => get_post_status($postId),
            'imagenPostOp'            => $imagenPostOp,
            'postTitulo'              => $postTitulo,
        ];
    }

    /**
     * Obtener resumen de colaboraciones del usuario actual.
     * 
     * @return array Colaboraciones del usuario.
     */
    public function obtenerColabsResumen(): array
    {
        $currentUserId = get_current_user_id();

        $args = [
            'post_type'      => 'colab',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => 'colabColaborador',
                    'value'   => $currentUserId,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => 'colabAutor',
                    'value'   => $currentUserId,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ],
            ],
        ];

        $query  = new \WP_Query($args);
        $colabs = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $postId = get_the_ID();

                $imagenPost = get_the_post_thumbnail_url($postId, 'full');
                if (!$imagenPost) {
                    $imagenPost = site_url('/wp-content/uploads/2024/09/1ndoryu_1725478496.webp');
                }

                $colabs[] = [
                    'post_id'         => $postId,
                    'conversacion_id' => get_post_meta($postId, 'conversacion_id', true),
                    'imagen'          => $imagenPost,
                    'titulo'          => get_the_title(),
                ];
            }
            wp_reset_postdata();
        }

        return $colabs;
    }
}
