<?

function definir_acciones_usuario($usuarios_acciones, $actualizar_si_existe = false)
{
    foreach ($usuarios_acciones as $user_id => $cantidad_acciones) {
        if (get_user_meta($user_id, 'acciones', true) && $actualizar_si_existe) {
            update_user_meta($user_id, 'acciones', $cantidad_acciones);
        } else {
            add_user_meta($user_id, 'acciones', $cantidad_acciones, true);
        }
    }
}
$usuarios_acciones = [
    1 => 420000,
    40 => 4000,
    41 => 12000,
    45 => 9000, //HORACIO
    49 => 5000,
    51 => 6500
];


function obtenerHistorialAccionesUsuario()
{
    global $wpdb;
    $tablaHistorial = $wpdb->prefix . 'historial_acciones';
    $user_id = get_current_user_id();
    $resultados = $wpdb->get_results($wpdb->prepare(
        "SELECT fecha, acciones FROM $tablaHistorial WHERE user_id = %d ORDER BY fecha ASC",
        $user_id
    ));

    return $resultados;
}

function registrarHistorialAcciones()
{
    global $wpdb;
    $tablaHistorial = $wpdb->prefix . 'historial_acciones';
    $usuarios = get_users();

    $valAcc = calc_ing(48, false)['valAcc'];
    $fecha = date('Y-m-d');

    foreach ($usuarios as $user) {
        $acciones = get_user_meta($user->ID, 'acciones', true);

        if ($acciones) {
            $wpdb->delete(
                $tablaHistorial,
                [
                    'user_id' => $user->ID,
                    'fecha' => $fecha
                ],
                [
                    '%d',
                    '%s'
                ]
            );
            // Insertar el nuevo registro
            $wpdb->insert(
                $tablaHistorial,
                [
                    'user_id' => $user->ID,
                    'fecha' => $fecha,
                    'acciones' => $acciones,
                    'valor' => $acciones * $valAcc
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                    '%f'
                ]
            );
        }
    }
}

function registrar_evento_cron_historial_acciones()
{
    if (!wp_next_scheduled('evento_cron_historial_acciones')) {
        wp_schedule_event(time(), 'hourly', 'evento_cron_historial_acciones');
    }
}
add_action('wp', 'registrar_evento_cron_historial_acciones');

function calcularAccionPorUsuario($mostrarTodos = true)
{
    global $wpdb;
    $totalAcciones = 810000;
    $valAcc = calc_ing(48, false)['valAcc'];
    
    // Obtener usuarios según la condición
    if ($mostrarTodos) {
        $usuarios = array_filter(get_users(), function ($user) {
            return get_user_meta($user->ID, 'acciones', true);
        });
        usort($usuarios, function ($a, $b) {
            return get_user_meta($b->ID, 'acciones', true) - get_user_meta($a->ID, 'acciones', true);
        });
        array_shift($usuarios); // Opcional, si quieres excluir al primer usuario
    } else {
        $usuarios = [wp_get_current_user()];
        $acciones = get_user_meta($usuarios[0]->ID, 'acciones', true);
        if (!$acciones) return 'No tienes acciones.';
    }

    // Iniciar la tabla
    $output = '<table><thead><tr><th>Perfil</th><th>Usuario</th><th>Valor Total</th></tr></thead><tbody>';
    
    foreach ($usuarios as $user) {
        $acciones = get_user_meta($user->ID, 'acciones', true);
        $valorTotal = $acciones * $valAcc;
        $imagen = imagenPerfil($user->ID);

        // Generar la fila con perfil, nombre de usuario y valor total
        $output .= sprintf(
            '<tr><td><img src="%s" alt="%s" /></td><td>%s</td><td>$%s</td></tr>',
            esc_url($imagen),
            esc_attr($user->user_login),
            esc_html($user->user_login),
            number_format($valorTotal, 2, '.', '.')
        );
    }
    
    return $output . '</tbody></table>';
}
