<?php
// Incluir el servicio de ventas
require_once 'app/Services/VentaService.php';

get_header(); // Incluye el header
?>


<?php
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
            // Refactor: Lógica de obtención de datos movida a VentaService.php
            $datos_venta = obtenerDatosVenta(get_the_ID());
            extract($datos_venta);

            // El bloque HTML ahora usa las variables extraídas de $datos_venta
            echo <<<HTML
            <div class='venta-item'>
                <div class='venta-header'>
                    <div class='venta-buyer'>
                        <div class='imagen-buyer'>
                            <a href='$buyer_profile_url'><img src='$buyer_profile_pic' alt='Buyer profile pic'></a>
                        </div>
                        <div class='name-buyer'>
                            <a href='$buyer_profile_url'><span>{$buyer_login}</span></a>
                            <p class='type-buyer'>Comprador</p>
                        </div>
                    </div>
                    <div class='venta-seller'>
                        <div class='name-seller'>
                            <a href='$seller_profile_url'><span>{$seller_login}</span></a>
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
                        <div id="waveform-".get_the_ID()."" class="waveform-container-venta" data-audio-url="$audio_url">
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