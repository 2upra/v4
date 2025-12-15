<?php

/**
 * Servicio para consultas de colecciones.
 * 
 * Responsabilidad única: Obtener y consultar datos de colecciones.
 *
 * @package Kamples\Services\Coleccion
 * @since 1.0.0
 */

namespace Kamples\Services\Coleccion;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColeccionQueryService
{
    private ColeccionSampleService $sampleService;

    public function __construct(?ColeccionSampleService $sampleService = null)
    {
        $this->sampleService = $sampleService ?? new ColeccionSampleService();
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
}
