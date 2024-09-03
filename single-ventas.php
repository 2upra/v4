<?php get_header(); // Incluye el header ?>


<?php
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
            $buyer_id = get_post_meta(get_the_ID(), 'buyer_id', true);
            $seller_id = get_post_meta(get_the_ID(), 'seller_id', true);
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
            $audio_url = get_post_meta(get_the_ID(), 'audio_url', true);
            $price = get_post_meta(get_the_ID(), 'price', true);
            $corrected_price = $price / 100;
            $date = get_the_date('Y-m-d H:i:s');
            $buyer_email = $buyer_info->user_email;
            $seller_email = $seller_info->user_email;
            $buyer_profile_pic = get_avatar_url($buyer_id);
            $seller_profile_pic = get_avatar_url($seller_id);
            $buyer_name_or_username = $buyer_info->display_name ? $buyer_info->display_name : $buyer_info->user_login;
            $seller_name_or_username = $seller_info->display_name ? $seller_info->display_name : $seller_info->user_login;
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
                            <a href='$buyer_profile_url'><span>{$buyer_info->user_login}</span></a>
                            <p class='type-buyer'>Comprador</p>
                        </div>
                    </div>
                    <div class='venta-seller'>
                        <div class='name-seller'>
                            <a href='$seller_profile_url'><span>{$seller_info->user_login}</span></a>
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
    endwhile;
	else :
    // No se encontraron posts
endif;
?>


    	<?php get_footer(); // Incluye el footer ?>