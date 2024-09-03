<?php
function reporte()
{
    wp_enqueue_script('reporte', get_template_directory_uri() . '/js/reporte.js', array('jquery'), '1.0.17', true);
    wp_localize_script('reporte', 'miAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'reporte');

function manejar_reporte_error() {
    if (empty($_POST['mensaje'])) {
        wp_send_json_error(['message' => 'El mensaje no puede estar vacío.']);
    }

    $mensaje = sanitize_text_field($_POST['mensaje']);
    $user = wp_get_current_user();
    $admin_email = get_option('admin_email');
    $subject = 'Reporte de Error de ' . $user->user_login;
    $body = 'Usuario: ' . $user->user_login . ' (' . $user->user_email . ')' . "\r\n\r\n" . 'Mensaje: ' . $mensaje;

    wp_mail($admin_email, $subject, $body);

    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'reportes_errores',
        ['user_id' => $user->ID, 'mensaje' => $mensaje, 'fecha' => current_time('mysql')]
    );

    wp_send_json_success(['message' => 'Mensaje enviado. Gracias por reportar el error.']);
}
add_action('wp_ajax_enviar_reporte_error', 'manejar_reporte_error');

function get_all_error_reports() {
    global $wpdb;
    return $wpdb->get_results(
        "SELECT r.*, u.ID as user_id, u.user_login, u.user_email 
         FROM {$wpdb->prefix}reportes_errores r 
         LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
         ORDER BY r.fecha DESC", ARRAY_A);
}

function handle_delete_error_report() {
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permiso para realizar esta acción.');
    }

    global $wpdb;
    $result = $wpdb->delete(
        $wpdb->prefix . 'reportes_errores', 
        ['id' => intval($_POST['report_id'])], 
        ['%d']
    );

    $result ? wp_send_json_success() : wp_send_json_error();
}
add_action('wp_ajax_delete_error_report', 'handle_delete_error_report');



function reportes() {
    $reports = get_all_error_reports();
    
    if (empty($reports)) {
        return '<p>No hay reporte de errores</p>';
    }
    
    ob_start(); 
    
    ?>
    <table class="error-reports-table">
        <thead>
            <tr>
                <th>Perfil</th>
                <th>Usuario</th>
                <th>Mensaje</th>
                <th>Fecha</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $report): 
                $profile_picture = obtener_url_imagen_perfil_o_defecto($report['user_id']);
            ?>
                <tr class="XXDD">
                    <td><img src="<?php echo esc_url($profile_picture); ?>" alt="<?php echo esc_attr($report['user_login']); ?>" /></td>
                    <td><?php echo esc_html($report['user_login']); ?></td>
                    <td><?php echo esc_html($report['mensaje']); ?></td>
                    <td><?php echo esc_html($report['fecha']); ?></td>
                    <td>
                        <button class="delete-error-report" data-report-id="<?php echo esc_attr($report['id']); ?>">
                            <?php echo $GLOBALS['iconocheck']; ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    
    return ob_get_clean(); 
}

