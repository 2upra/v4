<?php

// Contiene funciones auxiliares relacionadas con las suscripciones de usuario (ej: renderizar botones).

// Refactor(Org): Mueve función botonSuscribir() desde app/Content/Posts/View/componentPost.php
//BOTON PARA SUSCRIBIRSE
function botonSuscribir($autorId, $author_name, $subscription_price_id = 'price_1OqGjlCdHJpmDkrryMzL0BCK')
{
    ob_start();
    $current_user = wp_get_current_user();
?>
    <button
        class="ITKSUG"
        data-offering-user-id="<?php echo esc_attr($autorId); ?>"
        data-offering-user-login="<?php echo esc_attr($author_name); ?>"
        data-offering-user-email="<?php echo esc_attr(get_the_author_meta('user_email', $autorId)); ?>"
        data-subscriber-user-id="<?php echo esc_attr($current_user->ID); ?>"
        data-subscriber-user-login="<?php echo esc_attr($current_user->user_login); ?>"
        data-subscriber-user-email="<?php echo esc_attr($current_user->user_email); ?>"
        data-price="<?php echo esc_attr($subscription_price_id); ?>"
        data-url="<?php echo esc_url(get_permalink()); ?>">
        Suscribirse
    </button>

<?php

    return ob_get_clean();
}
