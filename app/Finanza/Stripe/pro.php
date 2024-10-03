<?php
add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/crear_sesion_pro', ['methods' => 'POST', 'callback' => 'crear_sesion_pro', 'permission_callback' => '__return_true']);
    register_rest_route('avada/v1', '/stripe_webhook_pro', ['methods' => 'POST', 'callback' => 'stripe_webhook_pro', 'permission_callback' => '__return_true']);
});


function crear_sesion_pro(WP_REST_Request $request)
{
    if (!isset($_ENV['STRIPEKEY'])) {
        $error = 'La clave de Stripe no está configurada';
        stripeError($error);  // Log del error
        return new WP_Error('stripe_key_missing', $error, ['status' => 500]);
    }

    \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
    $body = $request->get_json_params();
    $userId = isset($body['user_id']) ? intval($body['user_id']) : 0;
    
    if (!$userId) {
        $error = 'Usuario no autenticado o ID no proporcionado.';
        stripeError($error);  // Log del error
        return new WP_REST_Response(['error' => $error], 401);
    }

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [['price' => 'price_1PBgGfCdHJpmDkrrHorFUNaV', 'quantity' => 1]],
            'mode' => 'subscription',
            'success_url' => 'https://2upra.com',
            'cancel_url' => 'https://2upra.com',
            'client_reference_id' => $userId,
        ]);

        return new WP_REST_Response(['id' => $session->id], 200);
    } catch (Exception $e) {
        $error = 'Error al crear la sesión: ' . $e->getMessage();
        stripeError($error);  // Log del error
        return new WP_REST_Response(['error' => $error], 500);
    }
}

function stripe_webhook_pro(WP_REST_Request $request)
{
    if (!isset($_ENV['STRIPEKEY'])) {
        $error = 'La clave de Stripe no está configurada';
        stripeError($error);  // Log del error
        return new WP_Error('stripe_key_missing', $error, ['status' => 500]);
    }

    \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);

    try {
        if (!isset($_ENV['HOOKPRO'])) {
            $error = 'La clave de webhook no está configurada';
            stripeError($error);  // Log del error
            return new WP_Error('hookpro_missing', $error, ['status' => 500]);
        }

        $event = \Stripe\Webhook::constructEvent(
            $request->get_body(),
            $request->get_header('stripe-signature'),
            $_ENV['HOOKPRO']
        );

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];

            if ($session['mode'] === 'subscription') {
                $subscription = \Stripe\Subscription::retrieve($session['subscription']);
                foreach ($subscription->items->data as $item) {
                    if ($item->price->id === 'price_1PBgGfCdHJpmDkrrHorFUNaV') {
                        $userId = $session['client_reference_id'];
                        if (!empty($userId)) {
                            update_user_meta($userId, 'user_pro', '1');
                            stripeError('Actualizando usuario a Pro: ' . $userId);  // Log de éxito
                        } else {
                            stripeError('client_reference_id vacío o nulo');  // Log del error
                        }
                        break;
                    }
                }
            }
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    } catch (Exception $e) {
        $error = 'Error en el webhook: ' . $e->getMessage();
        stripeError($error);  // Log del error
        return new WP_REST_Response(
            [
                'error' => $e instanceof \Stripe\Exception\SignatureVerificationException ? 'Firma de webhook inválida' : 'Error interno'
            ],
            $e instanceof \UnexpectedValueException ? 400 : 500
        );
    }
}



