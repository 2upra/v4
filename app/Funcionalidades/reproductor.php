<?php

function reproductor()
{
?>

    <div class="TMLIWT" style="display: none;">

        <audio class="GSJJHK" style="display:none;"></audio>
        <div class="GPFFDR">

            <div class="CMJUXB">
                <div class="progress-container">
                    <div class="progress-bar"></div>
                </div>
            </div>

            <div class="CMJUXC">
                <div class="HOYBKW">
                    <img class="LWXUER">
                </div>
                <div class="XKPMGD">
                    <p class="tituloR"></p>
                    <p class="AutorR"></p>
                </div>
                <div class="PQWXDA">
                    <button class="prev-btn">
                        <?php echo $GLOBALS['anterior']; ?>
                    </button>
                    <button class="play-btn">
                        <?php echo $GLOBALS['play']; ?>
                    </button>
                    <button class="pause-btn" style="display: none;">
                        <?php echo $GLOBALS['pause']; ?>
                    </button>
                    <button class="next-btn">
                        <?php echo $GLOBALS['siguiente']; ?>
                    </button>
                    <div class="BSUXDA">
                        <button class="JMFCAI">
                            <?php echo $GLOBALS['volumen']; ?>
                        </button>
                        <div class="TGXRDF">
                            <input type="range" class="volume-control" min="0" max="1" step="0.01" value="1">
                        </div>
                    </div>
                    <button class="PCNLEZ">
                        <?php echo $GLOBALS['cancelicon']; ?>
                    </button>
                </div>

            </div>

        </div>
    </div>
<?php

}
add_action('wp_footer', 'reproductor');

function reproducciones(WP_REST_Request $request) {
    // Verificar nonce
    if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
        return new WP_Error('invalid_nonce', 'Nonce inválido', array('status' => 403));
    }

    // Limitar tasa de solicitudes
    if (!limitador()) {
        return new WP_Error('rate_limit_exceeded', 'Límite de solicitudes excedido', array('status' => 429));
    }

    // Validar y sanitizar entradas
    $audioSrc = sanitize_text_field($request->get_param('src'));
    $postId = absint($request->get_param('post_id'));
    $artistId = absint($request->get_param('artist'));
    $userId = get_current_user_id();

    if (!$userId) {
        return new WP_Error('unauthorized', 'Usuario no autenticado', array('status' => 401));
    }

    // Validar que el post y el artista existan
    if (!get_post($postId)) {
        return new WP_Error('invalid_post', 'El post no existe', array('status' => 400));
    }
    if (!get_user_by('ID', $artistId)) {
        return new WP_Error('invalid_artist', 'El artista no es válido', array('status' => 400));
    }

    guardarLog("Solicitud recibida: audioSrc=$audioSrc, postId=$postId, artistId=$artistId, userId=$userId");

    // Manejar reproducción
    if ($postId) {
        $reproducciones_key = 'reproducciones_post';
        $current_count = (int) get_post_meta($postId, $reproducciones_key, true);
        update_post_meta($postId, $reproducciones_key, $current_count + 1);
        guardarLog("Reproducción registrada para el post ID $postId");
    }

    // Manejar oyente
    if ($artistId) {
        $meta_key = 'oyentes_' . $artistId;
        $oyentes = get_option($meta_key, []);
        $current_time = current_time('mysql', 1);
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

        // Limpiar oyentes antiguos
        $oyentes = array_filter($oyentes, function($last_heard) use ($thirty_days_ago) {
            return $last_heard >= $thirty_days_ago;
        });

        $oyentes[$userId] = $current_time;
        update_option($meta_key, $oyentes);
        guardarLog("Oyente actualizado para el artista ID $artistId");
    }

    return new WP_REST_Response(['message' => 'Datos procesados correctamente'], 200);
}

function reproduccionesAPI() {
    register_rest_route('miplugin/v1', '/reproducciones-y-oyentes/', array(
        'methods' => 'POST',
        'callback' => 'reproducciones',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
}
add_action('rest_api_init', 'reproduccionesAPI');

function limitador() {
    $user_id = get_current_user_id();
    $transient_name = 'rate_limit_' . $user_id;
    $rate_limit = get_transient($transient_name);

    if (false === $rate_limit) {
        set_transient($transient_name, 1, 20); // 1 solicitud por 20 seg
    } elseif ($rate_limit >= 5) { // Máximo 5 solicitudes por minuto
        return false;
    } else {
        set_transient($transient_name, $rate_limit + 1, 60);
    }

    return true;
}



