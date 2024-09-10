<?php
add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/crear_sesion_pro', ['methods' => 'POST', 'callback' => 'crear_sesion_pro', 'permission_callback' => '__return_true']);
    register_rest_route('avada/v1', '/stripe_webhook_pro', ['methods' => 'POST', 'callback' => 'stripe_webhook_pro', 'permission_callback' => '__return_true']);
});

function crear_sesion_pro(WP_REST_Request $request)
{
    if (!isset($_ENV['STRIPEKEY'])) return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', ['status' => 500]);
    \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
    $body = $request->get_json_params();
    $userId = isset($body['user_id']) ? intval($body['user_id']) : 0;
    if (!$userId) return new WP_REST_Response(['error' => 'Usuario no autenticado o ID no proporcionado.'], 401);
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
        return new WP_REST_Response(['error' => 'Error al crear la sesión: ' . $e->getMessage()], 500);
    }
}

function stripe_webhook_pro(WP_REST_Request $request)
{
    if (!isset($_ENV['STRIPEKEY'])) return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', ['status' => 500]);
    \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
    try {
        $event = \Stripe\Webhook::constructEvent($request->get_body(), $request->get_header('stripe-signature'), 'whsec_KqmYRMCJDpxcEBy9npv5XGNVcoii7lN1');
        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];
            if ($session['mode'] === 'subscription') {
                $subscription = \Stripe\Subscription::retrieve($session['subscription']);
                foreach ($subscription->items->data as $item) {
                    if ($item->price->id === 'price_1PBgGfCdHJpmDkrrHorFUNaV') {
                        $userId = $session['client_reference_id'];
                        if (!empty($userId)) {
                            update_user_meta($userId, 'user_pro', '1');
                            error_log('Actualizando usuario a Pro: ' . $userId);
                        } else {
                            error_log('client_reference_id vacío o nulo');
                        }
                        break;
                    }
                }
            }
        }
        return new WP_REST_Response(['status' => 'success'], 200);
    } catch (Exception $e) {
        error_log('Error en el webhook: ' . $e->getMessage());
        return new WP_REST_Response(['error' => $e instanceof \Stripe\Exception\SignatureVerificationException ? 'Firma de webhook inválida' : 'Error interno'], $e instanceof \UnexpectedValueException ? 400 : 500);
    }
}


