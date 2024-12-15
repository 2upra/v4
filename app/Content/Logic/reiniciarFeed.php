<? 

/*
[12-Dec-2024 08:25:19 UTC] Caché guardada exitosamente. Nombre de la caché: feed_personalizado_user_1_.cache
[12-Dec-2024 08:25:19 UTC] [borrarCache] Archivo de caché no encontrado: /var/www/wordpress/wp-content/cache/feed/feed_datos_1.cache
*/

function reiniciarFeed($current_user_id)
{
    $tipoUsuario = get_user_meta($current_user_id, 'tipoUsuario', true);
    //error_log("TipoUsuario inicial={$tipoUsuario} reiniciarFeed");
    global $wpdb;
    $is_admin = current_user_can('administrator');
    guardarLog("Iniciando reinicio de feed para usuario ID: $current_user_id");
    $cache_key = ($current_user_id == 44)
        ? "feed_personalizado_user_44_"
        : "feed_personalizado_user_{$current_user_id}_";

    $cache_time = $is_admin ? 7200 : 43200; // 2 horas para admin, 12 horas para usuarios

    // Obtener todos los archivos de caché relacionados con el usuario actual
    $cache_dir = WP_CONTENT_DIR . '/cache/feed/';
    $cache_pattern = ($current_user_id == 44)
        ? "feed_personalizado_anonymous_*"
        : "feed_personalizado_user_{$current_user_id}_*";
    $transients_eliminados = 0;

    if (file_exists($cache_dir)) {
        $files = glob($cache_dir . $cache_pattern . '.cache');

        if (empty($files)) {
            guardarLog("No se encontraron cachés para reiniciar del usuario ID: $current_user_id");
        } else {
            foreach ($files as $file) {
                if (unlink($file)) {
                    $transients_eliminados++;
                    guardarLog("Caché eliminada: {$file} para usuario ID: $current_user_id");

                    guardarLog("Usuario ID: $current_user_id REcalculando nuevo feed para primera página (sin caché)");
                    //error_log("TipoUsuario inicial={$tipoUsuario} enviado a calcularFeedPersonalizado");
                    $posts_personalizados = calcularFeedPersonalizado($current_user_id, '', '', $tipoUsuario);

                    if (!$posts_personalizados) {
                        guardarLog("Error: Fallo al calcular feed personalizado para usuario ID: $current_user_id");
                        return ['post_ids' => [], 'post_not_in' => []];
                    }

                    // Guardar en caché y respaldo
                    $cache_content = ['posts' => $posts_personalizados, 'timestamp' => time()];
                    guardarCache($cache_key, $cache_content, $cache_time);
                }
            }
        }
    }

    // Eliminar los respaldos de opciones relacionados
    /*
    $option_pattern = ($current_user_id == 44)
        ? 'feed_personalizado_anonymous_%'
        : 'feed_personalizado_user_' . $current_user_id . '_%';

    $query = $wpdb->get_col($wpdb->prepare(
        "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
        $option_pattern . '_backup'
    ));

    foreach ($query as $option_name) {
        if (delete_option($option_name)) {
            guardarLog("Backup eliminado: {$option_name} para usuario ID: $current_user_id");
        }
    }
    */
    //borra la cache de calculo de posts
    borrarCache('feed_datos_' . $current_user_id);
    guardarLog("Caché específica eliminada: feed_datos_$current_user_id");

    guardarLog("Reinicio de feed completado para usuario ID: $current_user_id - Total de cachés eliminadas: $transients_eliminados");

    return $transients_eliminados;
}