<?

/*
tengo este codigo en mi servidor, pero cuando doy click en la app, no funciona (si funciona en la web y tiene que seguir funcionando en la web), que tengo que hacer
por favor no confundas la app de electron que es diferente con la app de android que estoy haciendo
*/


function iniciar_sesion()
{

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

function handle_google_callback() {
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

                // Limpiar el nombre para crear un user_login válido
                $user_login = sanitize_user(str_replace(' ', '', strtolower($name)), true);
                
                // Asegurarse de que el user_login sea único
                $original_user_login = $user_login;
                $counter = 1;
                while (username_exists($user_login)) {
                    $user_login = $original_user_login . $counter;
                    $counter++;
                }

                $user_id = wp_create_user($user_login, $random_password, $email);

                if (is_wp_error($user_id)) {
                    echo 'Error al crear el usuario.';
                    return;
                }

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

add_action('wp_head', function () {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        echo "<script>window.userId = {$user_id};</script>";
    }
});

// Agregar el token de Firebase como un campo de usuario
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/save-token', array(
        'methods' => 'POST',
        'callback' => 'save_firebase_token',
        'permission_callback' => function () {
            return is_user_logged_in(); // Asegúrate de que solo usuarios logueados puedan guardar un token
        },
    ));
});

function save_firebase_token($request) {
    $user_id = get_current_user_id();
    $firebase_token = sanitize_text_field($request->get_param('token'));

    if (!$firebase_token) {
        return new WP_Error('no_token', 'El token es requerido.', array('status' => 400));
    }

    // Guardar el token en el meta del usuario
    update_user_meta($user_id, 'firebase_token', $firebase_token);

    return array('success' => true, 'message' => 'Token guardado correctamente.');
}

function send_push_notification($user_id, $title, $message) {
    // Obtener el token de Firebase del usuario
    $firebase_token = get_user_meta($user_id, 'firebase_token', true);

    if (!$firebase_token) {
        return new WP_Error('no_token', 'El usuario no tiene un token de Firebase.', array('status' => 404));
    }

    // Datos para enviar a Firebase
    $fields = array(
        'to' => $firebase_token,
        'notification' => array(
            'title' => $title,
            'body' => $message,
        ),
    );

    // Configuración de la solicitud
    $headers = array(
        'Authorization: key=YOUR_SERVER_KEY', // Sustituye con tu clave de servidor de Firebase
        'Content-Type: application/json',
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

add_action('init', function () {
    $user_id = 355; // ID del usuario a notificar
    $title = 'Hola!';
    $message = 'Esta es una notificación personalizada.';

    $response = send_push_notification($user_id, $title, $message);
    error_log($response);

});
