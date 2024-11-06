<?

function renderNoti($usuario_id)
{
    $notificaciones = getNoti($usuario_id);
    $html_notificaciones = '';
    $cache_imagenes = []; // Caché para almacenar las URLs de las imágenes de perfil

    foreach ($notificaciones as $notificacion) {
        $clase_leida = $notificacion->leida ? 'notificacion-leida' : 'notificacion-no-leida';
        $texto_notificacion = wp_kses($notificacion->texto, array('a' => array('href' => array())));

        // Verificamos si ya tenemos la imagen en caché
        if (!isset($cache_imagenes[$notificacion->actor_id])) {
            // Si no está en caché, la obtenemos y la almacenamos
            $cache_imagenes[$notificacion->actor_id] = imagenPerfil($notificacion->actor_id);
        }

        $imagen_perfil_url = $cache_imagenes[$notificacion->actor_id];
        $perfil_url = "/perfil/" . $notificacion->actor_id;

        $html_notificaciones .= sprintf(
            '<div class="notificacion %s">' .
                '<a href="%s" class="notificacion-imagen">' .
                '<img src="%s" alt="Imagen de perfil" class="imagen-perfil-notificacion">' .
                '</a>' .
                '<a href="%s" class="notificacion-contenido">' .
                '<div class="notificacion-texto">%s</div>' .
                '<div class="notificacion-fecha">%s</div>' .
                '</a>' .
                '</div>',
            $clase_leida,
            esc_url($perfil_url),
            esc_url($imagen_perfil_url),
            esc_url($notificacion->enlace),
            $texto_notificacion,
            TiempoRelativoNoti($notificacion->fecha)
        );
    }
    return $html_notificaciones;
}

//te muestro el resto del codig para que tengas contexto


function hayNotiNoLeidas($usuario_id)
{
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM wp_notificaciones WHERE usuario_id = %d AND leida = 0",
        $usuario_id
    )) > 0;
}

function verificarNoti()
{
    echo json_encode(['tiene_notificaciones' => hayNotiNoLeidas($_POST['usuario_id'] ?? 0)]);
    wp_die();
}
add_action('wp_ajax_verificar_notificaciones', 'verificarNoti');

function agregarNoti($usuario_id, $texto, $enlace, $actor_id)
{
    global $wpdb;
    $intervalo_horas = 1;
    $fecha_limite = gmdate('Y-m-d H:i:s', strtotime("-$intervalo_horas hours"));
    $notificaciones_existentes = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM wp_notificaciones WHERE usuario_id = %d AND texto = %s AND enlace = %s AND actor_id = %d AND fecha > %s",
        $usuario_id,
        $texto,
        $enlace,
        $actor_id,
        $fecha_limite
    ));
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

function getNotiConPerfiles($usuario_id)
{
    global $wpdb;
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT n.*, u.imagen_perfil_url 
            FROM wp_notificaciones n 
            LEFT JOIN wp_usuarios u ON n.actor_id = u.id 
            WHERE n.usuario_id = %d 
            ORDER BY n.fecha DESC",
            $usuario_id
        ),
        OBJECT
    );
}

function marcarLeidoNoti($usuario_id)
{
    global $wpdb;
    $result = $wpdb->update('wp_notificaciones', ['leida' => 1], ['usuario_id' => $usuario_id, 'leida' => 0]);
    $logMessage = $result === false
        ? "Error al marcar notificaciones como leídas para el usuario ID: {$usuario_id}"
        : "Notificaciones marcadas como leídas para el usuario ID: {$usuario_id}, filas afectadas: {$result}";
    error_log($logMessage);
}

function marcarLeidaNoti()
{
    marcarLeidoNoti($_POST['usuario_id'] ?? 0);
    wp_die();
}
add_action('wp_ajax_marcar_como_leidas', 'marcarLeidaNoti');

function manejarNoti()
{
    echo renderNoti($_POST['usuario_id'] ?? 0);
    wp_die();
}
add_action('wp_ajax_cargar_notificaciones', 'manejarNoti');


function getNoti($usuario_id)
{
    global $wpdb;
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM wp_notificaciones WHERE usuario_id = %d ORDER BY fecha DESC",
            $usuario_id
        ),
        OBJECT
    );
}


function iconoNotificaciones()
{
    $usuario_id = get_current_user_id();
    $hay_no_leidas = hayNotiNoLeidas($usuario_id);
    $clase_notificaciones = $hay_no_leidas ? 'tiene-notificaciones' : '';

    $html_icono_notificaciones = '<div id="icono-notificaciones" class="icono-notificaciones ' . $clase_notificaciones . '" style="cursor: pointer; width: 17px; height: 17px;">' .
        '<svg viewBox="0 0 24 24" fill="currentColor">' . // Asegúrate de ajustar el viewBox si es necesario
        '<path class="cls-2" d="m11.75,21.59c-.46,0-.96-.17-1.61-.57C3.5,16.83,0,12.19,0,7.61,0,3.27,3.13,0,7.29,0c1.72,0,3.28.58,4.46,1.62,1.19-1.05,2.75-1.62,4.46-1.62,4.16,0,7.29,3.27,7.29,7.61,0,4.59-3.5,9.22-10.12,13.4-.63.39-1.16.58-1.63.58Zm.11-2.49h0Zm-.22,0h0ZM7.29,2.5c-2.78,0-4.79,2.15-4.79,5.11,0,3.63,3.18,7.64,8.95,11.29.14.08.23.13.3.16.07-.03.17-.08.3-.17,5.76-3.64,8.94-7.65,8.94-11.28,0-2.96-2.01-5.11-4.79-5.11-1.45,0-2.67.61-3.43,1.71l-1.03,1.49-1.02-1.5c-.75-1.1-1.97-1.7-3.43-1.7Z"/>' .
        '</svg>' .
        '</div>';

    $html_notificaciones = renderNoti($usuario_id);
    $html_completo = $html_icono_notificaciones . '<div class="notificaciones-container" style="display: none;">' . $html_notificaciones . '</div>';

    return $html_completo;
}


function borrarNotiAntiguas()
{
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
add_action('borrar_notificaciones_antiguas_hook', 'borrarNotiAntiguas');



