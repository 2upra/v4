<?php

// Refactor(Org): Mueve función botonColeccion() de UIHelper a CollectionHelper
function botonColeccion($postId)
{

    $extraClass = '';
    if (is_user_logged_in()) {
        $userId = get_current_user_id();
        $coleccion = get_user_meta($userId, 'samplesGuardados', true);
        if (is_array($coleccion) && isset($coleccion[$postId])) {
            $extraClass = ' colabGuardado';
        }
    }

    ob_start();
?>
    <div class="ZAQIBB botonColeccion<? echo esc_attr($extraClass); ?>">
        <button class="botonColeccionBtn" aria-label="Guardar sonido" data-post_id="<? echo esc_attr($postId); ?>" data-nonce="<? echo wp_create_nonce('colec_nonce'); ?>">
            <? echo isset($GLOBALS['iconoGuardar']) ? $GLOBALS['iconoGuardar'] : ''; // Verifica si $GLOBALS['iconoGuardar'] está definida 
            ?>
        </button>
    </div>
<?
    return ob_get_clean();
}
