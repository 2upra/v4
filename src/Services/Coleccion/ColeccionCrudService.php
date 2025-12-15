<?php

/**
 * Servicio para operaciones CRUD de colecciones.
 * 
 * Responsabilidad única: Crear, editar y eliminar colecciones.
 *
 * @package Kamples\Services\Coleccion
 * @since 1.0.0
 */

namespace Kamples\Services\Coleccion;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColeccionCrudService
{
    /**
     * Límite máximo de colecciones por usuario.
     */
    private const LIMITE_COLECCIONES = 50;

    private $logger;
    private ColeccionSampleService $sampleService;

    public function __construct(?ColeccionSampleService $sampleService = null)
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->sampleService = $sampleService ?? new ColeccionSampleService();
    }

    /**
     * Crear una nueva colección.
     * 
     * @param array $datos Datos de la colección.
     * @return array ['exito' => bool, 'coleccionId' => int|null, 'mensaje' => string]
     */
    public function crearColeccion(array $datos): array
    {
        $userId      = (int) $datos['userId'];
        $titulo      = sanitize_text_field($datos['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($datos['descripcion'] ?? '');
        $sampleId    = (int) ($datos['sampleId'] ?? 0);
        $imgUrl      = $this->sanitizarUrlImagen($datos['imgColec'] ?? '');
        $imgColecId  = sanitize_text_field($datos['imgColecId'] ?? '');
        $privado     = (int) ($datos['privado'] ?? 0);

        $this->logger->info('guardar', "Creando colección: titulo=$titulo, sampleId=$sampleId");

        /* Validar título */
        if (empty($titulo)) {
            $this->logger->warning('guardar', 'Título de colección vacío');
            return [
                'exito'       => false,
                'coleccionId' => null,
                'mensaje'     => 'El título de la colección es obligatorio'
            ];
        }

        /* Verificar límite de colecciones */
        if (!$this->puedeCrearColeccion($userId)) {
            $this->logger->warning('guardar', "Usuario $userId alcanzó límite de colecciones");
            return [
                'exito'       => false,
                'coleccionId' => null,
                'mensaje'     => 'Has alcanzado el límite de ' . self::LIMITE_COLECCIONES . ' colecciones'
            ];
        }

        /* Crear la colección */
        $coleccionId = wp_insert_post([
            'post_title'   => $titulo,
            'post_content' => $descripcion,
            'post_type'    => 'colecciones',
            'post_status'  => 'publish',
            'post_author'  => $userId,
        ]);

        if (!$coleccionId || is_wp_error($coleccionId)) {
            $this->logger->error('guardar', 'Error al crear colección');
            return [
                'exito'       => false,
                'coleccionId' => null,
                'mensaje'     => 'Error al crear la colección'
            ];
        }

        $this->logger->info('guardar', "Colección creada: ID $coleccionId");

        /* Establecer imagen destacada */
        if (!empty($imgUrl) && function_exists('subirImagenDesdeURL')) {
            $imageId = subirImagenDesdeURL($imgUrl, $coleccionId);
            if ($imageId) {
                set_post_thumbnail($coleccionId, $imageId);
                $this->logger->info('guardar', "Imagen destacada establecida: $imageId");
            }
        }

        /* Guardar privacidad */
        if ($privado === 1) {
            update_post_meta($coleccionId, 'privado', 1);
        }

        /* Guardar imgColecId */
        if (!empty($imgColecId) && $imgColecId !== 'null') {
            update_post_meta($coleccionId, 'imgColecId', $imgColecId);
        }

        /* Añadir sample inicial si existe */
        if ($sampleId > 0) {
            $resultado = $this->sampleService->añadirSampleAColeccion($coleccionId, $sampleId, $userId);
            if (!$resultado['exito']) {
                wp_delete_post($coleccionId, true);
                return [
                    'exito'       => false,
                    'coleccionId' => null,
                    'mensaje'     => $resultado['mensaje']
                ];
            }
        }

        return [
            'exito'       => true,
            'coleccionId' => $coleccionId,
            'mensaje'     => 'Colección creada exitosamente'
        ];
    }

    /**
     * Editar una colección existente.
     * 
     * @param array $datos Datos de la colección.
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function editarColeccion(array $datos): array
    {
        $coleccionId = (int) $datos['coleccionId'];
        $userId      = (int) $datos['userId'];
        $nombre      = sanitize_text_field($datos['nombre'] ?? '');
        $descripcion = sanitize_textarea_field($datos['descripcion'] ?? '');
        $tags        = isset($datos['tags']) ? array_map('sanitize_text_field', $datos['tags']) : [];
        $imageUrl    = esc_url_raw($datos['imagen'] ?? '');

        $coleccion = get_post($coleccionId);

        if (!$coleccion || (int) $coleccion->post_author !== $userId) {
            return [
                'exito'   => false,
                'mensaje' => 'No tienes permisos para editar esta colección'
            ];
        }

        /* Actualizar título y descripción */
        wp_update_post([
            'ID'           => $coleccionId,
            'post_title'   => $nombre,
            'post_content' => $descripcion,
        ]);

        /* Actualizar tags */
        if (!empty($tags)) {
            update_post_meta($coleccionId, 'tagsColec', $tags);
        } else {
            delete_post_meta($coleccionId, 'tagsColec');
        }

        /* Actualizar imagen si se proporcionó */
        if ($imageUrl && function_exists('subirImagenDesdeURL')) {
            $imageId = subirImagenDesdeURL($imageUrl, $coleccionId);
            if ($imageId) {
                set_post_thumbnail($coleccionId, $imageId);
            }
        }

        return [
            'exito'   => true,
            'mensaje' => 'Colección actualizada correctamente'
        ];
    }

    /**
     * Eliminar una colección.
     * 
     * @param int $coleccionId ID de la colección.
     * @param int $userId      ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function eliminarColeccion(int $coleccionId, int $userId): array
    {
        $coleccion = get_post($coleccionId);

        if (!$coleccion) {
            $this->logger->warning('guardar', "Colección $coleccionId no existe");
            return [
                'exito'   => false,
                'mensaje' => 'La colección no existe'
            ];
        }

        if ((int) $coleccion->post_author !== $userId) {
            $this->logger->warning('guardar', "Usuario $userId sin permisos para eliminar colección $coleccionId");
            return [
                'exito'   => false,
                'mensaje' => 'No tienes permisos para eliminar esta colección'
            ];
        }

        /* Obtener samples de la colección */
        $samples = $this->sampleService->obtenerSamplesDeColeccion($coleccionId);

        /* Limpiar referencias en samplesGuardados del usuario */
        $this->sampleService->limpiarReferenciasUsuario($userId, $samples, $coleccionId);

        /* Eliminar la colección */
        if (!wp_delete_post($coleccionId, true)) {
            $this->logger->error('guardar', "Error al eliminar colección $coleccionId");
            return [
                'exito'   => false,
                'mensaje' => 'Error al eliminar la colección'
            ];
        }

        return [
            'exito'   => true,
            'mensaje' => 'Colección eliminada correctamente'
        ];
    }

    /**
     * Verificar si el usuario puede crear más colecciones.
     */
    public function puedeCrearColeccion(int $userId): bool
    {
        $query = new \WP_Query([
            'post_type'      => 'colecciones',
            'post_status'    => 'publish',
            'author'         => $userId,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        return $query->found_posts < self::LIMITE_COLECCIONES;
    }

    /**
     * Sanitizar URL de imagen.
     */
    private function sanitizarUrlImagen(string $imgUrl): string
    {
        if ($imgUrl === 'http://null' || $imgUrl === 'null' || empty($imgUrl)) {
            return '';
        }
        return esc_url_raw($imgUrl);
    }
}
