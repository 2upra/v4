<?php

namespace App\View\Helpers;

use WP_Query;

//a pesar que la ultina notificacion esta marcada como visto, muestra de color rojo de igual manera, no se que esa fallando, el post type es valido,
function iconoNotificaciones()
{
    $user_id = get_current_user_id(); // Obtener el ID del usuario actual

    // Argumentos de la consulta para obtener LA ÚLTIMA notificación
    $args_latest = array(
        'post_type' => 'notificaciones',
        'posts_per_page' => 1,
        'author' => $user_id,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $latest_notification_query = new WP_Query($args_latest);
    $hay_no_vistas = false; // Inicializamos a false

    if ($latest_notification_query->have_posts()) {
        while ($latest_notification_query->have_posts()) {
            $latest_notification_query->the_post();
            $visto = get_post_meta(get_the_ID(), 'visto', true);
            // Verificar si la última notificación NO está marcada como vista
            if ($visto != '1') {
                $hay_no_vistas = true;
            }
        }
        wp_reset_postdata(); // Importante restablecer postdata
    }

    // Cambiar el color del ícono si la última notificación no está vista
    $icon_color = $hay_no_vistas ? '#d43333' : 'currentColor';

    // HTML del ícono de notificaciones
    $html_icono_notificaciones = '<div id="icono-notificaciones" class="icono-notificaciones" style="cursor: pointer;">' .
        '<svg viewBox="0 0 24 24" fill="' . $icon_color . '">' .
        '<path class="cls-2" d="m11.75,21.59c-.46,0-.96-.17-1.61-.57C3.5,16.83,0,12.19,0,7.61,0,3.27,3.13,0,7.29,0c1.72,0,3.28.58,4.46,1.62,1.19-1.05,2.75-1.62,4.46-1.62,4.16,0,7.29,3.27,7.29,7.61,0,4.59-3.5,9.22-10.12,13.4-.63.39-1.16.58-1.63.58Zm.11-2.49h0Zm-.22,0h0ZM7.29,2.5c-2.78,0-4.79,2.15-4.79,5.11,0,3.63,3.18,7.64,8.95,11.29.14.08.23.13.3.16.07-.03.17-.08.3-.17,5.76-3.64,8.94-7.65,8.94-11.28,0-2.96-2.01-5.11-4.79-5.11-1.45,0-2.67.61-3.43,1.71l-1.03,1.49-1.02-1.5c-.75-1.1-1.97-1.7-3.43-1.7Z"/>' .
        '</svg>' .
        '</div>';

    // HTML de las notificaciones (si es necesario)
    $html_notificaciones = ''; // Aquí puedes añadir el HTML de las notificaciones si lo necesitas

    // Combinar el ícono de notificaciones con el contenedor de notificaciones
    $html_completo = $html_icono_notificaciones . '<div class="notificaciones-container" style="display: none;">' . $html_notificaciones . '</div>';

    return $html_completo;
}
