<?

add_action('rest_api_init', function () {
    error_log('rest_api_init ejecutado'); // Añade este log
    $routes = [
        '/stripe_webhook_compra_test' => 'stripe_webhook_compra_test',
        '/crear_sesion_compra_test' => 'crear_sesion_compra_test'
    ];
    foreach ($routes as $route => $callback) {
        register_rest_route('avada/v1', $route, [
            'methods' => 'POST',
            'callback' => $callback,
            'permission_callback' => '__return_true',
        ]);
        error_log("Ruta registrada: /wp-json/avada/v1" . $route); // Añade este log dentro del bucle
    }
});

/*
https://2upra.com/wp-json/avada/v1/crear_sesion_compra_test
[17-Dec-2024 21:08:45 UTC] Ruta registrada: /wp-json/avada/v1/stripe_webhook_compra_test
[17-Dec-2024 21:08:45 UTC] Ruta registrada: /wp-json/avada/v1/crear_sesion_compra_test
*/

function botonCompra($postId)
{
    // Obtiene el ID del usuario actual
    $userId = get_current_user_id();
    $precio = get_post_meta($postId, 'precioRola1', true);
    if (empty($precio)) {
        $precio = get_post_meta($postId, 'precioRola', true);
    }
    $precio = is_numeric($precio) ? $precio : '0.00';

    // Inicia el buffer de salida para capturar la salida HTML
    ob_start();
?>
    <div class="TJKQGJ botonCompraDiv">
        <button
            class="botonCompra"
            data-post_id="<?= esc_attr($postId) ?>"
            data-user_id="<?= esc_attr($userId) ?>"
            data-nonce="<?= wp_create_nonce('compraNonce') ?>">
            <?php echo $GLOBALS['dolar']; ?>
        </button>
        <span class="precioCount"><? echo esc_html($precio); ?></span>
    </div>
<?

    $output = ob_get_clean();
    return $output;
}


function stripe_webhook_compra_test(WP_REST_Request $request)
{
    try {
        if (!isset($_ENV['STRIPEKEY'])) {
            $error_message = 'La clave de Stripe no está configurada';
            stripeError($error_message);
            return new WP_Error('stripe_key_missing', $error_message, ['status' => 500]);
        }

        \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
        $data = $request->get_json_params();

        $userId = sanitize_text_field($data['userId'] ?? '');
        $postId = sanitize_text_field($data['postId'] ?? '');
        $precio = floatval($data['precio'] ?? 0);

        if (!$userId || $postId || $precio <= 0) {
            $error_message = 'Parámetros inválidos proporcionados';
            stripeError($error_message);
            return new WP_REST_Response(['error' => $error_message], 400);
        }

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Compra de beat&sample'],
                    'unit_amount' => intval($precio * 100),
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
        $error_message = 'Error al crear sesión de Stripe: ' . $e->getMessage();
        stripeError($error_message);
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}

function crear_sesion_compra() {}
