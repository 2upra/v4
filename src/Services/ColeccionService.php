<?php

/**
 * Servicio para gestionar colecciones de samples.
 * 
 * Encapsula toda la lógica de negocio relacionada con el sistema
 * de colecciones (CPT 'colecciones').
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services;

/* Evitar acceso directo */

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColeccionService
{
    /**
     * Límite máximo de colecciones por usuario.
     */
    private const LIMITE_COLECCIONES = 50;

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
            $resultado = $this->añadirSampleAColeccion($coleccionId, $sampleId, $userId);
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
        $samples = $this->obtenerSamplesDeColeccion($coleccionId);

        /* Limpiar referencias en samplesGuardados del usuario */
        $this->limpiarReferenciasUsuario($userId, $samples, $coleccionId);

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
     * Guardar un sample en una colección.
     * 
     * @param mixed $colecId  ID de la colección o 'favoritos'/'despues'.
     * @param int   $sampleId ID del sample.
     * @param int   $userId   ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string, 'samples' => array]
     */
    public function guardarSample($colecId, int $sampleId, int $userId): array
    {
        /* Manejar colecciones especiales */
        if ($colecId === 'favoritos' || $colecId === 'despues') {
            $colecId = $this->obtenerOCrearColeccionEspecial($colecId, $userId);
            if (!$colecId) {
                return [
                    'exito'   => false,
                    'mensaje' => 'Error al crear la colección especial',
                    'samples' => []
                ];
            }
        }

        return $this->añadirSampleAColeccion((int) $colecId, $sampleId, $userId);
    }

    /**
     * Añadir un sample a una colección.
     * 
     * @param int $coleccionId ID de la colección.
     * @param int $sampleId    ID del sample.
     * @param int $userId      ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string, 'samples' => array]
     */
    public function añadirSampleAColeccion(int $coleccionId, int $sampleId, int $userId): array
    {
        $coleccion = get_post($coleccionId);

        if (!$coleccion || (int) $coleccion->post_author !== $userId) {
            return [
                'exito'   => false,
                'mensaje' => 'No tienes permiso para modificar esta colección',
                'samples' => []
            ];
        }

        $samples = $this->obtenerSamplesDeColeccion($coleccionId);

        /* Verificar duplicados */
        if (in_array($sampleId, $samples)) {
            return [
                'exito'   => false,
                'mensaje' => 'Este sample ya existe en la colección',
                'samples' => $samples
            ];
        }

        /* Añadir sample */
        $samples[] = $sampleId;
        $actualizado = update_post_meta($coleccionId, 'samples', $samples);

        if ($actualizado) {
            update_post_meta($coleccionId, 'ultimaModificacion', current_time('mysql'));
            $this->actualizarSamplesGuardadosUsuario($userId, $sampleId, $coleccionId, 'añadir');
            $this->borrarCacheColeccion($coleccionId);
            $this->actualizarTimestampSamplesGuardados($userId);

            return [
                'exito'   => true,
                'mensaje' => 'Sample agregado exitosamente',
                'samples' => $samples
            ];
        }

        return [
            'exito'   => false,
            'mensaje' => 'Error al guardar el sample en la colección',
            'samples' => []
        ];
    }

    /**
     * Eliminar un sample de una colección.
     * 
     * @param int $coleccionId ID de la colección.
     * @param int $sampleId    ID del sample.
     * @param int $userId      ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function eliminarSampleDeColeccion(int $coleccionId, int $sampleId, int $userId): array
    {
        $coleccion = get_post($coleccionId);

        if (!$coleccion) {
            return [
                'exito'   => false,
                'mensaje' => 'Colección no encontrada'
            ];
        }

        if ((int) $coleccion->post_author !== $userId) {
            return [
                'exito'   => false,
                'mensaje' => 'No tienes permisos para modificar esta colección'
            ];
        }

        $samples = $this->obtenerSamplesDeColeccion($coleccionId);
        $key = array_search($sampleId, $samples);

        if ($key === false) {
            return [
                'exito'   => false,
                'mensaje' => 'No se encontró el sample en la colección'
            ];
        }

        unset($samples[$key]);
        $samples = array_values($samples);
        update_post_meta($coleccionId, 'samples', $samples);

        $this->actualizarSamplesGuardadosUsuario($userId, $sampleId, $coleccionId, 'eliminar');
        $this->borrarCacheColeccion($coleccionId);

        return [
            'exito'   => true,
            'mensaje' => 'Sample eliminado de colección'
        ];
    }

    /**
     * Verificar en qué colecciones está un sample.
     * 
     * @param int $sampleId ID del sample.
     * @param int $userId   ID del usuario.
     * @return array IDs de las colecciones que contienen el sample.
     */
    public function verificarSampleEnColecciones(int $sampleId, int $userId): array
    {
        $coleccionesConSample = [];

        $colecciones = get_posts([
            'post_type'      => 'colecciones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'author'         => $userId,
        ]);

        foreach ($colecciones as $coleccion) {
            $samples = $this->obtenerSamplesDeColeccion($coleccion->ID);
            if (in_array($sampleId, $samples)) {
                $coleccionesConSample[] = $coleccion->ID;
            }
        }

        return $coleccionesConSample;
    }

    /**
     * Obtener lista de colecciones del usuario.
     * 
     * @param int $userId ID del usuario.
     * @return array Colecciones del usuario.
     */
    public function obtenerColeccionesUsuario(int $userId): array
    {
        $query = new \WP_Query([
            'post_type'      => 'colecciones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'author'         => $userId,
            'meta_key'       => 'ultimaModificacion',
            'orderby'        => 'meta_value',
            'order'          => 'DESC'
        ]);

        $colecciones = [];
        $imagenDefault = site_url('/wp-content/uploads/2024/10/699bc48ebc970652670ff977acc0fd92.jpg');

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $postId = get_the_ID();
                $thumbnailUrl = get_the_post_thumbnail_url($postId, 'thumbnail');

                $colecciones[] = [
                    'id'     => $postId,
                    'titulo' => get_the_title(),
                    'imagen' => $thumbnailUrl ?: $imagenDefault,
                ];
            }
            wp_reset_postdata();
        }

        return $colecciones;
    }

    /**
     * Obtener variables de una colección.
     * 
     * @param int|null $postId ID de la colección.
     * @return array Variables de la colección.
     */
    public function obtenerVariablesColec(?int $postId = null): array
    {
        if ($postId === null) {
            global $post;
            $postId = $post->ID ?? 0;
        }

        $usuarioActual  = get_current_user_id();
        $autorId        = (int) get_post_field('post_author', $postId);
        $samplesMeta    = get_post_meta($postId, 'samples', true);
        $datosColeccion = get_post_meta($postId, 'datosColeccion', true);
        $sampleCount    = 0;
        $sampleCountReal = 0;

        if (!empty($samplesMeta)) {
            $samplesArray = $this->deserializarDatos($samplesMeta);

            if (is_array($samplesArray)) {
                $sampleCount = count($samplesArray);

                if ($usuarioActual) {
                    $descargasAnteriores = get_user_meta($usuarioActual, 'descargas', true);
                    foreach ($samplesArray as $sampleId) {
                        if (!isset($descargasAnteriores[$sampleId])) {
                            $sampleCountReal++;
                        }
                    }
                } else {
                    $sampleCountReal = $sampleCount;
                }
            }
        }

        return [
            'fecha'          => get_the_date('', $postId),
            'colecStatus'    => get_post_status($postId),
            'autorId'        => $autorId,
            'samples'        => $sampleCount . ' samples',
            'datosColeccion' => $datosColeccion,
            'sampleCount'    => $sampleCountReal,
        ];
    }

    /**
     * Renderizar botón de colección para un post.
     * 
     * @param int $postId ID del post.
     * @return string HTML del botón.
     */
    public function renderizarBotonColeccion(int $postId): string
    {
        $extraClass = '';
        if (is_user_logged_in()) {
            $userId    = get_current_user_id();
            $coleccion = get_user_meta($userId, 'samplesGuardados', true);
            if (is_array($coleccion) && isset($coleccion[$postId])) {
                $extraClass = ' colabGuardado';
            }
        }

        $iconoGuardar = $GLOBALS['iconoGuardar'] ?? '';
        $nonce = wp_create_nonce('colec_nonce');

        ob_start();
?>
        <div class="ZAQIBB botonColeccion<?php echo esc_attr($extraClass); ?>">
            <button class="botonColeccionBtn" aria-label="Guardar sonido"
                data-post_id="<?php echo esc_attr($postId); ?>"
                data-nonce="<?php echo esc_attr($nonce); ?>">
                <?php echo $iconoGuardar; ?>
            </button>
        </div>
<?php
        return ob_get_clean();
    }

    /* =========================================================================
     * MÉTODOS PRIVADOS AUXILIARES
     * ========================================================================= */

    /**
     * Verificar si el usuario puede crear más colecciones.
     */
    private function puedeCrearColeccion(int $userId): bool
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
     * Obtener o crear colección especial (favoritos/despues).
     */
    private function obtenerOCrearColeccionEspecial(string $tipo, int $userId): ?int
    {
        $colecEspId = get_user_meta($userId, $tipo . '_coleccion_id', true);

        if ($colecEspId) {
            return (int) $colecEspId;
        }

        $titulo = ($tipo === 'favoritos') ? 'Favoritos' : 'Usar más tarde';
        $imgUrl = ($tipo === 'favoritos')
            ? site_url('/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg')
            : site_url('/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg');

        $colecEspId = wp_insert_post([
            'post_title'  => $titulo,
            'post_type'   => 'colecciones',
            'post_status' => 'publish',
            'post_author' => $userId,
        ]);

        if (!is_wp_error($colecEspId)) {
            update_user_meta($userId, $tipo . '_coleccion_id', $colecEspId);
            update_post_meta($colecEspId, 'coleccion_especial', $titulo);

            if (function_exists('subirImagenDesdeURL')) {
                $imgId = subirImagenDesdeURL($imgUrl, $colecEspId);
                if ($imgId) {
                    set_post_thumbnail($colecEspId, $imgId);
                }
            }

            return $colecEspId;
        }

        return null;
    }

    /**
     * Obtener samples de una colección.
     */
    private function obtenerSamplesDeColeccion(int $coleccionId): array
    {
        $samples = get_post_meta($coleccionId, 'samples', true);

        if (!is_array($samples)) {
            $samples = $this->deserializarDatos($samples);
        }

        return is_array($samples) ? $samples : [];
    }

    /**
     * Actualizar samplesGuardados del usuario.
     */
    private function actualizarSamplesGuardadosUsuario(int $userId, int $sampleId, int $coleccionId, string $accion): void
    {
        $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
        if (!is_array($samplesGuardados)) {
            $samplesGuardados = [];
        }

        if ($accion === 'añadir') {
            if (!isset($samplesGuardados[$sampleId])) {
                $samplesGuardados[$sampleId] = [];
            }
            if (!in_array($coleccionId, $samplesGuardados[$sampleId])) {
                $samplesGuardados[$sampleId][] = $coleccionId;
            }
        } elseif ($accion === 'eliminar') {
            if (isset($samplesGuardados[$sampleId])) {
                $index = array_search($coleccionId, $samplesGuardados[$sampleId]);
                if ($index !== false) {
                    unset($samplesGuardados[$sampleId][$index]);
                    $samplesGuardados[$sampleId] = array_values($samplesGuardados[$sampleId]);

                    if (empty($samplesGuardados[$sampleId])) {
                        unset($samplesGuardados[$sampleId]);
                    }
                }
            }
        }

        if (empty($samplesGuardados)) {
            delete_user_meta($userId, 'samplesGuardados');
        } else {
            update_user_meta($userId, 'samplesGuardados', $samplesGuardados);
        }
    }

    /**
     * Limpiar referencias del usuario al eliminar colección.
     */
    private function limpiarReferenciasUsuario(int $userId, array $samples, int $coleccionId): void
    {
        $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
        if (!is_array($samplesGuardados)) {
            return;
        }

        $modificado = false;
        foreach ($samples as $sampleId) {
            if (isset($samplesGuardados[$sampleId])) {
                $index = array_search($coleccionId, $samplesGuardados[$sampleId]);
                if ($index !== false) {
                    unset($samplesGuardados[$sampleId][$index]);
                    $samplesGuardados[$sampleId] = array_values($samplesGuardados[$sampleId]);

                    if (empty($samplesGuardados[$sampleId])) {
                        unset($samplesGuardados[$sampleId]);
                    }
                    $modificado = true;
                }
            }
        }

        if ($modificado) {
            if (empty($samplesGuardados)) {
                delete_user_meta($userId, 'samplesGuardados');
            } else {
                update_user_meta($userId, 'samplesGuardados', $samplesGuardados);
            }
        }
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

    /**
     * Deserializar datos (JSON o serialize).
     */
    private function deserializarDatos($data)
    {
        if (empty($data)) {
            return $data;
        }

        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $json = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        $unserialized = @unserialize($data);
        if ($unserialized !== false || $data === 'b:0;') {
            return $unserialized;
        }

        return $data;
    }

    /**
     * Borrar caché de colección.
     */
    private function borrarCacheColeccion(int $coleccionId): void
    {
        if (function_exists('borrarCacheColeccion')) {
            borrarCacheColeccion($coleccionId);
        }
    }

    /**
     * Actualizar timestamp de samples guardados.
     */
    private function actualizarTimestampSamplesGuardados(int $userId): void
    {
        if (function_exists('actualizarTimestampSamplesGuardados')) {
            actualizarTimestampSamplesGuardados($userId);
        }
    }
}
