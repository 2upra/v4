<?php
// app/Services/VentaService.php
// Servicio para manejar la lógica de datos de las ventas.
// Creado para mover la lógica de obtención de datos desde single-ventas.php

/**
 * Obtiene todos los datos necesarios para mostrar una venta individual.
 *
 * @param int $postId El ID del post de tipo 'venta'.
 * @return array Un array asociativo con los datos de la venta.
 */
function obtenerDatosVenta($postId) {
    // Obtener metadatos básicos
    $buyer_id = get_post_meta($postId, 'buyer_id', true);
    $seller_id = get_post_meta($postId, 'seller_id', true);
    $related_post_id = get_post_meta($postId, 'related_post_id', true);
    $product_id = get_post_meta($postId, 'product_id', true);
    $image_url = get_post_meta($postId, 'image_url', true);
    $audio_url = get_post_meta($postId, 'audio_url', true);
    $price = get_post_meta($postId, 'price', true);
    $status_venta = get_post_meta($postId, 'status', true);

    // Datos del producto
    $product_post = get_post($product_id);
    if ($product_post instanceof WP_Post) {
        $product_post_content = apply_filters('the_content', $product_post->post_content);
        $product_post_title = $product_post->post_title;
        $product_post_date = get_the_date('Y-m-d H:i:s', $product_id); // Fecha del producto
        $product_post_url = get_permalink($product_id);
    } else {
        $product_post_content = 'Contenido del producto no disponible.';
        $product_post_title = 'Título no disponible';
        $product_post_date = '';
        $product_post_url = '#';
    }

    // Datos del comprador
    $buyer_info = get_userdata($buyer_id);
    if ($buyer_info instanceof WP_User) {
        $buyer_email = $buyer_info->user_email;
        $buyer_profile_pic = get_avatar_url($buyer_id);
        $buyer_name_or_username = $buyer_info->display_name ?: $buyer_info->user_login;
        $buyer_login = $buyer_info->user_login;
        $buyer_profile_url = get_author_posts_url($buyer_id);
    } else {
        $buyer_email = '#';
        $buyer_profile_pic = get_avatar_url(0);
        $buyer_name_or_username = 'Usuario no disponible';
        $buyer_login = 'Usuario no disponible';
        $buyer_profile_url = '#';
    }

    // Datos del vendedor
    $seller_info = get_userdata($seller_id);
    if ($seller_info instanceof WP_User) {
        $seller_email = $seller_info->user_email;
        $seller_profile_pic = get_avatar_url($seller_id);
        $seller_name_or_username = $seller_info->display_name ?: $seller_info->user_login;
        $seller_login = $seller_info->user_login;
        $seller_profile_url = get_author_posts_url($seller_id);
    } else {
        $seller_email = '#';
        $seller_profile_pic = get_avatar_url(0);
        $seller_name_or_username = 'Usuario no disponible';
        $seller_login = 'Usuario no disponible';
        $seller_profile_url = '#';
    }

    // Datos del post relacionado
    $related_post = get_post($related_post_id);
    if ($related_post instanceof WP_Post) {
        $related_post_title = $related_post->post_title;
        $related_post_date = get_the_date('Y-m-d H:i:s', $related_post_id); // Fecha del post relacionado
        $related_post_url = get_permalink($related_post_id);
    } else {
        $related_post_title = 'Post relacionado no disponible';
        $related_post_date = '';
        $related_post_url = '#';
    }

    // Datos propios de la venta
    $corrected_price = $price ? $price / 100 : 0; // Corregir precio y manejar posible valor no numérico
    $date = get_the_date('Y-m-d H:i:s', $postId); // Fecha de la venta
    $content = apply_filters('the_content', get_post_field('post_content', $postId)); // Contenido de la venta

    // Devolver todos los datos en un array
    return [
        'buyer_id' => $buyer_id,
        'seller_id' => $seller_id,
        'related_post_id' => $related_post_id,
        'product_id' => $product_id,
        'product_post_content' => $product_post_content,
        'product_post_title' => $product_post_title,
        'product_post_date' => $product_post_date,
        'product_post_url' => $product_post_url,
        'buyer_email' => $buyer_email,
        'buyer_profile_pic' => $buyer_profile_pic,
        'buyer_name_or_username' => $buyer_name_or_username,
        'buyer_login' => $buyer_login,
        'buyer_profile_url' => $buyer_profile_url,
        'seller_email' => $seller_email,
        'seller_profile_pic' => $seller_profile_pic,
        'seller_name_or_username' => $seller_name_or_username,
        'seller_login' => $seller_login,
        'seller_profile_url' => $seller_profile_url,
        'image_url' => $image_url,
        'audio_url' => $audio_url,
        'price' => $price,
        'corrected_price' => $corrected_price,
        'date' => $date,
        'content' => $content, // Contenido del post 'venta'
        'related_post_title' => $related_post_title,
        'related_post_date' => $related_post_date,
        'related_post_url' => $related_post_url,
        'status_venta' => $status_venta,
        // Añadir cualquier otra variable que se use en el HTML original y no esté aquí
    ];
}
?>