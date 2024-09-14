<?php

add_action('wp_ajax_enviar_reporte_error', function() {
    if (empty($_POST['mensaje'])) wp_send_json_error(['message' => 'El mensaje no puede estar vacío.']);
    $user = wp_get_current_user();
    $mensaje = sanitize_text_field($_POST['mensaje']);
    wp_mail(get_option('admin_email'), 'Reporte de Error de ' . $user->user_login, 
        "Usuario: {$user->user_login} ({$user->user_email})\r\n\r\nMensaje: $mensaje");
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'reportes_errores', 
        ['user_id' => $user->ID, 'mensaje' => $mensaje, 'fecha' => current_time('mysql')]);
    wp_send_json_success(['message' => 'Mensaje enviado. Gracias por reportar el error.']);
});

function get_all_error_reports() {
    global $wpdb;
    return $wpdb->get_results("SELECT r.*, u.ID as user_id, u.user_login, u.user_email 
        FROM {$wpdb->prefix}reportes_errores r 
        LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
        ORDER BY r.fecha DESC", ARRAY_A);
}

add_action('wp_ajax_delete_error_report', function() {
    if (!current_user_can('manage_options')) wp_die('No tienes permiso para realizar esta acción.');
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'reportes_errores', ['id' => intval($_POST['report_id'])], ['%d']) 
        ? wp_send_json_success() : wp_send_json_error();
});

function reportes() {
    $reports = get_all_error_reports();
    if (empty($reports)) return '<p>No hay reporte de errores</p>';
    ob_start(); 
    ?>
    <table class="error-reports-table">
        <thead><tr><th>Perfil</th><th>Usuario</th><th>Mensaje</th><th>Fecha</th><th>Acción</th></tr></thead>
        <tbody>
            <?php foreach ($reports as $report): ?>
                <tr class="XXDD">
                    <td><img src="<?= esc_url(imagenPerfil($report['user_id'])) ?>" alt="<?= esc_attr($report['user_login']) ?>" /></td>
                    <td><?= esc_html($report['user_login']) ?></td>
                    <td><?= esc_html($report['mensaje']) ?></td>
                    <td><?= esc_html($report['fecha']) ?></td>
                    <td><button class="delete-error-report" data-report-id="<?= esc_attr($report['id']) ?>"><?= $GLOBALS['iconocheck'] ?></button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean(); 
}