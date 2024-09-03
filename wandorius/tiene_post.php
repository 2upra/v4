<?php 

//SI TIENE ROLAS ENVIADAS 
function actualizar_meta_usuario_tiene_posts($user_id, $filtro) {

    $query_args = array(
        'post_type' => 'social_post',
        'posts_per_page' => -1, 
        'author' => $user_id,
        'meta_query' => array(
            array(
                'key' => $filtro,
                'value' => '1',
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($query_args);
    if ($query->have_posts()) {
        update_user_meta($user_id, 'tiene_posts_en_'.$filtro, true);
    } else {
        update_user_meta($user_id, 'tiene_posts_en_'.$filtro, false);
    }
    wp_reset_postdata();
}
$user_id = get_current_user_id();
$filtro = 'rola';
actualizar_meta_usuario_tiene_posts($user_id, $filtro);


//SI TIENE COLABS PENDIENTE
function actualizar_meta_usuario_tiene_colabs_pendientes($user_id) {

    $query_args = array(
        'post_type' => 'colab',
        'posts_per_page' => -1,
        'author' => $user_id,
        'post_status' => 'publish', // Asegurarse de que solo se consideren publicaciones publicadas
        'meta_query' => array(
            array(
                'key' => 'colab_status',
                'value' => 'pendiente',
                'compare' => 'LIKE'
            )
        )
    );

    $query = new WP_Query($query_args);
    if ($query->have_posts()) {
        update_user_meta($user_id, 'tiene_colabs_pendientes', true);
    } else {
        update_user_meta($user_id, 'tiene_colabs_pendientes', false);
    }
    wp_reset_postdata();
}

$user_id = get_current_user_id();
actualizar_meta_usuario_tiene_colabs_pendientes($user_id);