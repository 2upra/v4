<?

function iniciar_sesion()
{
    /*
        <label for="nombre_usuario">Nombre de Usuario</label>
        <input type="text" id="nombre_usuario_login" name="nombre_usuario_login" required class="nombre_usuario"><br>
        <label for="contrasena_usuario">Contraseña:</label>
        <input type="password" id="contrasena_usuario_login" name="contrasena_usuario_login" required class="contrasena_usuario"><br>
        <input class="R0A915 A1" type="submit" name="iniciar_sesion_submit" value="Iniciar sesión">
    */
    // Si el usuario ya está logueado, mostrar un mensaje
    if (is_user_logged_in()) {
        return '<div>Ya has iniciado sesión. ¿Quieres cerrar sesión? <a href="' . wp_logout_url(home_url()) . '">Cerrar sesión</a></div>';
    }

    $mensaje = '';

    // Procesar el formulario de inicio de sesión
    if (isset($_POST['iniciar_sesion_submit'])) {
        $user = wp_signon(array(
            'user_login' => sanitize_user($_POST['nombre_usuario_login']),
            'user_password' => $_POST['contrasena_usuario_login']
        ), false);

        if (!is_wp_error($user)) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);

            // Redirigir al usuario
            if (!headers_sent()) {
                wp_safe_redirect('https://2upra.com');
                exit;
            } else {
                echo "<script>window.location.href='https://2upra.com';</script>";
                exit;
            }
        } else {
            $mensaje = '<div class="error-mensaje">Error al iniciar sesión. Por favor, verifica tus credenciales.</div>';
        }
    }

    ob_start();
?>
    <div class="PUWJVS">
        <form class="CXHMID" action="" method="post">
            <div class="XUSEOO">
                <div class="XYSRLL">

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

// Manejo del callback de Google
function handle_google_callback() {
    // Desactiva la caché para esta acción
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        $client_id = '84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com';
        $client_secret = ($_ENV['GOOGLEAPI']); // Asegúrate de definir esto correctamente en tu entorno
        $redirect_uri = 'https://2upra.com/google-callback';

        // Solicitar el token de acceso a Google
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

        // Obtener la información del usuario desde Google
        $user_info_response = wp_remote_get('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $access_token);
        $user_info = json_decode($user_info_response['body']);

        if ($user_info && isset($user_info->email)) {
            $email = $user_info->email;
            $name = $user_info->name;

            // Verificar si el usuario ya existe
            if ($user = get_user_by('email', $email)) {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                $token = generate_secure_token($user->ID);

                if (!headers_sent()) {
                    if (is_electron_app()) {
                        wp_redirect('https://2upra.com/app?token=' . $token);
                    } else {
                        wp_redirect('https://2upra.com');
                    }
                    exit;
                } else {
                    if (is_electron_app()) {
                        echo "<script>window.location.href='https://2upra.com/app?token=" . $token . "';</script>";
                    } else {
                        echo "<script>window.location.href='https://2upra.com';</script>";
                    }
                    exit;
                }
            } else {
                // Crear un nuevo usuario en WordPress
                $random_password = wp_generate_password();
                $user_id = wp_create_user($name, $random_password, $email);

                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);

                if (!headers_sent()) {
                    wp_redirect('https://2upra.com');
                    exit;
                } else {
                    echo "<script>window.location.href='https://2upra.com';</script>";
                    exit;
                }
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
sucede esto aqui como lo arreglo 
[29-Nov-2024 01:06:42 UTC] WordPress database error Subquery returns more than 1 row for query SELECT user_id FROM wpsg_usermeta WHERE meta_key = 'session_token' AND meta_value = '95ce51f52034cd56166248765471114f3901cce271f5b58bff5b9764aab297c7' AND CAST((SELECT meta_value FROM wpsg_usermeta WHERE user_id = user_id AND meta_key = 'session_token_expiration') AS UNSIGNED) > 1732842402 made by require('wp-blog-header.php'), wp, WP->main, WP->parse_request, do_action_ref_array('parse_request'), WP_Hook->do_action, WP_Hook->apply_filters, rest_api_loaded, WP_REST_Server->serve_request, WP_REST_Server->dispatch, WP_REST_Server->respond_to_request, verify_token_endpoint, verify_secure_token
[29-Nov-2024 01:06:42 UTC] verify_secure_token - token: 95ce51f52034cd56166248765471114f3901cce271f5b58bff5b9764aab297c7 user_id: not found
[29-Nov-2024 01:06:42 UTC] verify_secure_token - invalid token: 95ce51f52034cd56166248765471114f3901cce271f5b58bff5b9764aab297c7
[29-Nov-2024 01:06:42 UTC] verify_token_endpoint - invalid token: 95ce51f52034cd56166248765471114f3901cce271f5b58bff5b9764aab297c7
*/
function generate_secure_token($user_id)
{
    // Genera un token único y define la expiración
    $token = bin2hex(random_bytes(32));
    $expiration = time() + 36000; // 10 horas

    // Elimina posibles entradas duplicadas para este usuario
    delete_user_meta($user_id, 'session_token');
    delete_user_meta($user_id, 'session_token_expiration');

    // Debug: verifica si los metadatos fueron realmente eliminados
    $existing_token = get_user_meta($user_id, 'session_token', true);
    $existing_expiration = get_user_meta($user_id, 'session_token_expiration', true);

    if ($existing_token || $existing_expiration) {
        error_log('generate_secure_token - No se pudieron eliminar las entradas duplicadas.');
        return false; // Devuelve false si no se eliminan correctamente
    }

    // Añade los nuevos valores
    $result_token = update_user_meta($user_id, 'session_token', $token);
    $result_expiration = update_user_meta($user_id, 'session_token_expiration', $expiration);

    error_log('generate_secure_token - user_id: ' . $user_id . ' token: ' . $token . ' expiration: ' . $expiration  . ' result_token: ' . ($result_token ? 'true' : 'false') . ' result_expiration: ' . ($result_expiration ? 'true' : 'false'));

    return $token; // Devuelve el token generado
}

function verify_secure_token($token)
{
    global $wpdb;

    // Cambiamos la subconsulta para usar MAX() y asegurarnos de que solo devuelva un valor
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id 
         FROM $wpdb->usermeta 
         WHERE meta_key = 'session_token' 
           AND meta_value = %s 
           AND CAST((
               SELECT MAX(meta_value) 
               FROM $wpdb->usermeta 
               WHERE user_id = $wpdb->usermeta.user_id 
                 AND meta_key = 'session_token_expiration'
           ) AS UNSIGNED) > %d",
        $token,
        time()
    ));

    // Log para depuración
    error_log('verify_secure_token - token: ' . $token . ' user_id: ' . ($user_id ? $user_id : 'not found'));

    // Devuelve el ID del usuario si es válido, de lo contrario devuelve false
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
    // Obtiene el token enviado en el request
    $token = $request->get_param('token');

    // Verifica el token
    $user_id = verify_secure_token($token);

    if ($user_id) {
        error_log('verify_token_endpoint - token: ' . $token . ' user_id: ' . $user_id);
        return new WP_REST_Response(array('user_id' => $user_id, 'status' => 'valid'), 200);
    } else {
        error_log('verify_token_endpoint - invalid token: ' . $token);
        return new WP_REST_Response(array('message' => 'Token inválido', 'status' => 'invalid'), 401);
    }
}
