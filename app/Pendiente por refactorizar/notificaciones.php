<?

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

function procesar_notificaciones()
{
    // Obtener las notificaciones pendientes
    $notificaciones_pendientes = get_option('notificaciones_pendientes', []);
    if (empty($notificaciones_pendientes)) {
        error_log('No hay notificaciones pendientes.');
        return;
    }

    // Procesar un lote de 5 notificaciones
    $lote = array_splice($notificaciones_pendientes, 0, 5);

    foreach ($lote as $indice => $notificacion) {
        $url = $notificacion['url'] ?? '';
        $autor_id = $notificacion['autor_id'];

        if ($autor_id == 10000) {
            // Enviar a todos los usuarios
            $usuarios = get_users();
            foreach ($usuarios as $usuario) {
                // **Añadido:** Verificar si el usuario actual es el mismo que el autor
                if ($usuario->ID != $autor_id) {
                    $resultado = crearNotificacion(
                        $usuario->ID,
                        $notificacion['mensaje'],
                        false,
                        $notificacion['post_id'],
                        $notificacion['titulo'],
                        $url,
                        $autor_id
                    );

                    if (is_wp_error($resultado)) {
                        // Manejo del error según el código proporcionado
                        if ($resultado->get_error_code() === 'not_found' || $resultado->get_error_code() === 'no_token') {
                            error_log("No se pudo enviar la notificación al usuario " . $usuario->ID . " (token no encontrado).");
                        } elseif ($resultado->get_error_code() === 'usuario_invalido') {
                            error_log("No se pudo enviar la notificación al usuario " . $usuario->ID . " (usuario inválido).");
                        } else {
                            error_log("Error al enviar a usuario " . $usuario->ID . ": " . $resultado->get_error_message());
                        }
                    }
                } else {
                    error_log("Se omitió la notificación al usuario " . $usuario->ID . " porque es el mismo que el autor.");
                }
            }
        } else {
            // Enviar a un seguidor específico
            // **Añadido:** Verificar si el seguidor es el mismo que el autor
            if ($notificacion['seguidor_id'] != $autor_id) {
                $resultado = crearNotificacion(
                    $notificacion['seguidor_id'],
                    $notificacion['mensaje'],
                    false,
                    $notificacion['post_id'],
                    $notificacion['titulo'],
                    $url,
                    $autor_id
                );

                if (is_wp_error($resultado)) {
                    // Manejo del error según el código proporcionado
                    if ($resultado->get_error_code() === 'not_found' || $resultado->get_error_code() === 'no_token') {
                        error_log("No se pudo enviar la notificación al seguidor " . $notificacion['seguidor_id'] . " (token no encontrado).");
                    } elseif ($resultado->get_error_code() === 'usuario_invalido') {
                        error_log("No se pudo enviar la notificación al seguidor " . $notificacion['seguidor_id'] . " (usuario inválido).");
                    } else {
                        error_log("Error al enviar a seguidor " . $notificacion['seguidor_id'] . ": " . $resultado->get_error_message());
                    }
                }
            } else {
                error_log("Se omitió la notificación al seguidor " . $notificacion['seguidor_id'] . " porque es el mismo que el autor.");
            }
        }
    }

    // Reindexar y actualizar la lista de notificaciones pendientes
    update_option('notificaciones_pendientes', $notificaciones_pendientes);

    if (empty($notificaciones_pendientes)) {
        error_log('No quedan notificaciones pendientes. Desactivando el cron.');
        wp_clear_scheduled_hook('wp_enqueue_notifications');
    }
}
function crearNotificacion($usuarioReceptor, $contenido, $metaSolicitud = false, $postIdRelacionado = 0, $titulo = 'Nueva notificación', $url = null, $emisor = null)
{
    $usuario = get_user_by('ID', $usuarioReceptor);
    if (!$usuario) {
        error_log("[crearNotificacion] Error: Usuario receptor no válido ID: " . $usuarioReceptor);
        return new WP_Error('usuario_invalido', "Usuario receptor no válido ID: " . $usuarioReceptor);
    }

    $postId = wp_insert_post([
        'post_type'   => 'notificaciones',
        'post_title'   => $titulo,
        'post_content' => wp_kses($contenido, 'post'),
        'post_author'  => $usuarioReceptor,
        'post_status'  => 'publish',
        'meta_input'   => [
            'emisor' => $emisor ?? get_current_user_id(),
            'solicitud' => $metaSolicitud,
            'post_relacionado' => intval($postIdRelacionado),
        ]
    ]);

    if (is_wp_error($postId)) {
        error_log("[crearNotificacion] Error al crear la notificación: " . $postId->get_error_message());
        return $postId;
    }

    $url = $url ?? get_permalink($postId);

    $firebase_token = get_user_meta($usuarioReceptor, 'firebase_token', true);
    if (empty($firebase_token)) {
        return $postId;
    }

    $resultadoPush = send_push_notification($usuarioReceptor, $titulo, $contenido, $url);
    if (is_wp_error($resultadoPush)) {
        if (in_array($resultadoPush->get_error_code(), ['not_found', 'no_token'])) {
            delete_user_meta($usuarioReceptor, 'firebase_token');
            error_log("[crearNotificacion] Token de Firebase eliminado para el usuario ID: " . $usuarioReceptor);
        }
        return $resultadoPush;
    }

    error_log("[crearNotificacion] Notificación push enviada con éxito al usuario ID: " . $usuarioReceptor);
    return $postId;
}
//
function send_push_notification($user_id, $title, $message, $url)
{
    // Ruta al archivo de credenciales del servicio Firebase
    $serviceAccountFile = '/var/www/firebase_keys/upra-b6879-firebase-adminsdk-w9xma-5f138a5b75.json';

    // Verificar si el archivo de credenciales existe
    if (!file_exists($serviceAccountFile)) {
        error_log('[Firebase] No se encontró el archivo de credenciales en ' . $serviceAccountFile);
        return new WP_Error('no_service_account', 'No se encontró el archivo de credenciales.', array('status' => 500));
    }

    try {
        // Inicializar el cliente de Firebase
        $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($serviceAccountFile);
        $messaging = $factory->createMessaging();
    } catch (\Exception $e) {
        // Manejar errores durante la inicialización de Firebase
        error_log('[Firebase] Error al inicializar Firebase: ' . $e->getMessage());
        return new WP_Error('firebase_init_failed', 'Error al inicializar Firebase.', array('status' => 500));
    }

    // Obtener el token de Firebase del usuario
    $firebase_token = get_user_meta($user_id, 'firebase_token', true);

    // Verificar si el usuario tiene un token
    if (empty($firebase_token)) {
        error_log('[Firebase] El usuario ' . $user_id . ' no tiene un token de Firebase.');
        return new WP_Error('no_token', 'El usuario no tiene un token de Firebase.', array('status' => 404));
    }

    // Construir el mensaje de notificación
    $messageData = [
        'token' => $firebase_token,
        'notification' => [
            'title' => $title,
            'body' => $message,
        ],
        'data' => [
            'url' => $url,
        ],
    ];

    try {
        // Enviar la notificación push
        $response = $messaging->send($messageData);
        error_log('[Firebase] Notificación enviada al usuario ' . $user_id);
        return 'Notificación enviada con éxito.';
    } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
        // Manejar el caso donde el token no es válido o no se encuentra
        error_log('[Firebase] Error al enviar la notificación (NotFound): ' . $e->getMessage());
        return new WP_Error('not_found', 'Requested entity was not found.', array('status' => 404));
    } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
        // Manejar el caso donde el mensaje es inválido
        error_log('[Firebase] Error al enviar la notificación (InvalidMessage): ' . $e->getMessage());
        return new WP_Error('invalid_message', 'El mensaje enviado es inválido.', array('status' => 400));
    } catch (\Kreait\Firebase\Exception\Messaging\MessagingException $e) {
        // Manejar otros errores relacionados con el envío de mensajes
        error_log('[Firebase] Error al enviar la notificación: ' . $e->getMessage());
        return new WP_Error('messaging_error', 'Error al enviar la notificación.', array('status' => 500));
    } catch (\Exception $e) {
        // Manejar cualquier otro error
        error_log('[Firebase] Error desconocido al enviar la notificación: ' . $e->getMessage());
        return new WP_Error('unknown_error', 'Ocurrió un error desconocido.', array('status' => 500));
    }
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
        echo '<ul>';
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
        echo '<p class="sinnotifi">No hay notificaciones disponibles.</p>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}

add_action('wp_ajax_marcar_notificacion_vista', 'marcarNotificacionVista');

function marcarNotificacionVista()
{
    if (!is_user_logged_in()) {
        error_log('Acceso denegado: Usuario no autenticado.');
        wp_send_json_error(['message' => 'No tienes permiso para realizar esta acción.'], 403);
    }

    $notificacionId = isset($_POST['notificacionId']) ? intval($_POST['notificacionId']) : 0;

    if ($notificacionId <= 0 || !get_post($notificacionId)) {
        error_log("ID no válido o inexistente: $notificacionId");
        wp_send_json_error(['message' => 'El ID de la notificación no es válido.'], 400);
    }

    $currentUserId = get_current_user_id();
    $postAuthorId = get_post_field('post_author', $notificacionId);

    if ($postAuthorId && $postAuthorId != $currentUserId) {
        error_log("Permiso denegado: Usuario actual ($currentUserId) no es el autor ($postAuthorId) del post ID $notificacionId.");
        wp_send_json_error(['message' => 'No tienes permiso para modificar esta notificación.'], 403);
    }

    $metaActual = get_post_meta($notificacionId, 'visto', true);
    if ($metaActual === '1') {
        error_log("La meta 'visto' ya está configurada en '1' para el ID: $notificacionId.");
        wp_send_json_success(['message' => 'La notificación ya estaba marcada como vista.', 'notificacionId' => $notificacionId]);
    }

    $actualizado = update_post_meta($notificacionId, 'visto', 1);
    if ($actualizado === false) {
        global $wpdb;
        $wpdb_error = $wpdb->last_error ? $wpdb->last_error : 'No hay errores en la base de datos.';
        error_log("Fallo al actualizar la meta 'visto' para el ID: $notificacionId. Error de base de datos: $wpdb_error.");
        wp_send_json_error(['message' => 'No se pudo actualizar la meta de la notificación.'], 500);
    }

    error_log("Meta 'visto' actualizada correctamente para el ID: $notificacionId por el usuario: $currentUserId.");
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

// AJAX handler para verificar notificaciones no vistas
add_action('wp_ajax_verificar_notificaciones', 'verificar_notificaciones');

function verificar_notificaciones()
{
    $user_id = get_current_user_id();
    $timeout = 30; // Mantener la conexión abierta hasta 30 segundos
    $start_time = time();

    // Bucle para mantener la conexión abierta hasta que haya nuevas notificaciones o se agote el tiempo
    while (time() - $start_time < $timeout) {
        // Verifica si hay notificaciones no leídas
        $args = array(
            'post_type' => 'notificaciones',
            'posts_per_page' => 1, // Solo necesitamos la última notificación
            'meta_query' => array(
                array(
                    'key' => 'visto',
                    'value' => '1',
                    'compare' => '!=', // No ha sido vista
                ),
            ),
            'author' => $user_id, // Notificaciones del usuario actual
        );

        $notificaciones_query = new WP_Query($args);
        $hay_no_vistas = $notificaciones_query->have_posts() ? true : false;

        if ($hay_no_vistas) {
            wp_send_json(array('hay_no_vistas' => true));
        }

        // Esperar un segundo antes de volver a verificar
        sleep(1);
    }

    // Si después del timeout no hay notificaciones nuevas, enviar respuesta vacía
    wp_send_json(array('hay_no_vistas' => false));
}
