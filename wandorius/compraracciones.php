<?php

//JS 
function script_stripeacciones()
{
    wp_enqueue_script('script_stripeacciones', get_template_directory_uri() . '/js/stripeacciones.js', array(), '1.0.6', true);
}
add_action('wp_enqueue_scripts', 'script_stripeacciones');


//ENDPOINT WEBHOOK ACCIONES
add_action('rest_api_init', function () {
    register_rest_route(
        'avada/v1',
        '/stripe_webhook_acciones',
        array(
            'methods' => 'POST',
            'callback' => 'manejador_webhook_acciones',
            'permission_callback' => '__return_true',
        )
    );
});


add_action('rest_api_init', function () {
    register_rest_route(
        'avada/v1',
        '/crear_sesion_acciones',
        array(
            'methods' => 'POST',
            'callback' => 'crear_sesion_acciones',
            'permission_callback' => '__return_true',
        )
    );
});

function crear_sesion_acciones(WP_REST_Request $request) {
    try {
        if (!isset($_ENV['STRIPEKEY'])) {
            return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', array('status' => 500));
        }
        $STRIPEKEY = $_ENV['STRIPEKEY'];
        \Stripe\Stripe::setApiKey($STRIPEKEY);

        // Obtener parámetros de la solicitud
        $data = $request->get_json_params();
        $userId = isset($data['userId']) ? sanitize_text_field($data['userId']) : '';
        $cantidadCompra = isset($data['cantidadCompra']) ? floatval($data['cantidadCompra']) : 0;

        // Validar parámetros
        if (empty($userId) || !is_numeric($cantidadCompra) || $cantidadCompra <= 0) {
            return new WP_REST_Response(['error' => 'Parámetros inválidos proporcionados'], 400);
        }

        // Calcular el monto en centavos
        $unit_amount = intval($cantidadCompra * 100);

        // Crear sesión de Stripe
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Compra de Acciones',
                        ],
                        'unit_amount' => $unit_amount,
                    ],
                    'quantity' => 1,
                ]
            ],
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
        // Registrar error y devolver respuesta
        error_log('Error al crear sesión de Stripe: ' . $e->getMessage());
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}




function manejador_webhook_acciones(WP_REST_Request $request)
{

    $stripe = new \Stripe\StripeClient('sk_test_51M9uLoCdHJpmDkrrkRjNxoLxfT4Xm9blOJj8NMQZ5cTWkZzDvU3jFQKnMYfUsv3MuFIu2pACQrrdMtc5NGlkWW4n00IWqZAMFC');
    $endpoint_secret = 'whsec_RAfNkxkUWDq2DSw2KrJl7ekXmCquGQpO';
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    } catch (\UnexpectedValueException $e) {
        http_response_code(400);
        guardar_log('Error de payload: ' . $e->getMessage());
        exit();
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        http_response_code(400);
        guardar_log('Error de firma: ' . $e->getMessage());
        exit();
    }

    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            $metadata = $session->metadata;

            if (isset($metadata->transaction_type) && $metadata->transaction_type == 'compra') {
                $userId = $metadata->user_id;
                $cantidadCompra = $session->amount_total / 100; // Convertir de centavos a dólares
                $fechaCompra = current_time('mysql');

                // Obtener las compras existentes del usuario
                $compras = get_user_meta($userId, 'compras_acciones', true);
                if (!is_array($compras)) {
                    $compras = [];
                }

                // Agregar la nueva compra
                $compras[] = [
                    'cantidad' => $cantidadCompra,
                    'fecha' => $fechaCompra,
                ];

                // Actualizar la meta del usuario
                update_user_meta($userId, 'compras_acciones', $compras);
            } else {
                guardar_log('Transaction type is not compra or metadata is missing.');
            }
            break;
        default:
            guardar_log('Unhandled event type: ' . $event->type);
            break;
    }

    http_response_code(200);
    return new WP_REST_Response('Webhook recibido correctamente', 200);
}

function get_all_transactions()
{
	$users = get_users();
	$all_transactions = [];

	foreach ($users as $user) {
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
    $transactions = get_all_transactions();

    $output = '<table class="transactions-table">';
    $output .= '<thead><tr><th>Perfil</th><th>Usuario</th><th>Cantidad</th><th>Fecha</th></tr></thead>';
    $output .= '<tbody>';

    foreach ($transactions as $transaction) {
        $user = get_user_by('email', $transaction['user_email']);
        if (!$user) continue;

        $profile_picture = obtener_url_imagen_perfil_o_defecto($user->ID);

        $output .= sprintf(
            '<tr class="XXDD"><td><img src="%s" alt="%s" /></td><td>%s</td><td>$%s</td><td>%s</td></tr>',
            esc_url($profile_picture),
            esc_attr($user->user_login),
            esc_html($user->user_login),
            esc_html($transaction['cantidad']),
            esc_html($transaction['fecha'])
        );
    }

    return $output . '</tbody></table>';
}