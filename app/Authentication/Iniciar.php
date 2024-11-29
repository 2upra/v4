<?

function iniciar_sesion()
{
    if (is_user_logged_in()) {
        return '<div>Ya has iniciado sesión. ¿Quieres cerrar sesión? <a href="' . wp_logout_url(home_url()) . '">Cerrar sesión</a></div>';
    }

    $mensaje = '';
    if (isset($_POST['iniciar_sesion_submit'])) {
        $user = wp_signon(array(
            'user_login' => sanitize_user($_POST['nombre_usuario_login']),
            'user_password' => $_POST['contrasena_usuario_login']
        ), false);

        if (!is_wp_error($user)) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);

            // Clean output buffer
            ob_clean();

            wp_safe_redirect('https://2upra.com');
            exit;
        } else {
            $mensaje = '<div class="error-mensaje">Error al iniciar sesión. Por favor, verifica tus credenciales.</div>';
        }
    }

    ob_start();
?>

    <div class="PUWJVS">
        <form class="CXHMID" action="" method="post">
            <div class="XUSEOO">
                <label for="nombre_usuario">Nombre de Usuario</label>
                <input type="text" id="nombre_usuario_login" name="nombre_usuario_login" required class="nombre_usuario"><br>
                <label for="contrasena_usuario">Contraseña:</label>
                <input type="password" id="contrasena_usuario_login" name="contrasena_usuario_login" required class="contrasena_usuario"><br>
                <div class="XYSRLL">
                    <input class="R0A915 A1" type="submit" name="iniciar_sesion_submit" value="Iniciar sesión">
                    <button type="button" class="R0A915 botonprincipal A1 A2" id="google-login-btn"><?php echo $GLOBALS['Google']; ?>Iniciar sesión con Google</button>

                    <script>
                        document.getElementById('google-login-btn').addEventListener('click', function() {
                            window.location.href = 'https://accounts.google.com/o/oauth2/auth?' +
                                'client_id=84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com&' +
                                'redirect_uri=https://2upra.com/google-callback&' +
                                'response_type=code&' +
                                'scope=email profile';
                        });
                    </script>

                    <button type="button" class="R0A915 A1 boton-cerrar">Volver</button>
                    <p><a href="https://2upra.com/tc/">Política de privacidad</a></p>
                </div>
                <?php echo $mensaje; ?>
            </div>
        </form>
        <div class="RFZJUH">
            <div class="HPUYVS" id="fondograno"><?php echo $GLOBALS['iconologo1']; ?></div>
        </div>
    </div>

<?php
    return ob_get_clean();
}


function handle_google_callback()
{
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        $client_id = '84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com';
        $client_secret = ($_ENV['GOOGLEAPI']);
        $redirect_uri = 'https://2upra.com/google-callback';
        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
            )
        ));

        if (is_wp_error($response)) {
            echo 'Error en la autenticación con Google.';
            return;
        }

        $token = json_decode($response['body']);
        $access_token = $token->access_token;
        $user_info_response = wp_remote_get('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $access_token);
        $user_info = json_decode($user_info_response['body']);

        if ($user_info && isset($user_info->email)) {
            $email = $user_info->email;
            $name = $user_info->name;

            if ($user = get_user_by('email', $email)) {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                $token = generate_secure_token($user->ID);
                if (is_electron_app()) {
                    wp_redirect('https://2upra.com/app?token=' . $token);
                } else {
                    wp_redirect('https://2upra.com');
                }
                exit;
            } else {
                $random_password = wp_generate_password();
                $user_id = wp_create_user($name, $random_password, $email);
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                wp_redirect('https://2upra.com');
                exit;
            }
        }
    }
}

add_action('init', 'handle_google_callback');

function is_electron_app()
{
    return isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true';
}

/*
sucede esto aqui 

[29-Nov-2024 01:04:02 UTC] WordPress database error Subquery returns more than 1 row for query SELECT user_id FROM wpsg_usermeta WHERE meta_key = 'session_token' AND meta_value = 'c0444a3286ee5adbb579c6712c7bd0a54a1babdae0f3545a59db0ae204a3fb4a' AND CAST((SELECT meta_value FROM wpsg_usermeta WHERE user_id = user_id AND meta_key = 'session_token_expiration') AS UNSIGNED) > 1732842242 made by require('wp-blog-header.php'), wp, WP->main, WP->parse_request, do_action_ref_array('parse_request'), WP_Hook->do_action, WP_Hook->apply_filters, rest_api_loaded, WP_REST_Server->serve_request, WP_REST_Server->dispatch, WP_REST_Server->respond_to_request, verify_token_endpoint, verify_secure_token
[29-Nov-2024 01:04:02 UTC] verify_secure_token - token: c0444a3286ee5adbb579c6712c7bd0a54a1babdae0f3545a59db0ae204a3fb4a user_id: not found
[29-Nov-2024 01:04:02 UTC] verify_secure_token - invalid token: c0444a3286ee5adbb579c6712c7bd0a54a1babdae0f3545a59db0ae204a3fb4a
[29-Nov-2024 01:04:02 UTC] verify_token_endpoint - invalid token: c0444a3286ee5adbb579c6712c7bd0a54a1babdae0f3545a59db0ae204a3fb4a
*/
function generate_secure_token($user_id)
{
    $token = bin2hex(random_bytes(32));
    $expiration = time() + 36000;

    // Remove existing entries to prevent duplicates
    delete_user_meta($user_id, 'session_token');
    delete_user_meta($user_id, 'session_token_expiration');

    // Add new entries
    $result_token = update_user_meta($user_id, 'session_token', $token);
    $result_expiration = update_user_meta($user_id, 'session_token_expiration', $expiration);

    error_log('generate_secure_token - user_id: ' . $user_id . ' token: ' . $token . ' expiration: ' . $expiration  . ' result_token: ' . ($result_token ? 'true' : 'false') . ' result_expiration: ' . ($result_expiration ? 'true' : 'false'));

    return $token;
}

function verify_secure_token($token)
{
    global $wpdb;
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'session_token' AND meta_value = %s AND CAST((SELECT meta_value FROM $wpdb->usermeta WHERE user_id = user_id AND meta_key = 'session_token_expiration') AS UNSIGNED) > %d",
        $token,
        time()
    ));

    error_log('verify_secure_token - token: ' . $token . ' user_id: ' . ($user_id ? $user_id : 'not found'));

    if ($user_id) {
        return $user_id;
    }

    error_log('verify_secure_token - invalid token: ' . $token);
    return false;
}

add_action('rest_api_init', function () {
    register_rest_route('2upra/v1', '/verify_token', array(
        'methods' => 'POST',
        'callback' => 'verify_token_endpoint',
    ));
});

function verify_token_endpoint(WP_REST_Request $request)
{
    $token = $request->get_param('token');
    $user_id = verify_secure_token($token);

    if ($user_id) {
        error_log('verify_token_endpoint - token: ' . $token . ' user_id: ' . $user_id);
        return new WP_REST_Response(array('user_id' => $user_id, 'status' => 'valid'), 200);
    } else {
        error_log('verify_token_endpoint - invalid token: ' . $token);
        return new WP_REST_Response(array('message' => 'Token inválido', 'status' => 'invalid'), 401);
    }
}
