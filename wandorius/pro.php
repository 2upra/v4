<?php 

//JS 
function script_stripe_pro() {
    wp_enqueue_script('script_stripe_pro', get_template_directory_uri() . '/js/stripepro.js', array(), '1.0.8', true);
}
add_action('wp_enqueue_scripts', 'script_stripe_pro');

//ENDPOINT PARA CREAR SESION PRO
add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/crear_sesion_pro', array(
        'methods' => 'POST',
        'callback' => 'crear_sesion_pro',
        'permission_callback' => '__return_true', 
    ));
});

//ENDPOINT WEBHOOK STATUS PRO
add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/stripe_webhook_pro', array(
        'methods' => 'POST',
        'callback' => 'stripe_webhook_pro',
        'permission_callback' => '__return_true', 
    ));
});

function boton_pro_shortcode() {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $user_info = get_userdata($user_id);
        $user_name = $user_info->user_login; 
        $es_usuario_pro = get_user_meta( $user_id, 'user_pro', true );

        if ( !$es_usuario_pro ) {
            return '<button id="botonPro" data-user-id="' . esc_attr($user_id) . '" data-user-name="' . esc_attr($user_name) . '">PRO</button>';
        }
    }
    return '';
}

//CREAR SESION PRO
function crear_sesion_pro(WP_REST_Request $request) {
    if (!isset($_ENV['STRIPEKEY'])) {
        return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', array('status' => 500));
    }
    $STRIPEKEY = $_ENV['STRIPEKEY'];
    \Stripe\Stripe::setApiKey($STRIPEKEY);

    $cancelUrl = "https://2upra.com";
    $price = "price_1PBgGfCdHJpmDkrrHorFUNaV";
    $body = $request->get_json_params();
    $userId = isset($body['user_id']) ? intval($body['user_id']) : 0;

    if (!$userId) {
        return new WP_REST_Response(['error' => 'Usuario no autenticado o ID no proporcionado.'], 401);
    }

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $price,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $cancelUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $userId,
        ]);

        return new WP_REST_Response(['id' => $session->id], 200);
    } catch (Exception $e) {
        return new WP_REST_Response(['error' => 'Error al crear la sesión: ' . $e->getMessage()], 500);
    }
}

function stripe_webhook_pro(WP_REST_Request $request) {
    if (!isset($_ENV['STRIPEKEY'])) {
        return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', array('status' => 500));
    }
    $STRIPEKEY = $_ENV['STRIPEKEY'];
    \Stripe\Stripe::setApiKey($STRIPEKEY);
    $body = $request->get_body();

    $webhookSecret = 'whsec_KqmYRMCJDpxcEBy9npv5XGNVcoii7lN1'; // Sustituir por tu secreto de webhook
    $signature = $request->get_header('stripe-signature');

    try {
        $event = \Stripe\Webhook::constructEvent($body, $signature, $webhookSecret);

        switch ($event['type']) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];

                if ($session['mode'] === 'subscription') {
                    $subscriptionId = $session['subscription'];
                    $subscription = \Stripe\Subscription::retrieve($subscriptionId);
                    $expectedPriceId = 'price_1PBgGfCdHJpmDkrrHorFUNaV';

                    foreach ($subscription->items->data as $item) {
                        if ($item->price->id === $expectedPriceId) {
                            $userId = $session['client_reference_id'];
                            if (!empty($userId)) {
                                update_user_meta($userId, 'user_pro', '1');
                                error_log('Actualizando usuario a Pro: ' . $userId);
                                break;
                            } else {
                                error_log('client_reference_id vacío o nulo');
                            }
                        }
                    }
                }
                break;

            default:
                error_log('Evento no manejado: ' . $event['type']);
                return new WP_REST_Response(['error' => 'Evento no manejado'], 400);
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    } catch (\UnexpectedValueException $e) {
        error_log('Error en el webhook: ' . $e->getMessage());
        return new WP_REST_Response(['error' => 'Payload inválido'], 400);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        error_log('Error en la verificación de la firma del webhook: ' . $e->getMessage());
        return new WP_REST_Response(['error' => 'Firma de webhook inválida'], 400);
    } catch (Exception $e) {
        error_log('Error en el webhook: ' . $e->getMessage());
        return new WP_REST_Response(['error' => 'Error interno'], 500);
    }
}

//PRO VERIFICAR
function verificar_estado_pro_usuario() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $es_pro = get_user_meta($user_id, 'user_pro', true);
        if ($es_pro == '1') {
            return "Eres un usuario Pro! 🌟";
        } else {
            return "Aún no eres un usuario Pro. 😞";
        }
    } else {
        return "Debes iniciar sesión para ver tu estado de suscripción.";
    }
}
function add_pro_modal_to_footer() {

    $plan_title = 'Patrocinio ';
    $highlight = '✨'; 
    $modal_content = '
        <p class="priceplan">$5 <span>USD/mensual</span></p>
        <p class="beneficiosplan">+ Participación creativa</p>
        <p class="beneficiosplan">+ Acceso anticipado</p>
        <p class="beneficiosplan">+ Contenido exclusivo</p>
        <p class="beneficiosplan">+ Reconocimiento</p>
        <p class="beneficiosplan">+ Acciones mensuales del proyecto</p>
        <p class="beneficiosplan">+ Sin limites de descarga</p>
        <p class="beneficiosplan">+ Sin limites de almacenamiento</p>
        <button class="DZYBQD MQKUSE">Suscribirte</button>';
    
    ?>
    <div class="panelperfilsup modalpro" id="propro">
        <div class="panelperfilsupsec pla1">
            <p class="titulomodal">Apoya el proyecto y recibe beneficios</p>
        </div>
        <div class="panelperfilsupsec plan2">
            <p class="tituloplan"><?php echo $plan_title . $highlight; ?></p>
            <?php echo $modal_content; ?>
        </div>
    </div>

    <div class="panelperfilsup modalpro" id="proproacciones">
        <div class="panelperfilsupsec pla1">
            <p class="titulomodal">Apoya el proyecto y recibe acciones mensuales</p>
        </div>
        <div class="panelperfilsupsec plan2">
            <p class="tituloplan"><?php echo $plan_title . $highlight; ?></p>
            <?php echo $modal_content; ?>
        </div>
    </div>
    <div id="modalBackground" class="modal-background"></div>
    <?php
}
add_action('wp_footer', 'add_pro_modal_to_footer');