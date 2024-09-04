<?php
add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/crear_sesion_pro', ['methods' => 'POST', 'callback' => 'crear_sesion_pro', 'permission_callback' => '__return_true']);
    register_rest_route('avada/v1', '/stripe_webhook_pro', ['methods' => 'POST', 'callback' => 'stripe_webhook_pro', 'permission_callback' => '__return_true']);
});

function boton_pro_shortcode()
{
    if (is_user_logged_in() && !get_user_meta(get_current_user_id(), 'user_pro', true)) {
        $user_info = get_userdata(get_current_user_id());
        return '<button id="botonPro" data-user-id="' . esc_attr(get_current_user_id()) . '" data-user-name="' . esc_attr($user_info->user_login) . '">PRO</button>';
    }
    return '';
}

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

function verificar_estado_pro_usuario()
{
    if (!is_user_logged_in()) return "Debes iniciar sesión para ver tu estado de suscripción.";
    return get_user_meta(get_current_user_id(), 'user_pro', true) == '1' ? "Eres un usuario Pro! 🌟" : "Aún no eres un usuario Pro. 😞";
}

function add_pro_modal_to_footer()
{

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
