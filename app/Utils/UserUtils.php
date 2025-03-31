<?php

function usuarioEsAdminOPro($user_id)
{
    // Verificar que el ID de usuario sea válido
    if (empty($user_id) || !is_numeric($user_id)) {
        ////guardarLog("usuarioEsAdminOPro: Error - ID de usuario inválido.");
        return false;
    }

    // Obtener el objeto usuario
    $user = get_user_by('id', $user_id);

    // Verificar si el usuario existe
    if (!$user) {
        ////guardarLog("usuarioEsAdminOPro: Error - Usuario no encontrado para el ID: " . $user_id);
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($user->roles)) {
        ////guardarLog("usuarioEsAdminOPro: Error - Usuario sin roles asignados. ID: " . $user_id);
        ////guardarLog("usuarioEsAdminOPro: Información del usuario - " . print_r($user, true));
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $user->roles)) {
        ////guardarLog("usuarioEsAdminOPro: Usuario es administrador. ID: " . $user_id);
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($user_id, 'pro', true);
    if (!empty($is_pro)) {
        ////guardarLog("usuarioEsAdminOPro: Usuario tiene la meta 'pro'. ID: " . $user_id);
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    ////guardarLog("usuarioEsAdminOPro: Usuario no es administrador ni tiene la meta 'pro'. ID: " . $user_id);
    return false;
}

function saberSi($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $last_run = get_user_meta($user_id, 'ultima_ejecucion_saber', true);
    $current_time = current_time('timestamp');

    if ($last_run && ($current_time - $last_run < 1)) {
        return; 
    }
    update_user_meta($user_id, 'ultima_ejecucion_saber', $current_time);

    //Saber si le gusta una rola
    $liked_posts = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    if (empty($liked_posts)) {
        update_user_meta($user_id, 'leGustaAlMenosUnaRola', false);
        return;
    }

    $rola_posts = get_posts(array(
        'post__in' => $liked_posts,
        'meta_query' => array(
            array(
                'key' => 'rola',
                'value' => 'true',
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    $le_gusta_rola = !empty($rola_posts);
    update_user_meta($user_id, 'leGustaAlMenosUnaRola', $le_gusta_rola);
}
