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




function manejar_reproducciones_y_oyentes(WP_REST_Request $request) {
    $audioSrc = $request->get_param('src');
    $postId = $request->get_param('post_id');
    $artistId = $request->get_param('artist');
    $userId = $request->get_param('user_id');

    guardar_log("Solicitud recibida: audioSrc=$audioSrc, postId=$postId, artistId=$artistId, userId=$userId");

    // Manejar reproducción
    if ($postId) {
        $reproducciones_key = 'reproducciones_post';
        $current_count = (int) get_post_meta($postId, $reproducciones_key, true);
        update_post_meta($postId, $reproducciones_key, $current_count + 1);
        guardar_log("Reproducción registrada para el post ID $postId");
    }

    // Manejar oyente
    if ($artistId && $userId) {
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
        guardar_log("Oyente actualizado para el artista ID $artistId");
    }

    return new WP_REST_Response(['message' => 'Datos procesados correctamente'], 200);
}

function registrar_endpoints_api() {
    register_rest_route('miplugin/v1', '/reproducciones-y-oyentes/', array(
        'methods' => 'POST',
        'callback' => 'manejar_reproducciones_y_oyentes',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'registrar_endpoints_api');

function contar_oyentes_unicos($artistId) {
    $meta_key = 'oyentes_' . $artistId;
    $oyentes = get_option($meta_key, []);
    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
    return count(array_filter($oyentes, function($last_heard) use ($thirty_days_ago) {
        return $last_heard >= $thirty_days_ago;
    }));
}

function cargar_reproductor_js() {
    wp_enqueue_script('reproductor-audio', get_template_directory_uri() . '/js/reproductor.js', [], '2.1.2', true);
}
add_action('wp_enqueue_scripts', 'cargar_reproductor_js');
