<?php 

// SUSCRIPCIONES 
add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/suscripcion_stripe', array(
        'methods' => 'POST',
        'callback' => 'crear_sesion_suscripcion', 
        'permission_callback' => '__return_true', 
    ));
});


add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/stripe_suscripcion', array(
        'methods' => 'POST',
        'callback' => 'stripe_suscripcion_handler',
        'permission_callback' => '__return_true',
    ));
});

//MANEJAR MULTIPLES SUSCRIPCIONES 
function update_user_meta_array($user_id, $meta_key, $value, $action = 'add') {
    $meta_values = get_user_meta($user_id, $meta_key, true);
    if (!is_array($meta_values)) {
        $meta_values = [];
    }

    if ($action == 'add' && !in_array($value, $meta_values)) {
        $meta_values[] = $value;
    } elseif ($action == 'remove') {
        $meta_values = array_diff($meta_values, [$value]);
    }

    update_user_meta($user_id, $meta_key, $meta_values);
}

//prueba
//CREAR SESION
function crear_sesion_suscripcion(WP_REST_Request $request) {
    if (!isset($_ENV['STRIPEKEY'])) {
        return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', array('status' => 500));
    }
    $STRIPEKEY = $_ENV['STRIPEKEY'];
    \Stripe\Stripe::setApiKey($STRIPEKEY);
    $data = $request->get_json_params();
    $offeringUserLogin = $data['offeringUserLogin'];
    $cancelUrl = "https://2upra.com/perfil/" . $offeringUserLogin;
    
    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $data['priceId'],
                'quantity' => 1,

            ]],
          'mode' => 'subscription',
            'success_url' => $data['successUrl'],
            'cancel_url' => $cancelUrl, 
            'metadata' => [
                'offeringUserId' => $data['offeringUserId'], 
                'offeringUserLogin' => $offeringUserLogin, 
                'offeringUserEmail' => $data['offeringUserEmail'], 
                'subscriberUserId' => $data['subscriberUserId'], 
                'subscriberUserLogin' => $data['subscriberUserLogin'], 
                'subscriberUserEmail' => $data['subscriberUserEmail'],
            ],
        ]);
        return new WP_REST_Response(['sessionId' => $session->id, 'checkoutUrl' => $session->url], 200);
    } catch (Exception $e) {
        error_log('Error al crear sesión de Stripe: ' . $e->getMessage());
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}

//MANEJADOR 

function stripe_suscripcion_handler($request) {
    if (!isset($_ENV['STRIPEKEY'])) {
        return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', array('status' => 500));
    }
    $STRIPEKEY = $_ENV['STRIPEKEY'];
    \Stripe\Stripe::setApiKey($STRIPEKEY);
    $payload = $request->get_body();
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $endpoint_secret = 'whsec_5G3dg6GsIWdpFmBoDi7FZMKjfVDzcCQo';

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            $fecha_actual = date_i18n('Y-m-d H:i:s'); 
            
            update_user_meta_array($session->metadata->subscriberUserId, 'subscription_ids', $session->subscription, 'add');
            update_user_meta_array($session->metadata->subscriberUserId, 'offering_user_ids', $session->metadata->offeringUserId, 'add');
            update_user_meta_array($session->metadata->subscriberUserId, 'fechas_suscripcion', $fecha_actual, 'add');


            $textoSuscriptor = "Te has suscrito a " . get_userdata($session->metadata->offeringUserId)->display_name;
            $textoOffering = get_userdata($session->metadata->subscriberUserId)->display_name . " se ha suscrito a ti";
            $enlaceSuscriptor = "/perfil/" . $session->metadata->offeringUserId; 
            $enlaceOffering = "/perfil/" . $session->metadata->subscriberUserId; 

            insertar_notificacion($session->metadata->subscriberUserId, $textoSuscriptor, $enlaceSuscriptor, $session->metadata->offeringUserId);
            insertar_notificacion($session->metadata->offeringUserId, $textoOffering, $enlaceOffering, $session->metadata->subscriberUserId);
            break;

        case 'customer.subscription.deleted':
            $subscription = $event->data->object;
            $users = get_users();
            foreach ($users as $user) {
                $subscription_ids = get_user_meta($user->ID, 'subscription_ids', true);
                $offering_user_ids = get_user_meta($user->ID, 'offering_user_ids', true);

                if (is_array($subscription_ids) && ($key = array_search($subscription->id, $subscription_ids)) !== false) {

                    if (is_array($offering_user_ids) && isset($offering_user_ids[$key])) {
                        $offeringUserId = $offering_user_ids[$key];

                        unset($subscription_ids[$key]);
                        unset($offering_user_ids[$key]);

                        update_user_meta($user->ID, 'subscription_ids', array_values($subscription_ids)); 
                        update_user_meta($user->ID, 'offering_user_ids', array_values($offering_user_ids)); 
        
                        $textoDesuscripcionSuscriptor = "Te has desuscrito de " . get_userdata($offeringUserId)->display_name;
                        $enlaceDesuscripcionSuscriptor = "/perfil/" . $offeringUserId;
            
                        insertar_notificacion($user->ID, $textoDesuscripcionSuscriptor, $enlaceDesuscripcionSuscriptor, $offeringUserId);
            
                        $textoDesuscripcionOffering = get_userdata($user->ID)->display_name . " se ha desuscrito de ti";
                        $enlaceDesuscripcionOffering = "/perfil/" . $user->ID; 
            
                        insertar_notificacion($offeringUserId, $textoDesuscripcionOffering, $enlaceDesuscripcionOffering, $user->ID);
            
                        delete_user_meta($user->ID, 'subscription_id');
                        delete_user_meta($user->ID, 'subscriber_id');
                        delete_user_meta($user->ID, 'offering_user_id');
                        break;
                        }
                    }
                }
                break;
            }
        return new WP_REST_Response(['message' => 'Webhook Handled'], 200);
    } catch(\UnexpectedValueException $e) {
        return new WP_REST_Response(['error' => 'Invalid Payload'], 400);
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        return new WP_REST_Response(['error' => 'Invalid Signature'], 400);
    }
}

//JS 
function script_stripe_suscripcion() {
    wp_enqueue_script('stripe-suscripcion-js', get_template_directory_uri() . '/js/stripe-suscripcion.js', array(), '1.0.21', true);
}
add_action('wp_enqueue_scripts', 'script_stripe_suscripcion');