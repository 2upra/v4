<?php

/**
 * Servicio para gestionar colaboraciones entre usuarios.
 * 
 * Encapsula toda la lógica de negocio relacionada con el sistema
 * de colaboraciones musicales (CPT 'colab').
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services;

/* Evitar acceso directo */

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColabService
{
    /**
     * Logger para registro de eventos.
     */
    private $logger;

    /**
     * Constructor.
     */
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
     * Validar si un usuario puede iniciar una colaboración.
     * 
     * @param int      $userId        ID del usuario actual.
     * @param int      $postId        ID del post origen.
     * @param \WP_Post $originalPost  Post original.
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarPuedeColaborar(int $userId, int $postId, \WP_Post $originalPost): array
    {
        /* No puede colaborar consigo mismo */
        if ($userId === (int) $originalPost->post_author) {
            return [
                'valido'  => false,
                'mensaje' => 'No puedes colaborar contigo mismo.'
            ];
        }

        /* Verificar si ya existe una colaboración */
        $colabsExistentes = get_post_meta($postId, 'colabs', true) ?: [];
        if (in_array($userId, $colabsExistentes)) {
            return [
                'valido'  => false,
                'mensaje' => 'Ya existe una colaboración existente para esta publicación'
            ];
        }

        return [
            'valido'  => true,
            'mensaje' => ''
        ];
    }

    /**
     * Crear una nueva colaboración.
     * 
     * @param array $datos Datos de la colaboración.
     * @return array ['exito' => bool, 'colabId' => int|null, 'mensaje' => string]
     */
    public function crearColaboracion(array $datos): array
    {
        $postId     = (int) $datos['postId'];
        $userId     = (int) $datos['userId'];
        $mensaje    = sanitize_textarea_field($datos['mensaje'] ?? '');
        $fileUrl    = esc_url_raw($datos['fileUrl'] ?? '');
        $fileId     = (int) ($datos['fileId'] ?? 0);

        $originalPost = get_post($postId);
        if (!$originalPost) {
            $this->logger->warning('colab', 'Post no encontrado', ['postId' => $postId]);
            return [
                'exito'   => false,
                'colabId' => null,
                'mensaje' => 'Publicación no encontrada'
            ];
        }

        /* Validar permisos */
        $validacion = $this->validarPuedeColaborar($userId, $postId, $originalPost);
        if (!$validacion['valido']) {
            return [
                'exito'   => false,
                'colabId' => null,
                'mensaje' => $validacion['mensaje']
            ];
        }

        /* Obtener nombres */
        $nombreAutor       = get_the_author_meta('display_name', $originalPost->post_author);
        $nombreColaborador = get_the_author_meta('display_name', $userId);

        /* Crear post de colaboración */
        $nuevoColabId = wp_insert_post([
            'post_author' => $originalPost->post_author,
            'post_title'  => sprintf('Colab entre %s y %s', $nombreAutor, $nombreColaborador),
            'post_type'   => 'colab',
            'post_status' => 'pending',
            'meta_input'  => [
                'colabPostOrigen'   => $postId,
                'colabAutor'        => $originalPost->post_author,
                'colabColaborador'  => $userId,
                'colabMensaje'      => $mensaje,
                'participantes'     => [$originalPost->post_author, $userId]
            ],
        ]);

        if (!$nuevoColabId || is_wp_error($nuevoColabId)) {
            $this->logger->error('colab', 'Error al crear colaboración', [
                'postId' => $postId,
                'userId' => $userId
            ]);
            return [
                'exito'   => false,
                'colabId' => null,
                'mensaje' => 'Error al crear la colaboración'
            ];
        }

        $this->logger->info('colab', 'Colaboración creada', ['colabId' => $nuevoColabId]);

        /* Crear conversación asociada */
        $conversacionId = $this->crearConversacionColab($originalPost->post_author, $userId);
        if ($conversacionId) {
            update_post_meta($nuevoColabId, 'conversacion_id', $conversacionId);
            update_post_meta($nuevoColabId, 'participantes', [$originalPost->post_author, $userId]);
        } else {
            $this->logger->error('colab', 'Error al crear conversación', ['colabId' => $nuevoColabId]);
            return [
                'exito'   => false,
                'colabId' => null,
                'mensaje' => 'Error al crear la conversación'
            ];
        }

        /* Actualizar metadatos del post original */
        $this->actualizarMetasPostOrigen($postId, $userId);

        /* Adjuntar archivo si existe */
        if (!empty($fileUrl)) {
            $adjuntado = $this->adjuntarArchivoColab($nuevoColabId, $fileUrl);
            if (!$adjuntado) {
                return [
                    'exito'   => false,
                    'colabId' => null,
                    'mensaje' => 'No se pudo adjuntar el archivo correctamente.'
                ];
            }
        }

        /* Confirmar archivo por ID si se proporciona */
        if ($fileId && function_exists('confirmarHashId')) {
            confirmarHashId($fileId);
            $this->logger->info('colab', 'Archivo confirmado', ['fileId' => $fileId]);
        }

        return [
            'exito'   => true,
            'colabId' => $nuevoColabId,
            'mensaje' => 'Colaboración iniciada correctamente'
        ];
    }

    /**
     * Crear conversación para la colaboración.
     * 
     * @param int $autorId       ID del autor.
     * @param int $colaboradorId ID del colaborador.
     * @return int|null ID de la conversación o null si falla.
     */
    private function crearConversacionColab(int $autorId, int $colaboradorId): ?int
    {
        global $wpdb;

        $tablaConversacion = $wpdb->prefix . 'conversacion';
        $tipoConversacion  = 2; /* Tipo colab */
        $participantes     = json_encode([$autorId, $colaboradorId]);
        $fecha             = current_time('mysql');

        $insertado = $wpdb->insert(
            $tablaConversacion,
            [
                'tipo'          => $tipoConversacion,
                'participantes' => $participantes,
                'fecha'         => $fecha,
            ],
            ['%d', '%s', '%s']
        );

        if ($insertado) {
            $conversacionId = $wpdb->insert_id;
            $this->logger->info('colab', 'Conversación creada', ['conversacionId' => $conversacionId]);
            return $conversacionId;
        }

        return null;
    }

    /**
     * Actualizar metadatos del post original al crear colaboración.
     * 
     * @param int $postId ID del post original.
     * @param int $userId ID del colaborador.
     */
    private function actualizarMetasPostOrigen(int $postId, int $userId): void
    {
        /* Añadir colaborador a la lista de colabs */
        $colabsExistentes   = get_post_meta($postId, 'colabs', true) ?: [];
        $colabsExistentes[] = $userId;
        update_post_meta($postId, 'colabs', $colabsExistentes);

        /* Añadir a participantes */
        $participantes = get_post_meta($postId, 'participantes', true) ?: [];
        if (!in_array($userId, $participantes)) {
            $participantes[] = $userId;
            update_post_meta($postId, 'participantes', $participantes);
        }
    }

    /**
     * Adjuntar archivo a la colaboración.
     * 
     * @param int    $colabId ID de la colaboración.
     * @param string $fileUrl URL del archivo.
     * @return bool True si se adjuntó correctamente.
     */
    private function adjuntarArchivoColab(int $colabId, string $fileUrl): bool
    {
        if (function_exists('adjuntarArchivo')) {
            return (bool) adjuntarArchivo($colabId, $fileUrl);
        }
        return false;
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
     * Manejar cambio de estado de colaboración.
     * 
     * @param int      $postId     ID del post.
     * @param \WP_Post $postAfter  Post después del cambio.
     * @param \WP_Post $postBefore Post antes del cambio.
     */
    public function manejarCambioEstado(int $postId, \WP_Post $postAfter, \WP_Post $postBefore): void
    {
        if ($postAfter->post_type !== 'colab') {
            return;
        }

        /* Si el estado cambia a algo distinto de publish o pending, eliminar de colabs */
        if ($postAfter->post_status !== 'publish' && $postAfter->post_status !== 'pending') {
            $postOrigenId   = (int) get_post_meta($postId, 'colabPostOrigen', true);
            $colaboradorId  = (int) get_post_meta($postId, 'colabColaborador', true);

            $colabsMeta = get_post_meta($postOrigenId, 'colabs', true);
            if (is_array($colabsMeta)) {
                $key = array_search($colaboradorId, $colabsMeta);
                if ($key !== false) {
                    unset($colabsMeta[$key]);
                    $resultado = update_post_meta($postOrigenId, 'colabs', array_values($colabsMeta));
                    if (!$resultado) {
                        $this->logger->error('colab', 'Error al actualizar metadatos de colaboración', [
                            'postOrigenId' => $postOrigenId
                        ]);
                    }
                }
            }
        }
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
