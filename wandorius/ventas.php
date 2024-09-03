<?php 

function ventas_shortcode_resumen($atts) {
    ob_start();
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type' => 'ventas',
        'posts_per_page' => -1, 
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $ventas = new WP_Query($args);
    $default_image_url = 'https://2upra.com/wp-content/uploads/2024/03/0d3eda100891319.5f12a58c2ca36.png';

    if ($ventas->have_posts()) {
        while ($ventas->have_posts()) {
            $ventas->the_post();
            $buyer_id = get_post_meta(get_the_ID(), 'buyer_id', true);
            $seller_id = get_post_meta(get_the_ID(), 'seller_id', true);

            if ($current_user_id == $buyer_id || $current_user_id == $seller_id || current_user_can('administrator')) {
                $buyer_info = get_userdata($buyer_id);
                $seller_info = get_userdata($seller_id);

                $buyer_name = $buyer_info->display_name;
                $seller_name = $seller_info->display_name;

                $related_post_id = get_post_meta(get_the_ID(), 'related_post_id', true);
                $product_id = get_post_meta(get_the_ID(), 'product_id', true);
                $product_post = get_post($product_id);

                if ($product_post !== null) {
                    $product_post_title = $product_post->post_title;
                    $product_post_url = get_permalink($product_id);
                } else {
                    $product_post_content = 'Contenido del producto no disponible.';
                    $product_post_title = 'Título no disponible';
                    $product_post_url = '#';
                }

                $venta_post_url = get_permalink(); // Enlace al post de la venta actual
                $image_url = get_post_meta(get_the_ID(), 'image_url', true) ?: $default_image_url;
                $price = get_post_meta(get_the_ID(), 'price', true) / 100;
                $date = get_the_date('Y-m-d H:i:s');

                echo "<div class='venta-item resumen'>";
                echo "<img src='$image_url' alt='Imagen del producto'>";
                echo "<div class='detalles-venta resumen'>";
                echo "<p>$product_post_title</p>";
                echo "<p>$date</p>";
                echo "<p>$buyer_name - $seller_name - $ $price</p>";
                echo "<a href='$product_post_url'>Más detalles del producto</a> | <a href='$venta_post_url'>Detalles de la venta</a>";
                echo "</div>";
                echo "</div>";
            } else {
            }
        }
    } else {
        echo '<p>No se encontraron ventas.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('ventas-resumen', 'ventas_shortcode_resumen');

//ENDPOINT WEBHOOK DATOS FRONTEND
add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/stripe_webhook', array(
        'methods' => 'POST',
        'callback' => 'manejador_webhook_stripe',
        'permission_callback' => '__return_true', 
    ));
});

//ENDPOINT PARA CREAR SESION VENTA
add_action('rest_api_init', function () {
    register_rest_route('avada/v1', '/crear_sesion_checkout', array(
        'methods' => 'POST',
        'callback' => 'crear_sesion_checkout',
        'permission_callback' => '__return_true', 
    ));
});

//CREAR SESION VENTA
function crear_sesion_checkout(WP_REST_Request $request) {

    if (!isset($_ENV['STRIPEKEY'])) {
        return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', array('status' => 500));
    }
    $STRIPEKEY = $_ENV['STRIPEKEY'];
    \Stripe\Stripe::setApiKey($STRIPEKEY);
    $data = $request->get_json_params();   
    $productId = $data['productId'];
    $price = $data['post_price'];
    $buyerId = $data['buyerId'] ?? null; 
    $buyerUsername = $data['buyerUsername'] ?? null;
    $sellerId = $data['sellerId'] ?? null;
    $sellerUsername = $data['sellerUsername'] ?? null;
    $imageUrl = $data['imageUrl'] ?? null; 
    $audioUrl = $data['audioUrl'] ?? null; 
    $unique_id = time() . rand();

    if (!is_numeric($price) || floatval($price) < 0) {
        error_log('Precio inválido recibido: ' . $price);
        return new WP_REST_Response(['error' => 'Precio inválido proporcionado'], 400);
    }
    $unit_amount = intval(floatval($price) * 100);

    try {

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => get_the_title($productId),
                    ],
                    'unit_amount' => $unit_amount,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [

                'transaction_type' => 'venta',
                'product_id' => $productId,
                'buyer_id' => $buyerId,
                'buyer_username' => $buyerUsername,
                'seller_id' => $sellerId,
                'sellerUsername' => $sellerUsername,
                'imageUrl' => $imageUrl, 
                'audioUrl' => $audioUrl, 
                'unique_id' => $unique_id,
            ],
            
            'mode' => 'payment',
            'success_url' => home_url('/ventas/' . urlencode('venta' . '-' . $productId . '-' . $unique_id)),
            'cancel_url' => home_url('/notas-de-parche'),
        ]);
    } catch (Exception $e) {
        error_log('Error al crear sesión de Stripe: ' . $e->getMessage());
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }

    return new WP_REST_Response(['id' => $session->id], 200);
}

//MANEJADOR venta

function manejador_webhook_stripe(WP_REST_Request $request) {
    if (!isset($_ENV['STRIPEKEY'])) {
        return new WP_Error('stripe_key_missing', 'La clave de Stripe no está configurada', array('status' => 500));
    }
    $STRIPEKEY = $_ENV['STRIPEKEY'];
    \Stripe\Stripe::setApiKey($STRIPEKEY);

    if (!isset($_ENV['ENDVENTA'])) {
        return new WP_Error('endpoint_secret_missing', 'El endpoint secret no está configurado', array('status' => 500));
    }
    
    $endpoint_secret = $_ENV['ENDVENTA'];

    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    } catch (\UnexpectedValueException $e) {
        http_response_code(400);
        exit();
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        http_response_code(400);
        exit();
    }


    switch ($event->type) {
        case 'checkout.session.completed':
        $session = $event->data->object;
        $metadata = $session->metadata;

        if (isset($metadata->transaction_type) && $metadata->transaction_type == 'venta') {
            $customer_details = $session->customer_details;
            $amount_total = $session->amount_total;
            $status = $session->status;
            $cancel_url = $session->cancel_url;
            $unique_id = $metadata->unique_id;

            $post_id = wp_insert_post([
                'post_title' => 'Venta ' . $metadata->product_id . ' ' . $unique_id,
                'post_content' => 'Detalles de la venta',
                'post_status' => 'publish',
                'post_type' => 'ventas',
            ]);

            if ($post_id) {
                add_post_meta($post_id, 'buyer_id', $metadata->buyer_id);
                add_post_meta($post_id, 'buyer_username', $metadata->buyer_username);
                add_post_meta($post_id, 'email_buyer', $customer_details->email);
                add_post_meta($post_id, 'name_buyer', $customer_details->name);

                add_post_meta($post_id, 'product_id', $metadata->product_id);
                add_post_meta($post_id, 'audio_url', $metadata->audioUrl);
                add_post_meta($post_id, 'image_url', $metadata->imageUrl);

                add_post_meta($post_id, 'price', $amount_total); 
                add_post_meta($post_id, 'status', $status); 
                add_post_meta($post_id, 'url', $cancel_url); 

                add_post_meta($post_id, 'seller_username', $metadata->sellerUsername);
                add_post_meta($post_id, 'seller_id', $metadata->seller_id);

                $textoComprador = "Tu compra de " . get_the_title($metadata->product_id) . " ha sido completada.";
                $enlaceComprador = "/perfil/" . $metadata->buyer_id; 
                insertar_notificacion($metadata->buyer_id, $textoComprador, $enlaceComprador, $metadata->seller_id);

                $textoVendedor = "Has vendido " . get_the_title($metadata->product_id) . ".";
                $enlaceVendedor = "/perfil/" . $metadata->seller_id; 
                insertar_notificacion($metadata->seller_id, $textoVendedor, $enlaceVendedor, $metadata->buyer_id);
            }
        }
        break;     
}

http_response_code(200);
}












function ventas_shortcode($atts) {
    ob_start();

    $current_user_id = get_current_user_id();

    $args = array(
        'post_type' => 'ventas',
        'posts_per_page' => 2,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $ventas = new WP_Query($args);
    $default_image_url = 'https://2upra.com/wp-content/uploads/2024/03/0d3eda100891319.5f12a58c2ca36.png';

    if ($ventas->have_posts()) {
        while ($ventas->have_posts()) {

            $ventas->the_post();
            $buyer_id = get_post_meta(get_the_ID(), 'buyer_id', true);
            $seller_id = get_post_meta(get_the_ID(), 'seller_id', true);
           
            if ($current_user_id == $buyer_id || $current_user_id == $seller_id || current_user_can('administrator')) {
                $related_post_id = get_post_meta(get_the_ID(), 'related_post_id', true);
                $product_id = get_post_meta(get_the_ID(), 'product_id', true);
                $product_post = get_post($product_id);

                if ($product_post !== null) {
                    // Si el post del producto existe, obtienes su contenido
                    $product_post_content = apply_filters('the_content', $product_post->post_content);
                    $product_post_title = $product_post->post_title;
                    $product_post_date = get_the_date('Y-m-d H:i:s', $product_id);
                    $product_post_url = get_permalink($product_id);
                } else {
                    // Si no existe, puedes manejar el caso como prefieras
                    $product_post_content = 'Contenido del producto no disponible.';
                    $product_post_title = 'Título no disponible';
                    $product_post_date = '';
                    $product_post_url = '#';
                }

                $buyer_info = get_userdata($buyer_id);
                $seller_info = get_userdata($seller_id);
                
                $image_url = get_post_meta(get_the_ID(), 'image_url', true); 
                if (empty($image_url)) {
                    $post_thumbnail_id = get_post_thumbnail_id(get_the_ID());
                    if ($post_thumbnail_id) {
                        $image_url = wp_get_attachment_image_url($post_thumbnail_id, 'medium');
                    } else {
                        $image_url = $default_image_url; 
                    }
                }
                $audio_url = get_post_meta(get_the_ID(), 'audio_url', true);
                $price = get_post_meta(get_the_ID(), 'price', true);
                $corrected_price = $price / 100;
                $date = get_the_date('Y-m-d H:i:s');
                $buyer_email = $buyer_info->user_email;
                $seller_email = $seller_info->user_email;
                
                $buyer_profile_pic = obtener_url_imagen_perfil_o_defecto($buyer_id);
                $seller_profile_pic = obtener_url_imagen_perfil_o_defecto($seller_id);
                
                $buyer_name_or_username = !empty($buyer_info->display_name) ? $buyer_info->display_name : $buyer_info->user_login;
                $seller_name_or_username = !empty($seller_info->display_name) ? $seller_info->display_name : $seller_info->user_login;


                
                $content = apply_filters('the_content', get_the_content());

                $related_post = get_post($related_post_id);
                $related_post_title = $related_post->post_title;
                $related_post_date = get_the_date('Y-m-d H:i:s', $related_post_id);
                $related_post_url = get_permalink($related_post_id);

                $buyer_profile_url = get_author_posts_url($buyer_id);
                $seller_profile_url = get_author_posts_url($seller_id);
                $status_venta = get_post_meta(get_the_ID(), 'status', true);

                echo <<<HTML
                <div class='venta-item'>
                    <div class='venta-header'>
                        <div class='venta-buyer'>   
                                <div class='imagen-buyer'>
                                    <a href='$buyer_profile_url'><img src='$buyer_profile_pic' alt='Buyer profile pic'></a>
                                </div>
                                <div class='name-buyer'>
                                    <a href='$buyer_profile_url'><span>{$buyer_name_or_username}</span></a>
                                    <p class='type-buyer'>Comprador</p>
                                </div>
                            </div>
                        <div class='venta-seller'>
                            <div class='name-seller'>
                                <a href='$seller_profile_url'><span>{$seller_name_or_username}</span></a>
                                <p class='type-seller'>Vendedor</p>
                            </div>
                            <div class='imagen-seller'>
                                <a href='$seller_profile_url'><img src='$seller_profile_pic' alt='Seller profile pic'></a>
                            </div>
                        </div>
                    </div>
                    <div class='infos-usuarios'>
                    <div class='info-buyer'>
                    <p>ID: {$buyer_id} - {$buyer_name_or_username}</p>
                    <a href='mailto:$buyer_email'>$buyer_email</a>
                    </div>
                    <div class='info-seller'>
                    <p>ID: {$seller_id} - {$seller_name_or_username}</p>
                    <a href='mailto:$seller_email'>$seller_email</a>
                    </div>
                    </div>
                    <div class='venta-body'>
                    <img src='$image_url' alt='Product image'>
                    <div class='detalles-venta'>
                    <div class='venta-content'>$product_post_content</div>
                    <span><a href='$related_post_url'>$related_post_title</a></span>
                    <div class='venta-date'>$date</div>
                    <div id="waveform-".get_the_ID(). class="waveform-container-venta" data-audio-url="$audio_url">
                    <div class="waveform-background" style="display: none"></div>
                    <div class="waveform-message"></div>
                    <div class="waveform-loading" style="display: none;">Cargando...</div>
                    </div>
                    <div class='status-venta'>
                    <div class='venta-price'>$ $corrected_price</div>
                    </div>
                    </div>      
                </div>
                </div>
                HTML;
                if (comments_open() || get_comments_number()) {
                    echo "<div class='ventass-comments'>";
                    comments_template();
                    echo "</div>";
                    }
                } else {
                echo '<p>No tienes permiso para ver esta información.</p>';
                }
            }
        } else {
            echo '<p>No se encontraron colaboraciones.</p>';
        }

        wp_reset_postdata();
        return ob_get_clean();
}
add_shortcode('ventas', 'ventas_shortcode');