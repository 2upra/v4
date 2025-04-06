<?

/*
2.5 segundos
*/

// Refactor(Org): Moved function obtenerInteresesUsuario to app/Services/UserService.php
// Refactor(Exec): Moved functions obtenerDatosFeed and obtenerDatosFeedConCache to app/Services/FeedService.php

// Funcion obtenerUsuariosSeguidos movida a app/Services/FollowService.php

// Refactor(Org): Moved function comprobarConexionBD to app/Utils/DatabaseUtils.php

// Refactor(Exec): Moved function validarUsuario to app/Helpers/UserHelper.php

// Funcion obtenerInteresesUsuario movida a app/Services/UserService.php

function vistasDatos($userId) {
    $tiempoInicio = microtime(true);
    $vistas = get_user_meta($userId, 'vistas_posts', true);
    //rendimientolog("[vistasDatos] Tiempo para obtener 'vistas': " . (microtime(true) - $tiempoInicio) . " segundos");
    return $vistas;
}

function obtenerIdsPostsRecientes() {
    $tiempoInicio = microtime(true);
    $args = [
        'post_type'      => 'social_post',
        'posts_per_page' => 50000,
        'date_query'     => [
            'after' => date('Y-m-d', strtotime('-365 days'))
        ],
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ];
    $postsIds = get_posts($args);
    //rendimientolog("[obtenerIdsPostsRecientes] Tiempo para obtener \$postsIds: " . (microtime(true) - $tiempoInicio) . " segundos");
    return $postsIds;
}


function obtenerMetadatosPosts($postsIds) {
    global $wpdb;
    $tiempoInicio = microtime(true);
    
    $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));
    $metaKeys = ['datosAlgoritmo', 'Verificado', 'postAut', 'artista', 'fan', 'nombreOriginal'];
    $metaKeysPlaceholders = implode(',', array_fill(0, count($metaKeys), '%s'));

    $sqlMeta = "
        SELECT post_id, meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key IN ($metaKeysPlaceholders) AND post_id IN ($placeholders)
    ";
    $preparedSqlMeta = $wpdb->prepare($sqlMeta, array_merge($metaKeys, $postsIds));
    $metaResultados = $wpdb->get_results($preparedSqlMeta);

    if ($wpdb->last_error) {
        //guardarLog("[obtenerMetadatosPosts] Error: Fallo al obtener metadata: " . $wpdb->last_error);
    }
    //rendimientolog("[obtenerMetadatosPosts] Tiempo para obtener \$metaResultados: " . (microtime(true) - $tiempoInicio) . " segundos");

    $metaData = [];
    foreach ($metaResultados as $meta) {
        $metaData[$meta->post_id][$meta->meta_key] = $meta->meta_value;
    }
    //rendimientolog("[obtenerMetadatosPosts] Tiempo para procesar \$metaResultados: " . (microtime(true) - $tiempoInicio) . " segundos");

    return $metaData;
}

function procesarMetadatosRoles($metaData) {
    $tiempoInicio = microtime(true);
    $metaRoles = [];
    foreach ($metaData as $postId => $meta) {
        $metaRoles[$postId] = [
            'artista' => isset($meta['artista']) ? filter_var($meta['artista'], FILTER_VALIDATE_BOOLEAN) : false,
            'fan'     => isset($meta['fan']) ? filter_var($meta['fan'], FILTER_VALIDATE_BOOLEAN) : false,
        ];
    }
    //rendimientolog("[procesarMetadatosRoles] Tiempo para procesar \$metaRoles: " . (microtime(true) - $tiempoInicio) . " segundos");
    return $metaRoles;
}

// Refactor(Org): Función obtenerLikesPorPost() movida a app/Services/LikeService.php


function obtenerDatosBasicosPosts($postsIds) {
    global $wpdb;
    $tiempoInicio = microtime(true);

    $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));

    $sqlPosts = "
        SELECT ID, post_author, post_date, post_content
        FROM {$wpdb->posts}
        WHERE ID IN ($placeholders)
    ";
    $postsResultados = $wpdb->get_results($wpdb->prepare($sqlPosts, $postsIds), OBJECT_K);

    if ($wpdb->last_error) {
        //guardarLog("[obtenerDatosBasicosPosts] Error: Fallo al obtener posts: " . $wpdb->last_error);
    }
    //rendimientolog("[obtenerDatosBasicosPosts] Tiempo para obtener \$postsResultados: " . (microtime(true) - $tiempoInicio) . " segundos");

    return $postsResultados;
}

function procesarContenidoPosts($postsResultados) {
    $tiempoInicio = microtime(true);
    $postContenido = [];
    foreach ($postsResultados as $post) {
        $postContenido[$post->ID] = $post->post_content;
    }
    //rendimientolog("[procesarContenidoPosts] Tiempo para procesar \$postContenido: " . (microtime(true) - $tiempoInicio) . " segundos");
    return $postContenido;
}
