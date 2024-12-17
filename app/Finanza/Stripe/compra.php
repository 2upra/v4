<?

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


add_action('rest_api_init', function () {
    error_log('rest_api_init ejecutado'); // Añade este log
    $routes = [
        '/stripe_webhook_compra' => 'stripe_webhook_compra',
        '/crear_sesion_compra' => 'crear_sesion_compra'
    ];
    foreach ($routes as $route => $callback) {
        register_rest_route('stripe/v1', $route, [
            'methods' => 'POST',
            'callback' => $callback,
            'permission_callback' => '__return_true',
        ]);
        error_log("Ruta registrada: /wp-json/stripe/v1" . $route); // Log corregido
    }
});

function crear_sesion_compra(WP_REST_Request $request)
{
    try {
        if (!isset($_ENV['STRIPEKEY'])) {
            $error_message = 'La clave de Stripe no está configurada';
            error_log('Error: ' . $error_message);
            return new WP_Error('stripe_key_missing', $error_message, ['status' => 500]);
        }

        \Stripe\Stripe::setApiKey($_ENV['STRIPEKEY']);
        $data = $request->get_json_params();

        $userId = sanitize_text_field($data['userId'] ?? '');
        $postId = sanitize_text_field($data['postId'] ?? '');
        $precio = floatval($data['precio'] ?? 0);

        if (!$userId) {
            $error_message = 'Parámetro userId inválido: debe ser una cadena de texto no vacía.';
            error_log('Error: ' . $error_message . ' (userId: ' . var_export($userId, true) . ')');
            return new WP_REST_Response(['error' => $error_message], 400);
        }

        if (!$postId) {
            $error_message = 'Parámetro postId inválido: debe ser una cadena de texto no vacía.';
            error_log('Error: ' . $error_message . ' (postId: ' . var_export($postId, true) . ')');
            return new WP_REST_Response(['error' => $error_message], 400);
        }

        if ($precio <= 0) {
            $error_message = 'Parámetro precio inválido: debe ser un número mayor que cero.';
            error_log('Error: ' . $error_message . ' (precio: ' . var_export($precio, true) . ')');
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
                'transaction_type' => 'comprabeat',
                'user_id' => $userId,
                'post_id' => $postId,
                'monto' => $precio,
            ],
            'mode' => 'payment',
            'success_url' => home_url(''),
            'cancel_url' => home_url(''),
        ]);

        return new WP_REST_Response(['id' => $session->id], 200);
    } catch (Exception $e) {
        $error_message = 'Error al crear sesión de Stripe: ' . $e->getMessage();
        error_log('Error: ' . $error_message); // Usa error_log para capturar el error de Stripe
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}

function manejador_webhook_compra(WP_REST_Request $request)
{
    $stripe = new \Stripe\StripeClient($_ENV['STRIPEKEY']);
    $endpoint_secret = ($_ENV['HOOKCOMPRA']);
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    } catch (\Exception $e) {
        $error_message = 'Error de webhook: ' . $e->getMessage();
        error_log('Webhook Error: ' . $error_message);
        return new WP_REST_Response('Webhook fallido', 400);
    }

    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;
        $metadata = $session->metadata;

        if ($metadata->transaction_type === 'compra') {
            $user_id = intval($metadata->user_id ?? 0);
            $post_id = intval($metadata->post_id ?? 0);
            $monto = floatval($metadata->monto ?? 0);
            $session_id = $session->id;

            if (!$user_id || !$post_id || $monto <= 0) {
                $error_message = 'Error de metadatos, los datos no son correctos user_id:' . $user_id . ' post_id:' . $post_id . ' monto:' . $monto;
                error_log('Webhook Error: ' . $error_message);
                return new WP_REST_Response('Metadatos invalidos', 400);
            }


            $fecha_compra = date('Y-m-d H:i:s');

            $compra_data = [
                'fecha' => $fecha_compra,
                'monto' => $monto,
                'session_id' => $session_id,
                'post_id' => $post_id
            ];

            // Registrar compra en la meta del usuario
            $compras_usuario = get_user_meta($user_id, 'compraBeat', true);
            $compras_usuario = is_array($compras_usuario) ? $compras_usuario : [];
            $compras_usuario[] = $compra_data;
            update_user_meta($user_id, 'compraBeat', $compras_usuario);

            // Registrar venta en la meta del autor del post
            $post_author_id = get_post_field('post_author', $post_id);
            if (!$post_author_id) {
                $error_message = 'Error al obtener el autor del post con ID: ' . $post_id;
                error_log('Webhook Error: ' . $error_message);
                return new WP_REST_Response('Error al obtener el autor del post', 400);
            }

            $venta_data = [
                'fecha' => $fecha_compra,
                'monto' => $monto,
                'session_id' => $session_id,
                'comprador_id' => $user_id, // guardamos el id del comprador
            ];


            $ventas_autor = get_user_meta($post_author_id, 'ventaBeat', true);
            $ventas_autor = is_array($ventas_autor) ? $ventas_autor : [];
            $ventas_autor[] = $venta_data;
            update_user_meta($post_author_id, 'ventaBeat', $ventas_autor);


            $success_message = 'Compra registrada correctamente para el usuario ' . $user_id . ' y venta registrada para el autor del post ' . $post_author_id;
            error_log('Webhook Success: ' . $success_message);
        } else {
            $error_message = 'Transacción no es de tipo compra: ' . $metadata->transaction_type;
            error_log('Webhook Error: ' . $error_message);
        }
    } else {
        $error_message = 'Unhandled event type: ' . $event->type;
        error_log('Webhook Error: ' . $error_message);
    }

    return new WP_REST_Response('Webhook recibido correctamente', 200);
}
