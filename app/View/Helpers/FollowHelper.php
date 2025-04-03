<?php
// Funciones movidas desde app/Content/Posts/View/componentPost.php

//BOTON DE SEGUIR
function botonseguir($autorId)
{
    $autorId = (int) $autorId;
    $usuarioActual = get_current_user_id();

    if ($usuarioActual === 0) {
        return ''; // Usuario no autenticado
    }

    // Si el usuario está viendo su propio perfil, añadimos una clase de deshabilitado
    if ($usuarioActual === $autorId) {
        ob_start();
?>
        <button class="mismo-usuario" disabled>

        </button>
    <?
        return ob_get_clean();
    }

    $siguiendo = get_user_meta($usuarioActual, 'siguiendo', true);
    $es_seguido = is_array($siguiendo) && in_array($autorId, $siguiendo);

    $clase_boton = $es_seguido ? 'dejar-de-seguir' : 'seguir';
    $icono_boton = $es_seguido ? $GLOBALS['iconorestar'] : $GLOBALS['iconosumar'];

    ob_start();
    ?>
    <button class="<? echo esc_attr($clase_boton); ?>"
        data-seguidor-id="<? echo esc_attr($usuarioActual); ?>"
        data-seguido-id="<? echo esc_attr($autorId); ?>">
        <? echo $icono_boton; ?>
    </button>
<?
    return ob_get_clean();
}

function botonSeguirPerfilBanner($autorId)
{

    $autorId = (int) $autorId;
    $usuarioActual = get_current_user_id();
    if ($usuarioActual === 0 || $usuarioActual === $autorId) {
        return '';
    }
    $siguiendo = get_user_meta($usuarioActual, 'siguiendo', true);
    $siguiendo = is_array($siguiendo) ? $siguiendo : array();
    $es_seguido = in_array($autorId, $siguiendo);
    $clase_boton = $es_seguido ? 'dejar-de-seguir' : 'seguir';
    $texto_boton = $es_seguido ? 'Dejar de seguir' : 'Seguir';


    ob_start();
?>
    <button class="borde <? echo esc_attr($clase_boton); ?>"
        data-seguidor-id="<? echo esc_attr($usuarioActual); ?>"
        data-seguido-id="<? echo esc_attr($autorId); ?>">
        <? echo esc_html($texto_boton); ?>
    </button>
<?
    return ob_get_clean();
}
