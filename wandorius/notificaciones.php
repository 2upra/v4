<?php 

add_action('wp_ajax_ajustar_zona_horaria', 'ajustar_zona_horaria');
add_action('wp_ajax_nopriv_ajustar_zona_horaria', 'ajustar_zona_horaria'); 

function ajustar_zona_horaria() {
    $zona_horaria = isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC';
    setcookie('usuario_zona_horaria', $zona_horaria, time() + 86400, '/');
    wp_die();
}
 
function tiempo_relativo($fecha) {
    $zona_horaria_usuario = isset($_COOKIE['usuario_zona_horaria']) ? $_COOKIE['usuario_zona_horaria'] : 'UTC';
    $fechaNotificacionUTC = new DateTime($fecha, new DateTimeZone('UTC'));
    $fechaNotificacion = $fechaNotificacionUTC->setTimezone(new DateTimeZone($zona_horaria_usuario));
    $ahora = new DateTime('now', new DateTimeZone($zona_horaria_usuario));
    $diferencia = $ahora->getTimestamp() - $fechaNotificacion->getTimestamp();

    if ($diferencia < 60) {
        return 'Justo ahora';
    } elseif ($diferencia < 3600) {
        return 'hace '.round($diferencia / 60).' minutos';
    } elseif ($diferencia < 86400) {
        return 'hace '.round($diferencia / 3600).' horas';
    } elseif ($diferencia < 604800) {
        return 'hace '.round($diferencia / 86400).' días';
    } elseif ($diferencia < 2419200) {
        return 'hace '.round($diferencia / 604800).' semanas';
    } elseif ($diferencia < 29030400) {
        return 'hace '.round($diferencia / 2419200).' meses';
    } else {
        return 'hace '.round($diferencia / 29030400).' años';
    }
}


function hay_notificaciones_no_leidas($usuario_id) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM wp_notificaciones WHERE usuario_id = %d AND leida = 0", 
        $usuario_id
    )) > 0;
}

function manejar_verificar_notificaciones() {
    echo json_encode(['tiene_notificaciones' => hay_notificaciones_no_leidas($_POST['usuario_id'] ?? 0)]);
    wp_die();
}
add_action('wp_ajax_verificar_notificaciones', 'manejar_verificar_notificaciones');

function insertar_notificacion($usuario_id, $texto, $enlace, $actor_id) {
    global $wpdb;

    // Define el intervalo de tiempo para buscar notificaciones duplicadas (en horas)
    $intervalo_horas = 1;

    // Calcula la fecha y hora actuales menos el intervalo definido
    $fecha_limite = gmdate('Y-m-d H:i:s', strtotime("-$intervalo_horas hours"));

    // Busca notificaciones existentes que coincidan con los mismos parámetros y se hayan creado dentro del intervalo definido
    $notificaciones_existentes = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM wp_notificaciones WHERE usuario_id = %d AND texto = %s AND enlace = %s AND actor_id = %d AND fecha > %s",
        $usuario_id,
        $texto,
        $enlace,
        $actor_id,
        $fecha_limite
    ));

    // Si no se encuentran notificaciones duplicadas recientes, inserta la nueva notificación
    if ($notificaciones_existentes == 0) {
        $wpdb->insert('wp_notificaciones', [
            'usuario_id' => $usuario_id,
            'texto' => $texto,
            'enlace' => $enlace,
            'actor_id' => $actor_id, 
            'fecha' => gmdate('Y-m-d H:i:s'),
            'leida' => 0
        ]);
    }
}

function obtener_notificaciones_no_leidas($usuario_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM wp_notificaciones WHERE usuario_id = %d AND leida = 0 ORDER BY fecha DESC", 
        $usuario_id
    ), OBJECT);
}

function marcar_notificaciones_como_leidas($usuario_id) {
    global $wpdb;
    $result = $wpdb->update('wp_notificaciones', ['leida' => 1], ['usuario_id' => $usuario_id, 'leida' => 0]);
    $logMessage = $result === false 
        ? "Error al marcar notificaciones como leídas para el usuario ID: {$usuario_id}" 
        : "Notificaciones marcadas como leídas para el usuario ID: {$usuario_id}, filas afectadas: {$result}";
    error_log($logMessage);
}

function manejar_marcar_como_leidas() {
    marcar_notificaciones_como_leidas($_POST['usuario_id'] ?? 0);
    wp_die();
}
add_action('wp_ajax_marcar_como_leidas', 'manejar_marcar_como_leidas');

function manejar_cargar_notificaciones() {
    echo generar_html_notificaciones($_POST['usuario_id'] ?? 0);
    wp_die();
}
add_action('wp_ajax_cargar_notificaciones', 'manejar_cargar_notificaciones');

function obtener_notificaciones($usuario_id) {
    global $wpdb;
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM wp_notificaciones WHERE usuario_id = %d ORDER BY fecha DESC",
            $usuario_id
        ),
        OBJECT
    );
}



function generar_html_notificaciones($usuario_id) {
    $notificaciones = obtener_notificaciones($usuario_id);
    $html_notificaciones = '';
    foreach ($notificaciones as $notificacion) {
        $clase_leida = $notificacion->leida ? 'notificacion-leida' : 'notificacion-no-leida';
        $texto_notificacion = wp_kses($notificacion->texto, array('a' => array('href' => array())));

        $imagen_perfil_url = obtener_url_imagen_perfil_o_defecto($notificacion->actor_id);
        $perfil_url = "/perfil/" . $notificacion->actor_id; 
        
        $html_notificaciones .= sprintf(
            '<div class="notificacion %s">'.
                '<a href="%s" class="notificacion-imagen">'.
                    '<img src="%s" alt="Imagen de perfil" class="imagen-perfil-notificacion">'.
                '</a>'.
                '<a href="%s" class="notificacion-contenido">'.
                    '<div class="notificacion-texto">%s</div>'.
                    '<div class="notificacion-fecha">%s</div>'.
                '</a>'.
            '</div>',
            $clase_leida,
            esc_url($perfil_url),
            esc_url($imagen_perfil_url),
            esc_url($notificacion->enlace),
            $texto_notificacion,
            tiempo_relativo($notificacion->fecha)
        );
    }
    return $html_notificaciones;
}

function mostrar_notificaciones_shortcode() {
    $usuario_id = get_current_user_id();
    $hay_no_leidas = hay_notificaciones_no_leidas($usuario_id);
    $clase_notificaciones = $hay_no_leidas ? 'tiene-notificaciones' : '';
 
    $html_icono_notificaciones = '<div id="icono-notificaciones" class="icono-notificaciones ' . $clase_notificaciones . '" style="cursor: pointer; width: 17px; height: 17px;">' .
        '<svg viewBox="0 0 24 24" fill="currentColor">' . // Asegúrate de ajustar el viewBox si es necesario
        '<path class="cls-2" d="m11.75,21.59c-.46,0-.96-.17-1.61-.57C3.5,16.83,0,12.19,0,7.61,0,3.27,3.13,0,7.29,0c1.72,0,3.28.58,4.46,1.62,1.19-1.05,2.75-1.62,4.46-1.62,4.16,0,7.29,3.27,7.29,7.61,0,4.59-3.5,9.22-10.12,13.4-.63.39-1.16.58-1.63.58Zm.11-2.49h0Zm-.22,0h0ZM7.29,2.5c-2.78,0-4.79,2.15-4.79,5.11,0,3.63,3.18,7.64,8.95,11.29.14.08.23.13.3.16.07-.03.17-.08.3-.17,5.76-3.64,8.94-7.65,8.94-11.28,0-2.96-2.01-5.11-4.79-5.11-1.45,0-2.67.61-3.43,1.71l-1.03,1.49-1.02-1.5c-.75-1.1-1.97-1.7-3.43-1.7Z"/>' .
        '</svg>' .
        '</div>';

    $html_notificaciones = generar_html_notificaciones($usuario_id);
    $html_completo = $html_icono_notificaciones . '<div class="notificaciones-container" style="display: none;">' . $html_notificaciones . '</div>';

    return $html_completo;
}
add_shortcode('mostrar_notificaciones', 'mostrar_notificaciones_shortcode');


function generar_html_notificaciones_sin_icono($usuario_id) {
    $html_notificaciones = generar_html_notificaciones($usuario_id);
    $html_completo = '<div class="notificaciones-container block">' . $html_notificaciones . '</div>';
    return $html_completo;
}

function mostrar_notificaciones_sin_icono_shortcode() {
    $usuario_id = get_current_user_id();
    $html_notificaciones = generar_html_notificaciones_sin_icono($usuario_id);
    
    return $html_notificaciones;
}
add_shortcode('mostrar_notificaciones_sin_icono', 'mostrar_notificaciones_sin_icono_shortcode');


function notificar_autor_nuevo_comentario($comment_ID, $comment_approved, $commentdata) {
    if (1 === $comment_approved) {
        $post_id = $commentdata['comment_post_ID'];
        $post_author_id = get_post_field('post_author', $post_id);

        $comentario_resumen = wp_trim_words($commentdata['comment_content'], 20, '...');
        $texto = 'Tienes un nuevo comentario en tu post: "' . $comentario_resumen . '"';
        $enlace = get_comment_link($comment_ID);
        insertar_notificacion($post_author_id, $texto, $enlace, $commentdata['user_id']);
    }
}
add_action('comment_post', 'notificar_autor_nuevo_comentario', 10, 3);

function notificar_autor_y_colaborador_nuevo_comentario($comment_ID, $comment_approved, $commentdata) {
    if (1 === $comment_approved) {
        $post_id = $commentdata['comment_post_ID'];
        $post_author_id = get_post_field('post_author', $post_id);
        $artist_id = get_post_meta($post_id, 'artist_id', true);
        $collaborator_id = get_post_meta($post_id, 'collaborator_id', true);

        $comentario_resumen = wp_trim_words($commentdata['comment_content'], 20, '...');
        $texto = 'Tienes un nuevo comentario en tu post: "' . $comentario_resumen . '"';
        $enlace = get_comment_link($comment_ID);

        insertar_notificacion($post_author_id, $texto, $enlace, $commentdata['user_id']);

        if ($artist_id && $artist_id != $post_author_id && $artist_id != $commentdata['user_id']) {
            insertar_notificacion($artist_id, $texto, $enlace, $commentdata['user_id']);
        }

        if ($collaborator_id && $collaborator_id != $post_author_id && $collaborator_id != $artist_id && $collaborator_id != $commentdata['user_id']) {
            insertar_notificacion($collaborator_id, $texto, $enlace, $commentdata['user_id']);
        }
    }
}
add_action('comment_post', 'notificar_autor_y_colaborador_nuevo_comentario', 10, 3);


function send_notification_to_artist_collaborator() {
    $postId = $_POST['postId'];
    $artistId = $_POST['artistId'];
    $collaboratorId = $_POST['collaboratorId'];
    $texto = 'El artista o colaborador ha solicitado tu atención en el post.';
    $enlace = get_permalink($postId);

    // Envía notificación al artista
    if (!empty($artistId)) {
        insertar_notificacion($artistId, $texto, $enlace, get_current_user_id());
    }

    // Envía notificación al colaborador
    if (!empty($collaboratorId)) {
        insertar_notificacion($collaboratorId, $texto, $enlace, get_current_user_id());
    }

    echo 'Notificación enviada.';
    wp_die(); // Esto es necesario para finalizar correctamente la solicitud AJAX
}
add_action('wp_ajax_send_notification_to_artist_collaborator', 'send_notification_to_artist_collaborator');
add_action('wp_ajax_nopriv_send_notification_to_artist_collaborator', 'send_notification_to_artist_collaborator');

function encolar_notificacion_colab_script() {
    wp_register_script('notificacioncolab', get_template_directory_uri() . '/js/notificacioncolab.js', array('jquery'), '1.0.2', true);
    wp_enqueue_script('notificacioncolab');
    $script_data = "var ajaxurl = '" . admin_url('admin-ajax.php') . "';";
    wp_add_inline_script('notificacioncolab', $script_data, 'before');
}


add_action('wp_enqueue_scripts', 'encolar_notificacion_colab_script');




function encolar_scripts_notificaciones() {
    wp_enqueue_script('notificaciones-js', get_template_directory_uri() . '/js/notificaciones.js', array('jquery'), '1.1.13', true);
    wp_localize_script('notificaciones-js', 'datosNotificaciones', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'usuarioID' => get_current_user_id()
    ));
}
add_action('wp_enqueue_scripts', 'encolar_scripts_notificaciones');

function borrar_notificaciones_antiguas() {
    global $wpdb;
    $hace_una_semana = gmdate('Y-m-d H:i:s', strtotime('-1 week'));
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM wp_notificaciones WHERE leida = 1 AND fecha <= %s",
            $hace_una_semana
        )
    );
}

if (!wp_next_scheduled('borrar_notificaciones_antiguas_hook')) {
    wp_schedule_event(time(), 'daily', 'borrar_notificaciones_antiguas_hook');
}

add_action('borrar_notificaciones_antiguas_hook', 'borrar_notificaciones_antiguas');
add_action('wp_ajax_insertar_notificacion_usuario', 'manejar_notificacion_usuario');
add_action('wp_ajax_nopriv_insertar_notificacion_usuario', 'manejar_notificacion_usuario'); 
