<?

function crearNotificacion($usuarioReceptor, $contenido, $metaSolicitud = false, $postIdRelacionado = 0)
{
    // Verifica que el usuario receptor sea válido
    $usuario = get_user_by('ID', $usuarioReceptor);
    if (!$usuario) {
        error_log("Error: Usuario receptor no válido ID: " . $usuarioReceptor);
        return false;
    }

    // Sanitiza el contenido de la notificación
    $contenidoSanitizado = wp_kses($contenido, 'post');
    $metaSolicitud = is_bool($metaSolicitud) ? $metaSolicitud : false;
    $postIdRelacionado = is_numeric($postIdRelacionado) ? intval($postIdRelacionado) : 0;

    // Crear el post de la notificación
    $nuevoPost = [
        'post_type'   => 'notificaciones',
        'post_title'   => 'Nueva notificación',
        'post_content' => $contenidoSanitizado,
        'post_author'  => $usuarioReceptor,
        'post_status'  => 'publish',
        'meta_input'   => [
            'emisor' => get_current_user_id(),
            'solicitud' => $metaSolicitud,
            'post_relacionado' => $postIdRelacionado
        ]
    ];

    $postId = wp_insert_post($nuevoPost);

    // Si hay un error, lo registramos
    if (is_wp_error($postId)) {
        error_log("Error al crear la notificación: " . $postId->get_error_message());
        return false;
    }

    return $postId;
}

function listarNotificaciones($pagina = 1)
{
    $usuarioReceptor = get_current_user_id();
    $notificacionesPorPagina = 12;
    $offset = ($pagina - 1) * $notificacionesPorPagina;
    $args = [
        'post_type'      => 'notificaciones',
        'post_status'    => 'publish',
        'posts_per_page' => $notificacionesPorPagina,
        'offset'         => $offset,
        'author'         => $usuarioReceptor,
    ];

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        echo '<ul class="notificaciones-lista modal" id="notificacionesModal">';
        while ($query->have_posts()) {
            $query->the_post();
            $emisor = get_post_meta(get_the_ID(), 'emisor', true);
            $solicitud = get_post_meta(get_the_ID(), 'solicitud', true);
            $postRelacionado = get_post_meta(get_the_ID(), 'post_relacionado', true);
            $fechaPublicacion = get_the_date('Y-m-d H:i:s');
            $fechaRelativa = tiempoRelativo($fechaPublicacion); 
            if ($emisor) {
                $avatar_optimizado = imagenPerfil($emisor);
            }
            ?>
            <li class="notificacion-item" data-notificacion-id="<? echo get_the_ID(); ?>">
                <? if (!empty($postRelacionado)) : ?>
                    <a href="<? echo get_permalink($postRelacionado); ?>" class="notificacion-enlace">
                <? endif; ?>
                    <? if (!empty($avatar_optimizado)) : ?>
                        <img class="avatar" src="<? echo esc_url($avatar_optimizado); ?>" alt="Avatar del emisor">
                    <? endif; ?>
                    <div class="DAEFSE">
                        <p class="notificacion-contenido"><? the_content(); ?></p>
                        <p class="notificacion-fecha"><? echo $fechaRelativa; ?></p> 
                    </div>
                <? if (!empty($postRelacionado)) : ?>
                    </a>
                <? endif; ?>
            </li>
            <?
        }
        echo '</ul>';
    } else {
        echo '<p>No hay notificaciones disponibles.</p>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}


add_action('wp_ajax_marcar_notificacion_vista', 'marcarNotificacionVista');
function marcarNotificacionVista() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No tienes permiso para realizar esta acción.'], 403);
    }

    $notificacionId = isset($_POST['notificacionId']) ? intval($_POST['notificacionId']) : 0;
    
    if ($notificacionId <= 0 || !get_post($notificacionId)) {
        wp_send_json_error(['message' => 'El ID de la notificación no es válido.'], 400);
    }

    $actualizado = update_post_meta($notificacionId, 'visto', 1);
    if ($actualizado === false) {
        wp_send_json_error(['message' => 'No se pudo actualizar la meta de la notificación.'], 500);
    }
    wp_send_json_success(['message' => 'Notificación marcada como vista.', 'notificacionId' => $notificacionId]);
}


function ajaxCargarNotificaciones()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('No tienes permiso para realizar esta acción.');
        wp_die();
    }
    $usuarioReceptor = get_current_user_id();
    $pagina = isset($_POST['pagina']) ? intval($_POST['pagina']) : 1;
    $html = listarNotificaciones($usuarioReceptor, $pagina);
    wp_send_json_success($html);
    wp_die();
}
add_action('wp_ajax_cargar_notificaciones', 'ajaxCargarNotificaciones');

function iconoNotificaciones()
{

    $html_icono_notificaciones = '<div id="icono-notificaciones" class="icono-notificaciones style="cursor: pointer; width: 17px; height: 17px;">' .
        '<svg viewBox="0 0 24 24" fill="currentColor">' .
        '<path class="cls-2" d="m11.75,21.59c-.46,0-.96-.17-1.61-.57C3.5,16.83,0,12.19,0,7.61,0,3.27,3.13,0,7.29,0c1.72,0,3.28.58,4.46,1.62,1.19-1.05,2.75-1.62,4.46-1.62,4.16,0,7.29,3.27,7.29,7.61,0,4.59-3.5,9.22-10.12,13.4-.63.39-1.16.58-1.63.58Zm.11-2.49h0Zm-.22,0h0ZM7.29,2.5c-2.78,0-4.79,2.15-4.79,5.11,0,3.63,3.18,7.64,8.95,11.29.14.08.23.13.3.16.07-.03.17-.08.3-.17,5.76-3.64,8.94-7.65,8.94-11.28,0-2.96-2.01-5.11-4.79-5.11-1.45,0-2.67.61-3.43,1.71l-1.03,1.49-1.02-1.5c-.75-1.1-1.97-1.7-3.43-1.7Z"/>' .
        '</svg>' .
        '</div>';

    $html_notificaciones = '';
    $html_completo = $html_icono_notificaciones . '<div class="notificaciones-container" style="display: none;">' . $html_notificaciones . '</div>';

    return $html_completo;
}
