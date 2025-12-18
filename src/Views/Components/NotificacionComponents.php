<?php

namespace Kamples\Views\Components;

use Kamples\Services\Social\NotificacionService;
use Kamples\Services\Usuario\PerfilService;
use Kamples\Services\Core\UtilService;

/**
 * Componentes de vista para notificaciones.
 *
 * @since 2.0.0
 */
class NotificacionComponents
{
    /**
     * Renderiza la lista de notificaciones.
     * 
     * @param int $usuarioId ID del usuario
     * @param int $pagina Número de página
     * @return string HTML de las notificaciones
     */
    public static function listarNotificaciones(int $usuarioId = 0, int $pagina = 1): string
    {
        if ($usuarioId === 0) {
            $usuarioId = get_current_user_id();
        }

        $notificacionesPorPagina = 12;
        $offset = ($pagina - 1) * $notificacionesPorPagina;

        $args = [
            'post_type'      => 'notificaciones',
            'post_status'    => 'publish',
            'posts_per_page' => $notificacionesPorPagina,
            'offset'         => $offset,
            'author'         => $usuarioId,
        ];

        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            return '<p class="sinnotifi">No hay notificaciones disponibles.</p>';
        }

        ob_start();
        echo '<ul>';

        while ($query->have_posts()) {
            $query->the_post();
            echo self::renderNotificacionItem();
        }

        echo '</ul>';
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Renderiza un item de notificación individual.
     * 
     * @return string HTML del item
     */
    private static function renderNotificacionItem(): string
    {
        $postId = get_the_ID();
        $emisor = get_post_meta($postId, 'emisor', true);
        $postRelacionado = get_post_meta($postId, 'post_relacionado', true);
        $fechaPublicacion = get_the_date('Y-m-d H:i:s');

        $fechaRelativa = UtilService::obtenerInstancia()->tiempoRelativo($fechaPublicacion);

        $avatarOptimizado = $emisor ? PerfilService::obtenerInstancia()->obtenerImagenPerfil($emisor) : '';

        ob_start();
?>
        <li class="notificacion-item" data-notificacion-id="<?php echo esc_attr($postId); ?>">
            <?php if (!empty($postRelacionado)) : ?>
                <a href="<?php echo esc_url(get_permalink($postRelacionado)); ?>" class="notificacion-enlace">
                <?php endif; ?>

                <?php if (!empty($avatarOptimizado)) : ?>
                    <img class="avatar" src="<?php echo esc_url($avatarOptimizado); ?>" alt="Avatar del emisor">
                <?php endif; ?>

                <div class="DAEFSE">
                    <p class="notificacion-contenido"><?php the_content(); ?></p>
                    <p class="notificacion-fecha"><?php echo esc_html($fechaRelativa); ?></p>
                </div>

                <?php if (!empty($postRelacionado)) : ?>
                </a>
            <?php endif; ?>
        </li>
<?php
        return ob_get_clean();
    }

    /**
     * Renderiza el icono de notificaciones con indicador de no leídas.
     * 
     * @param int $userId ID del usuario (opcional)
     * @return string HTML del icono
     */
    public static function iconoNotificaciones(int $userId = 0): string
    {
        if ($userId === 0) {
            $userId = get_current_user_id();
        }

        $notificacionService = NotificacionService::obtenerInstancia();
        $ultimaNotificacion = $notificacionService->obtenerUltimaNotificacion($userId);

        $hayNoVistas = false;
        if ($ultimaNotificacion && !$ultimaNotificacion['visto']) {
            $hayNoVistas = true;
        }

        $iconColor = $hayNoVistas ? '#d43333' : 'currentColor';

        $htmlIcono = '<div id="icono-notificaciones" class="icono-notificaciones" style="cursor: pointer;">' .
            '<svg viewBox="0 0 24 24" fill="' . esc_attr($iconColor) . '">' .
            '<path class="cls-2" d="m11.75,21.59c-.46,0-.96-.17-1.61-.57C3.5,16.83,0,12.19,0,7.61,0,3.27,3.13,0,7.29,0c1.72,0,3.28.58,4.46,1.62,1.19-1.05,2.75-1.62,4.46-1.62,4.16,0,7.29,3.27,7.29,7.61,0,4.59-3.5,9.22-10.12,13.4-.63.39-1.16.58-1.63.58Zm.11-2.49h0Zm-.22,0h0ZM7.29,2.5c-2.78,0-4.79,2.15-4.79,5.11,0,3.63,3.18,7.64,8.95,11.29.14.08.23.13.30.16.07-.03.17-.08.30-.17,5.76-3.64,8.94-7.65,8.94-11.28,0-2.96-2.01-5.11-4.79-5.11-1.45,0-2.67.61-3.43,1.71l-1.03,1.49-1.02-1.50c-.75-1.1-1.97-1.70-3.43-1.70Z"/>' .
            '</svg>' .
            '</div>';

        return $htmlIcono . '<div class="notificaciones-container" style="display: none;"></div>';
    }
}
