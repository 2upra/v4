<?

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

/*

cual es el problema aca y porque puede que este en bucle (lo veo a cada rato)
[16-Dec-2024 15:35:58 UTC] Cron wp_enqueue_notifications ejecutado.
[16-Dec-2024 15:36:02 UTC] [Firebase] Notificación enviada al usuario 49
[16-Dec-2024 15:36:02 UTC] [crearNotificacion] Notificación push enviada con éxito al usuario ID: 49
[16-Dec-2024 15:36:02 UTC] [crearNotificacion] Error: Usuario receptor no válido ID: 254
[16-Dec-2024 15:36:05 UTC] PHP Fatal error:  Uncaught Kreait\Firebase\Exception\Messaging\NotFound: Requested entity was not found. in /var/www/wordpress/wp-content/themes/2upra3v/vendor/kreait/firebase-php/src/Firebase/Exception/Messaging/NotFound.php:60
Stack trace:
#0 /var/www/wordpress/wp-content/themes/2upra3v/vendor/kreait/firebase-php/src/Firebase/Exception/MessagingApiExceptionConverter.php(113): Kreait\Firebase\Exception\Messaging\NotFound->withErrors()
#1 /var/www/wordpress/wp-content/themes/2upra3v/vendor/kreait/firebase-php/src/Firebase/Exception/MessagingApiExceptionConverter.php(121): Kreait\Firebase\Exception\MessagingApiExceptionConverter->convertResponse()
#2 /var/www/wordpress/wp-content/themes/2upra3v/vendor/kreait/firebase-php/src/Firebase/Exception/MessagingApiExceptionConverter.php(44): Kreait\Firebase\Exception\MessagingApiExceptionConverter->convertGuzzleRequestException()
#3 /var/www/wordpress/wp-content/themes/2upra3v/vendor/kreait/firebase-php/src/Firebase/Messaging.php(100): Kreait\Firebase\Exception\MessagingApiExceptionConverter->convertException()
#4 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/EachPromise.php(183): Kreait\Firebase\Messaging->Kreait\Firebase\{closure}()
#5 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(209): GuzzleHttp\Promise\EachPromise->GuzzleHttp\Promise\{closure}()
#6 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(158): GuzzleHttp\Promise\Promise::callHandler()
#7 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/TaskQueue.php(52): GuzzleHttp\Promise\Promise::GuzzleHttp\Promise\{closure}()
#8 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/guzzle/src/Handler/CurlMultiHandler.php(167): GuzzleHttp\Promise\TaskQueue->run()
#9 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/guzzle/src/Handler/CurlMultiHandler.php(206): GuzzleHttp\Handler\CurlMultiHandler->tick()
#10 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(251): GuzzleHttp\Handler\CurlMultiHandler->execute()
#11 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(227): GuzzleHttp\Promise\Promise->invokeWaitFn()
#12 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(272): GuzzleHttp\Promise\Promise->waitIfPending()
#13 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(229): GuzzleHttp\Promise\Promise->invokeWaitList()
#14 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(69): GuzzleHttp\Promise\Promise->waitIfPending()
#15 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/EachPromise.php(109): GuzzleHttp\Promise\Promise->wait()
#16 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(251): GuzzleHttp\Promise\EachPromise->GuzzleHttp\Promise\{closure}()
#17 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(227): GuzzleHttp\Promise\Promise->invokeWaitFn()
#18 /var/www/wordpress/wp-content/themes/2upra3v/vendor/guzzlehttp/promises/src/Promise.php(69): GuzzleHttp\Promise\Promise->waitIfPending()
#19 /var/www/wordpress/wp-content/themes/2upra3v/vendor/kreait/firebase-php/src/Firebase/Messaging.php(106): GuzzleHttp\Promise\Promise->wait()
#20 /var/www/wordpress/wp-content/themes/2upra3v/vendor/kreait/firebase-php/src/Firebase/Messaging.php(55): Kreait\Firebase\Messaging->sendAll()
#21 /var/www/wordpress/wp-content/themes/2upra3v/app/Pendiente por refactorizar/notificaciones.php(116): Kreait\Firebase\Messaging->send()
#22 /var/www/wordpress/wp-content/themes/2upra3v/app/Pendiente por refactorizar/notificaciones.php(68): send_push_notification()
#23 /var/www/wordpress/wp-content/themes/2upra3v/app/Form/Manejar.php(106): crearNotificacion()
#24 /var/www/wordpress/wp-includes/class-wp-hook.php(324): procesar_notificaciones()
#25 /var/www/wordpress/wp-includes/class-wp-hook.php(348): WP_Hook->apply_filters()
#26 /var/www/wordpress/wp-includes/plugin.php(565): WP_Hook->do_action()
#27 /var/www/wordpress/wp-cron.php(191): do_action_ref_array()
#28 {main}
  thrown in /var/www/wordpress/wp-content/themes/2upra3v/vendor/kreait/firebase-php/src/Firebase/Exception/Messaging/NotFound.php on line 60
*/

function crearNotificacion($usuarioReceptor, $contenido, $metaSolicitud = false, $postIdRelacionado = 0, $Titulo = 'Nueva notificación', $url = null, $emisor = null)
{
    // Verifica que el usuario receptor sea válido
    $usuario = get_user_by('ID', $usuarioReceptor);
    if (!$usuario) {
        error_log("[crearNotificacion] Error: Usuario receptor no válido ID: " . $usuarioReceptor);
        return false;
    }

    $contenidoSanitizado = wp_kses($contenido, 'post');
    $metaSolicitud = is_bool($metaSolicitud) ? $metaSolicitud : false;
    $postIdRelacionado = is_numeric($postIdRelacionado) ? intval($postIdRelacionado) : 0;

    // Determinar el emisor de la notificación
    $emisorId = get_current_user_id();
    if ($emisorId === 0 || $emisorId === null) {
        $emisorId = $emisor;
    }
    // Verificar si el emisor es válido
    if ($emisorId === null) {
        error_log("[crearNotificacion] Error: No se pudo determinar el emisor de la notificacion.");
        return false;
    }

    // Crear el post de la notificación
    $nuevoPost = [
        'post_type'   => 'notificaciones',
        'post_title'   => $Titulo,
        'post_content' => $contenidoSanitizado,
        'post_author'  => $usuarioReceptor,
        'post_status'  => 'publish',
        'meta_input'   => [
            'emisor' => $emisorId,
            'solicitud' => $metaSolicitud,
            'post_relacionado' => $postIdRelacionado
        ]
    ];

    $postId = wp_insert_post($nuevoPost);

    // Si hay un error, lo registramos
    if (is_wp_error($postId)) {
        error_log("[crearNotificacion] Error al crear la notificación: " . $postId->get_error_message());
        return false;
    }

    // Si la URL no se proporcionó, usar la página de inicio del sitio
    if ($url === null) {
        $url = home_url(); // Página de inicio
    }

    // Obtener el token de Firebase del usuario receptor
    $firebase_token = get_user_meta($usuarioReceptor, 'firebase_token', true);

    // Verificar si el usuario tiene un token de Firebase
    if (empty($firebase_token)) {
        return $postId;
    }

    // Enviar notificación push
    $titulo = $Titulo;
    $mensaje = $contenidoSanitizado;
    $resultadoPush = send_push_notification($usuarioReceptor, $titulo, $mensaje, $url);
    // Registrar en el log el resultado del envío
    if (is_wp_error($resultadoPush)) {
        error_log("[crearNotificacion] Error al enviar la notificación push: " . $resultadoPush->get_error_message());
        // Manejar el error aquí:
        if ($resultadoPush->get_error_code() === 'no_token' || strpos($resultadoPush->get_error_message(), 'Requested entity was not found') !== false) {
            // Eliminar el token inválido
            delete_user_meta($usuarioReceptor, 'firebase_token');
            error_log("[crearNotificacion] Token de Firebase eliminado para el usuario ID: " . $usuarioReceptor);
        }
        // Podrías agregar un meta al post para indicar que falló el envío
        update_post_meta($postId, 'envio_push_fallido', true);
    } else {
        error_log("[crearNotificacion] Notificación push enviada con éxito al usuario ID: " . $usuarioReceptor);
    }

    // Registrar en el log el resultado del envío
    if (is_wp_error($resultadoPush)) {
        error_log("[crearNotificacion] Error al enviar la notificación push: " . $resultadoPush->get_error_message());
    } else {
        error_log("[crearNotificacion] Notificación push enviada con éxito al usuario ID: " . $usuarioReceptor);
    }

    return $postId;
}
//
function send_push_notification($user_id, $title, $message, $url)
{
    $serviceAccountFile = '/var/www/wordpress/private/upra-b6879-firebase-adminsdk-w9xma-5f138a5b75.json';

    if (!file_exists($serviceAccountFile)) {
        error_log('[Firebase] No se encontró el archivo de credenciales en ' . $serviceAccountFile);
        return new WP_Error('no_service_account', 'No se encontró el archivo de credenciales.', array('status' => 500));
    }

    try {
        $factory = (new Factory)->withServiceAccount($serviceAccountFile);
        $messaging = $factory->createMessaging();
    } catch (\Exception $e) {
        error_log('[Firebase] Error al inicializar Firebase: ' . $e->getMessage());
        return new WP_Error('firebase_init_failed', 'Error al inicializar Firebase.', array('status' => 500));
    }

    $firebase_token = get_user_meta($user_id, 'firebase_token', true);

    if (!$firebase_token) {
        error_log('[Firebase] El usuario ' . $user_id . ' no tiene un token de Firebase.');
        return new WP_Error('no_token', 'El usuario no tiene un token de Firebase.', array('status' => 404));
    }

    $messageData = [
        'token' => $firebase_token,
        'notification' => [
            'title' => $title,
            'body' => $message,
        ],
        'data' => [
            'url' => $url,
            //'userId' => $user_id #aun no es necesario
        ],
    ];

    try {
        $messaging->send($messageData);
        error_log('[Firebase] Notificación enviada al usuario ' . $user_id);
        return 'Notificación enviada con éxito.';
    } catch (MessagingException $e) {
        error_log('[Firebase] Error al enviar la notificación: ' . $e->getMessage());
        return 'Error al enviar la notificación: ' . $e->getMessage();
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

    // Argumentos de la consulta
    $args = array(
        'post_type' => 'notificaciones',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'visto',
                'compare' => 'NOT EXISTS', // No ha sido visto (meta no existe)
            ),
            array(
                'key' => 'visto',
                'value' => '1',
                'compare' => '!=', // No ha sido visto (valor distinto de 1)
            ),
        ),
        'author' => $user_id,
        'orderby' => 'date', // Ordenar por fecha de creación
        'order' => 'DESC',  // La más reciente primero
    );

    // Crear la consulta
    $notificaciones_query = new WP_Query($args);
    $hay_no_vistas = $notificaciones_query->have_posts() ? true : false;

    // Cambiar el color del ícono si hay notificaciones no vistas
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
