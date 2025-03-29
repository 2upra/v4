<?php

function imagenPerfil($user_id)
{
    $imagenPerfilId = get_user_meta($user_id, 'imagen_perfil_id', true);
    if (!empty($imagenPerfilId)) {
        $url = wp_get_attachment_url($imagenPerfilId);
    } else {
        $url = 'https://2upra.com/wp-content/uploads/2024/05/perfildefault.jpg';
    }
    return img($url);
}
