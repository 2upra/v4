<?php

// Registro de rutas REST API
add_action('rest_api_init', function () {
    $routes = [
        '/stripe_webhook_acciones' => 'manejador_webhook_acciones',
        '/crear_sesion_acciones' => 'crear_sesion_acciones'
    ];
    foreach ($routes as $route => $callback) {
        register_rest_route('avada/v1', $route, [
            'methods' => 'POST',
            'callback' => $callback,
            'permission_callback' => '__return_true',
        ]);
    }
});

function crear_sesion_acciones(WP_REST_Request $request) {
    try {
        if (!isset($_ENV['STRIPEKEY'])) {
            return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', ['status' => 500]);
        }
        \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
        $data = $request->get_json_params();
        $userId = sanitize_text_field($data['userId'] ?? '');
        $cantidadCompra = floatval($data['cantidadCompra'] ?? 0);

        if (!$userId || $cantidadCompra <= 0) {
            return new WP_REST_Response(['error' => 'Parámetros inválidos proporcionados'], 400);
        }

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Compra de Acciones'],
                    'unit_amount' => intval($cantidadCompra * 100),
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'transaction_type' => 'compra',
                'user_id' => $userId,
            ],
            'mode' => 'payment',
            'success_url' => home_url(''),
            'cancel_url' => home_url(''),
        ]);

        return new WP_REST_Response(['id' => $session->id], 200);

    } catch (Exception $e) {
        error_log('Error al crear sesión de Stripe: ' . $e->getMessage());
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}

function manejador_webhook_acciones(WP_REST_Request $request) {
    $stripe = new \Stripe\StripeClient('sk_test_51M9uLoCdHJpmDkrrkRjNxoLxfT4Xm9blOJj8NMQZ5cTWkZzDvU3jFQKnMYfUsv3MuFIu2pACQrrdMtc5NGlkWW4n00IWqZAMFC');
    $endpoint_secret = 'whsec_RAfNkxkUWDq2DSw2KrJl7ekXmCquGQpO';
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    } catch (\Exception $e) {
        guardar_log('Error de webhook: ' . $e->getMessage());
        return new WP_REST_Response('Webhook fallido', 400);
    }

    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;
        $metadata = $session->metadata;

        if ($metadata->transaction_type === 'compra') {
            $compras = get_user_meta($metadata->user_id, 'compras_acciones', true) ?: [];
            $compras[] = ['cantidad' => $session->amount_total / 100, 'fecha' => current_time('mysql')];
            update_user_meta($metadata->user_id, 'compras_acciones', $compras);
        } else {
            guardar_log('Transaction type is not compra or metadata is missing.');
        }
    } else {
        guardar_log('Unhandled event type: ' . $event->type);
    }

    return new WP_REST_Response('Webhook recibido correctamente', 200);
}

function get_all_transactions() {
    $all_transactions = [];
    foreach (get_users() as $user) {
        $compras = get_user_meta($user->ID, 'compras_acciones', true);
        if (is_array($compras)) {
            foreach ($compras as $compra) {
                $all_transactions[] = [
                    'user_id' => $user->ID,
                    'user_email' => $user->user_email,
                    'cantidad' => $compra['cantidad'],
                    'fecha' => $compra['fecha']
                ];
            }
        }
    }
    return $all_transactions;
}

function generate_transactions_table() {
    $output = '<table class="transactions-table"><thead><tr><th>Perfil</th><th>Usuario</th><th>Cantidad</th><th>Fecha</th></tr></thead><tbody>';
    foreach (get_all_transactions() as $transaction) {
        if ($user = get_user_by('email', $transaction['user_email'])) {
            $output .= sprintf(
                '<tr class="XXDD"><td><img src="%s" alt="%s" /></td><td>%s</td><td>$%s</td><td>%s</td></tr>',
                esc_url(obtener_url_imagen_perfil_o_defecto($user->ID)),
                esc_attr($user->user_login),
                esc_html($user->user_login),
                esc_html($transaction['cantidad']),
                esc_html($transaction['fecha'])
            );
        }
    }
    return $output . '</tbody></table>';
}