<?

function eliminarPostPendientes() {
    // Argumentos para obtener posts en estado 'pending' y 'trash'
    $args = array(
        'post_type'      => 'any',  // Para aplicar a todos los tipos de post
        'post_status'    => array('pending', 'trash'), // Estados 'pending' y 'trash'
        'posts_per_page' => -1,     // Obtener todos los posts
    );

    // Obtener los posts con esos estados
    $posts = get_posts($args);

    // Tiempo actual
    $ahora = current_time('timestamp');

    foreach ($posts as $post) {
        // Fecha de la última modificación del post en GMT
        $ultima_modificacion = strtotime($post->post_modified_gmt);
        
        // Calcular la diferencia en días
        $diferencia_dias = ($ahora - $ultima_modificacion) / DAY_IN_SECONDS;

        // Si han pasado más de 7 días, eliminar el post
        if ($diferencia_dias >= 7) {
            wp_delete_post($post->ID, true); // true para eliminar permanentemente
        }
    }
}

// Agregar un intervalo semanal personalizado
function agregar_intervalo_semanal($schedules) {
    $schedules['weekly'] = array(
        'interval' => 7 * 24 * 60 * 60, // 1 semana en segundos
        'display'  => __('Una vez por semana'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'agregar_intervalo_semanal');

// Agregar un cron que se ejecute semanalmente
if (!wp_next_scheduled('eliminacionSemanal')) {
    wp_schedule_event(time(), 'weekly', 'eliminacionSemanal');
}

// Acción que ejecuta la función
add_action('eliminacionSemanal', 'eliminarPostPendientes');
