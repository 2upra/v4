<?php

/**
 * Componentes de descarga
 * 
 * Botones de descarga y sincronización para posts
 *
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

use Kamples\Services\DescargaService;

class DescargaComponents
{
    /**
     * Renderiza el botón de descarga para un post
     *
     * @param int $postId ID del post
     * @return string HTML del botón
     */
    public static function botonDescarga(int $postId): string
    {
        $paraDescarga = get_post_meta($postId, 'paraDescarga', true);

        if ($paraDescarga != '1') {
            return '';
        }

        $userId = get_current_user_id();
        $iconoDescarga = $GLOBALS['descargaicono'] ?? '';

        if (!$userId) {
            return self::botonDescargaNoLogueado($iconoDescarga);
        }

        $descargaService = DescargaService::obtenerInstancia();
        $yaDescargado = $descargaService->yaDescargado($userId, $postId);
        $claseExtra = $yaDescargado ? 'yaDescargado' : '';
        $esColeccion = get_post_type($postId) === 'colecciones' ? 'true' : 'false';

        ob_start();
?>
        <div class="ZAQIBB" id="descarga-container-<?= esc_attr($postId); ?>">
            <button class="icon-arrow-down <?= esc_attr($claseExtra); ?>"
                data-post-id="<?= esc_attr($postId); ?>"
                aria-label="Boton Descarga"
                id="download-button-<?= esc_attr($postId); ?>"
                onclick="return procesarDescarga('<?= esc_js($postId); ?>', '<?= esc_js($userId); ?>', '<?= $esColeccion; ?>')">
                <?= $iconoDescarga; ?>
            </button>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el botón de sincronización para un post
     *
     * @param int $postId ID del post
     * @return string HTML del botón
     */
    public static function botonSincronizar(int $postId): string
    {
        $paraDescarga = get_post_meta($postId, 'paraDescarga', true);

        if ($paraDescarga != '1') {
            return '';
        }

        $userId = get_current_user_id();
        $iconoDescarga = $GLOBALS['descargaicono'] ?? '';

        if (!$userId) {
            return self::botonDescargaNoLogueado($iconoDescarga);
        }

        $descargaService = DescargaService::obtenerInstancia();
        $yaDescargado = $descargaService->yaDescargado($userId, $postId);
        $claseExtra = $yaDescargado ? 'yaDescargado' : '';
        $esColeccion = get_post_type($postId) === 'colecciones' ? 'true' : 'false';

        ob_start();
    ?>
        <div class="ZAQIBB" id="sync-container-<?= esc_attr($postId); ?>">
            <button class="icon-arrow-down <?= esc_attr($claseExtra); ?>"
                data-post-id="<?= esc_attr($postId); ?>"
                aria-label="Boton Sincronizar"
                id="sync-button-<?= esc_attr($postId); ?>"
                onclick="return procesarDescarga('<?= esc_js($postId); ?>', '<?= esc_js($userId); ?>', '<?= $esColeccion; ?>')">
                <?= $iconoDescarga; ?>
            </button>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza botón de descarga para usuarios no logueados
     */
    private static function botonDescargaNoLogueado(string $icono): string
    {
        ob_start();
    ?>
        <div class="ZAQIBB">
            <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');"
                class="icon-arrow-down"
                aria-label="Descargar">
                <?= $icono; ?>
            </button>
        </div>
<?php
        return ob_get_clean();
    }
}
