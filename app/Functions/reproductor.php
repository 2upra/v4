<?

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
                        <? echo $GLOBALS['anterior']; ?>
                    </button>
                    <button class="play-btn">
                        <? echo $GLOBALS['play']; ?>
                    </button>
                    <button class="pause-btn" style="display: none;">
                        <? echo $GLOBALS['pause']; ?>
                    </button>
                    <button class="next-btn">
                        <? echo $GLOBALS['siguiente']; ?>
                    </button>
                    <div class="BSUXDA">
                        <button class="JMFCAI">
                            <? echo $GLOBALS['volumen']; ?>
                        </button>
                        <div class="TGXRDF">
                            <input type="range" class="volume-control" min="0" max="1" step="0.01" value="1">
                        </div>
                    </div>
                    <button class="PCNLEZ">
                        <? echo $GLOBALS['cancelicon']; ?>
                    </button>
                </div>

            </div>

        </div>
    </div>
<?

}
add_action('wp_footer', 'reproductor');

function reproducciones(WP_REST_Request $request) {
    // Limitar tasa de solicitudes
    if (!limitador()) {
        return new WP_Error('rate_limit_exceeded', 'Límite de solicitudes excedido', array('status' => 429));
    }

    // Validar y sanitizar entradas
    $audioSrc = sanitize_text_field($request->get_param('src'));
    $postId = absint($request->get_param('post_id'));
    $artistId = absint($request->get_param('artist'));

    // Obtener la dirección IP del cliente
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    // Validar que el post y el artista existan
    if (!get_post($postId)) {
        return new WP_Error('invalid_post', 'El post no existe', array('status' => 400));
    }
    if (!get_user_by('ID', $artistId)) {
        return new WP_Error('invalid_artist', 'El artista no es válido', array('status' => 400));
    }

    //guardarLog("Solicitud recibida: audioSrc=$audioSrc, postId=$postId, artistId=$artistId, ipAddress=$ipAddress");

    // Manejar reproducción
    if ($postId) {
        $reproducciones_key = 'reproducciones_post';
        $current_count = (int) get_post_meta($postId, $reproducciones_key, true);
        update_post_meta($postId, $reproducciones_key, $current_count + 1);
        //guardarLog("Reproducción registrada para el post ID $postId");
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

        // Actualizar oyente basado en IP
        $oyentes[$ipAddress] = $current_time;
        update_option($meta_key, $oyentes);
        //guardarLog("Oyente actualizado para el artista ID $artistId desde IP $ipAddress");
    }

    return new WP_REST_Response(['message' => 'Datos procesados correctamente'], 200);
}

// Registrar la ruta de la API
function reproduccionesAPI() {
    register_rest_route('miplugin/v1', '/reproducciones-y-oyentes/', array(
        'methods' => 'POST',
        'callback' => 'reproducciones',
        'permission_callback' => function() {
            return true; // Permitir todas las solicitudes sin autenticación
        }
    ));
}
add_action('rest_api_init', 'reproduccionesAPI');

// Función de limitación de tasa basada en IP
function limitador() {
    // Obtener la dirección IP del cliente
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $transient_name = 'rate_limit_' . $ip_address;
    $rate_limit = get_transient($transient_name);

    if (false === $rate_limit) {
        set_transient($transient_name, 1, 5); // 1 solicitud por 20 segundos
    } elseif ($rate_limit >= 15) { // Máximo 5 solicitudes por minuto
        return false;
    } else {
        set_transient($transient_name, $rate_limit + 1, 60);
    }

    return true;
}



