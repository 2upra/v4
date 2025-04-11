<?php

# Guarda la vista de un post y actualiza las vistas totales.
function guardarVista() {

    if (!isset($_POST['id_post']) || !is_numeric($_POST['id_post'])) {
        wp_send_json_error('ID de post inválido.');
        wp_die();
    }

    $idPost = intval($_POST['id_post']);
    $idUsuario = get_current_user_id();

    if ($idUsuario) {
        $vistasUsuario = get_user_meta($idUsuario, 'vistas_posts', true) ?: [];
        $vistasTotalesUsuario = get_user_meta($idUsuario, 'vistas_totales_usuario', true) ?: 0;
        $fechaActual = time();

        if (isset($vistasUsuario[$idPost])) {
            $vistasUsuario[$idPost]['count']++;
            $vistasUsuario[$idPost]['last_view'] = $fechaActual;
        } else {
            $vistasUsuario[$idPost] = [
                'count' => 1,
                'last_view' => $fechaActual,
            ];
        }

        $vistasTotalesUsuario++;
        update_user_meta($idUsuario, 'vistas_posts', $vistasUsuario);
        update_user_meta($idUsuario, 'vistas_totales_usuario', $vistasTotalesUsuario);


        if (function_exists('reiniciarFeed') && $vistasTotalesUsuario % 6 === 0) {
            reiniciarFeed($idUsuario);
        }
    }

    $vistaTotales = get_post_meta($idPost, 'vistas_totales', true) ?: 0;
    $vistaTotales++;
    update_post_meta($idPost, 'vistas_totales', $vistaTotales);
    $respuesta = ['vistas_totales' => $vistaTotales];

    if ($idUsuario && isset($vistasUsuario[$idPost])) {
        $respuesta['vistas_usuario'] = $vistasUsuario[$idPost]['count'];
    }

    wp_send_json_success($respuesta);
    wp_die();
}

// Funcion obtenerVistasPosts() movida desde app/Utils/AnalyticsUtils.php
/**
 * Obtiene el historial de vistas de posts para un usuario específico.
 *
 * @param int $userId ID del usuario.
 * @return array Array asociativo con ID de post como clave y datos de vista como valor, o array vacío si no hay vistas.
 */
function obtenerVistasPosts($userId)
{
    $vistas_posts = get_user_meta($userId, 'vistas_posts', true);

    if (empty($vistas_posts) || !is_array($vistas_posts)) {
        return [];
    }

    return $vistas_posts;
}

// Funcion limpiarVistasAntiguas() movida desde app/Utils/AnalyticsUtils.php
/**
 * Filtra un array de vistas, eliminando aquellas cuya última vista ('last_view')
 * es más antigua que un número específico de días.
 *
 * @param array $vistas Array asociativo de vistas (postId => ['count' => int, 'last_view' => timestamp]).
 * @param int $dias Número de días para el límite de antigüedad.
 * @return array El array de vistas filtrado.
 */
function limpiarVistasAntiguas($vistas, $dias)
{
    if (empty($vistas) || !is_array($vistas)) {
        return [];
    }

    $fechaLimite = time() - (absint($dias) * 86400); // 86400 segundos en un día

    foreach ($vistas as $postId => $infoVista) {
        // Asegurarse de que 'last_view' existe y es numérico
        if (!isset($infoVista['last_view']) || !is_numeric($infoVista['last_view'])) {
             // Opcional: manejar o registrar posts con datos de vista inválidos
             unset($vistas[$postId]);
             continue;
        }

        if ($infoVista['last_view'] < $fechaLimite) {
            unset($vistas[$postId]);
        }
    }

    return $vistas;
}

// Hooks AJAX para guardarVista() movidos desde app/Utils/AnalyticsUtils.php
add_action('wp_ajax_guardar_vistas', 'guardarVista');        // Para usuarios logueados
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista'); // Para usuarios no logueados

// Refactor(Exec): Moved function vistasDatos from app/Content/Logic/datosParaCalculo.php
function vistasDatos($userId) {
    $tiempoInicio = microtime(true);
    $vistas = get_user_meta($userId, 'vistas_posts', true);
    //rendimientolog("[vistasDatos] Tiempo para obtener 'vistas': " . (microtime(true) - $tiempoInicio) . " segundos");
    return $vistas;
}

// Refactor(Exec): Moved function contarPostsFiltrados() and its hooks from app/Content/Logic/contador.php
/**
 * Cuenta los posts filtrados según los criterios proporcionados vía AJAX.
 * Requiere que el usuario esté logueado.
 * Envía una respuesta JSON con el total de posts o un error.
 */
function contarPostsFiltrados() {
    // Verificar si el usuario tiene permisos para realizar la acción
    // Nota: Se mantiene la verificación de usuario logueado, aunque el hook nopriv también existe.
    // Considerar si el acceso nopriv es realmente necesario o si se debe restringir.
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Acceso no autorizado.']);
        return;
    }

    $current_user_id = get_current_user_id();

    // Obtener el post type desde la petición Ajax
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'social_post';

    $query_args = [
        'post_type'      => $post_type, // Usar el post type recibido
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ];

    // Obtener parámetros enviados por AJAX
    $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $filters = isset($_POST['filters']) ? $_POST['filters'] : []; // Nota: $filters no se usa actualmente en esta función.

    // Aplicar filtros específicos del usuario
    // Asegurarse de que la función aplicarFiltrosUsuario esté disponible en este contexto o moverla/incluirla.
    // Si aplicarFiltrosUsuario no está definida globalmente, esto causará un error fatal.
    // Asumiendo que está definida globalmente o en un archivo incluido.
    if (function_exists('aplicarFiltrosUsuario')) {
        $query_args = aplicarFiltrosUsuario($query_args, $current_user_id);
    } else {
        // Opcional: Registrar un error o advertencia si la función no existe.
        error_log('Advertencia: La función aplicarFiltrosUsuario no está definida en el contexto de contarPostsFiltrados.');
    }


    // Si hay una búsqueda activa, modificar los argumentos de la query
    // Asegurarse de que la función prefiltrarIdentifier esté disponible en este contexto o moverla/incluirla.
    // Si prefiltrarIdentifier no está definida globalmente, esto causará un error fatal.
    // Asumiendo que está definida globalmente o en un archivo incluido.
    if (!empty($search_query)) {
         if (function_exists('prefiltrarIdentifier')) {
            $query_args = prefiltrarIdentifier($search_query, $query_args);
         } else {
             // Opcional: Registrar un error o advertencia si la función no existe.
             error_log('Advertencia: La función prefiltrarIdentifier no está definida en el contexto de contarPostsFiltrados.');
         }
    }

    // Ejecutar la consulta para contar los posts
    $query = new WP_Query($query_args);
    $total_posts = $query->found_posts;

    // Enviar la respuesta en formato JSON
    wp_send_json_success(['total' => $total_posts]);
    // wp_die() es llamado implícitamente por wp_send_json_success/error.
}

// Registrar las acciones AJAX para contarPostsFiltrados
// Movido desde app/Content/Logic/contador.php
add_action('wp_ajax_contarPostsFiltrados', 'contarPostsFiltrados');
// Nota: El hook nopriv permite a usuarios no logueados llamar a esta función,
// pero la función misma verifica is_user_logged_in() y devuelve error.
// Esto podría ser intencional para dar un mensaje de error específico,
// o podría ser un remanente que debería eliminarse si la función es solo para logueados.
add_action('wp_ajax_nopriv_contarPostsFiltrados', 'contarPostsFiltrados');

// Refactor(Org): log_user_agent_callback() y su ruta REST movidos desde app/Authentication/Iniciar.php
// Función callback para manejar la petición de log de User-Agent
function log_user_agent_callback(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $userAgent = isset($params['userAgent']) ? sanitize_text_field($params['userAgent']) : '';
    $type = isset($params['type']) ? sanitize_text_field($params['type']) : '';

    // Registra la información en el archivo de registro de errores de WordPress
    error_log("UserAgent detectado ({$type}): " . $userAgent);

    // También puedes guardar la información en una base de datos personalizada o enviarla por correo electrónico si lo prefieres

    return new WP_REST_Response(array('message' => 'UserAgent registrado correctamente'), 200);
}

// Registra la ruta de la API REST para log de User-Agent
add_action('rest_api_init', function () {
    register_rest_route('myplugin/v1', '/log-user-agent', array(
        'methods' => 'POST',
        'callback' => 'log_user_agent_callback',
        'permission_callback' => '__return_true', // Permite que cualquiera pueda acceder (ajusta según tus necesidades de seguridad)
    ));
});
