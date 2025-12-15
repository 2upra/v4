<?php

/**
 * Servicio para operaciones CRUD de colaboraciones.
 * 
 * Maneja la creación, validación y gestión de archivos
 * de las colaboraciones musicales.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColabCrudService
{
    private $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
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
    public function crearConversacionColab(int $autorId, int $colaboradorId): ?int
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
    public function actualizarMetasPostOrigen(int $postId, int $userId): void
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
    public function adjuntarArchivoColab(int $colabId, string $fileUrl): bool
    {
        if (function_exists('adjuntarArchivo')) {
            return (bool) adjuntarArchivo($colabId, $fileUrl);
        }
        return false;
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
}
