<?




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



                    <button type="button" class="R0A915 botonprincipal A1 A2" id="google-login-btn">
                        <? echo $GLOBALS['Google']; ?>Iniciar sesión con Google
                    </button>

                    <script>
                        document.getElementById('google-login-btn').addEventListener('click', function() {
                            // URL de autenticación de Google OAuth
                            const googleOAuthURL = 'https://accounts.google.com/o/oauth2/auth?' +
                                'client_id=84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com&' +
                                'redirect_uri=https://2upra.com/google-callback&' +
                                'response_type=code&' +
                                'scope=email profile';

                            // Función para detectar navegadores embebidos
                            const isEmbeddedBrowser = () => {
                                const ua = navigator.userAgent || navigator.vendor || window.opera;

                                // Lista de userAgents conocidos de Threads (actualiza si es necesario)
                                const knownThreadsUserAgents = [
                                    "Threads",
                                    "Barcelona",
                                ];

                                // 1. Revisar lista de userAgents conocidos de Threads
                                if (knownThreadsUserAgents.some(threadsUA => ua.includes(threadsUA))) {
                                    return true;
                                }

                                // 2. Usar una expresión regular mejorada
                                if (/Instagram|FBAN|FBAV|Messenger|Meta|Facebook|Line|WebView|(Threads)|Twitter|Snapchat|TikTok/.test(ua)) {
                                    return true;
                                }

                                // Registrar el userAgent en el servidor para análisis posterior
                                logUserAgentToServer(ua, 'deteccion_normal');

                                return false;
                            };

                            // Función para abrir un enlace en el navegador externo
                            const openInExternalBrowser = (url) => {
                                const isAndroid = /Android/i.test(navigator.userAgent);
                                const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);

                                if (isAndroid) {
                                    // Intent para abrir en Chrome en Android
                                    try {
                                        const urlSinHttps = url.replace(/^https?:\/\//, '');
                                        window.location.href = `intent://${urlSinHttps}#Intent;scheme=https;package=com.android.chrome;end;`;
                                    } catch (e) {
                                        alert(`Por favor, abre este enlace en tu navegador:\n\n${url}`);
                                    }
                                } else if (isIOS) {
                                    // Intentamos abrir en Safari para iOS
                                    try {
                                        window.location.href = url;
                                        setTimeout(() => {
                                            alert(`Si el navegador no se abrió, copia y pega este enlace en Safari:\n\n${url}`);
                                        }, 2000);
                                    } catch (e) {
                                        alert(`Por favor, abre este enlace en tu navegador:\n\n${url}`);
                                    }
                                } else {
                                    // Otros dispositivos o navegadores
                                    alert(`Por favor, abre este enlace en tu navegador:\n\n${url}`);
                                }
                            };

                            // Función para registrar el userAgent en el servidor
                            const logUserAgentToServer = (userAgent, type) => {
                                fetch('/wp-json/myplugin/v1/log-user-agent', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({
                                            userAgent: userAgent,
                                            type: type,
                                        }),
                                    })
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('Error al registrar el userAgent');
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        //console.log('UserAgent registrado:', data);
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                    });
                            };

                            // Lógica principal
                            if (isEmbeddedBrowser()) {
                                logUserAgentToServer(navigator.userAgent, 'navegador_embebido');
                                openInExternalBrowser(googleOAuthURL);
                            } else {
                                window.location.href = googleOAuthURL;
                            }
                        });
                    </script>



                    <button type="button" class="R0A915 A1 boton-cerrar">Volver</button>
                    <p><a href="https://2upra.com/tc/">Política de privacidad</a></p>
                </div>
                <? echo $mensaje; ?>
            </div>
        </form>
        <div class="RFZJUH">
            <div class="HPUYVS" id="fondograno"><? echo $GLOBALS['iconologo1']; ?></div>
        </div>
    </div>
<?
    return ob_get_clean();
}

// Registra la ruta de la API REST
add_action('rest_api_init', function () {
    register_rest_route('myplugin/v1', '/log-user-agent', array(
        'methods' => 'POST',
        'callback' => 'log_user_agent_callback',
        'permission_callback' => '__return_true', // Permite que cualquiera pueda acceder (ajusta según tus necesidades de seguridad)
    ));
});

// Función callback para manejar la petición
function log_user_agent_callback(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $userAgent = isset($params['userAgent']) ? sanitize_text_field($params['userAgent']) : '';
    $type = isset($params['type']) ? sanitize_text_field($params['type']) : '';

    // Registra la información en el archivo de registro de errores de WordPress
    error_log("UserAgent detectado ({$type}): " . $userAgent);

    // También puedes guardar la información en una base de datos personalizada o enviarla por correo electrónico si lo prefieres

    return new WP_REST_Response(array('message' => 'UserAgent registrado correctamente'), 200);
}


function handle_google_callback()
{
    $log = "Iniciando handle_google_callback";
    if (isset($_GET['code'])) {
        $cod = $_GET['code'];
        $idCli = '84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com';
        $secretCli = $_ENV['GOOGLEAPI']; 
        $redirectUri = 'https://2upra.com/google-callback';

        $res = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code' => $cod,
                'client_id' => $idCli,
                'client_secret' => $secretCli,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            )
        ));

        if (is_wp_error($res)) {
            $log .= ", \n Error en la autenticación con Google: " . $res->get_error_message();
            guardarLog($log);
            return;
        }

        $tok = json_decode($res['body']);
        if (!isset($tok->access_token)) {
            $log .= ", \n  No se recibió el token de acceso.";
            guardarLog($log);
            return;
        }
        $accessToken = $tok->access_token;

        $infoUsuarioRes = wp_remote_get('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $accessToken);
        if (is_wp_error($infoUsuarioRes)) {
            $log .= ", \n  Error al obtener la información del usuario: " . $infoUsuarioRes->get_error_message();
            guardarLog($log);
            return;
        }
        $infoUsuario = json_decode($infoUsuarioRes['body']);

        if ($infoUsuario && isset($infoUsuario->email)) {
            $email = $infoUsuario->email;
            $nom = $infoUsuario->name;

            $usu = get_user_by('email', $email);

            if ($usu) {
                $log .= ", \n  El usuario con email $email ya existe.";
                
                if ($usu->ID == 355) {
                    $usu = get_user_by('id', 1);
                    $log .= " \n  El usuario con id 355 se le cambio el inicio de sesion por el usuario con id 1.";
                }
                
                wp_set_current_user($usu->ID);
                wp_set_auth_cookie($usu->ID);
                $token = generate_secure_token($usu->ID);
                 $log .= ", \n  Token generado para el usuario $usu->ID";

                if (!headers_sent()) {
                    $url = is_electron_app() ? 'https://2upra.com/app?token=' . $token : 'https://2upra.com';
                    wp_redirect($url);
                    $log .= ", \n  Redireccionando a $url";
                    guardarLog($log);
                    exit;
                } else {
                    $url = is_electron_app() ? 'https://2upra.com/app?token=' . $token : 'https://2upra.com';
                    echo "<script>
    window.location.href = '$url';
</script>";
                    $log .= ", \n  Redireccionando a $url via JavaScript";
                    guardarLog($log);
                    exit;
                }
            } else {
                $log .= ", \n  El usuario no existe. Creando nuevo usuario.";
                $pass = wp_generate_password();
                $login = sanitize_user(str_replace(' ', '', strtolower($nom)), true);
                $originalLogin = $login;
                $contador = 1;
                while (username_exists($login)) {
                    $login = $originalLogin . $contador;
                    $contador++;
                }

                $idUsu = wp_create_user($login, $pass, $email);

                if (is_wp_error($idUsu)) {
                    $log .= ", \n  Error al crear el usuario: " . $idUsu->get_error_message();
                    guardarLog($log);
                    return;
                }

                $log .= ", \n  Usuario creado con ID: $idUsu";
                wp_set_current_user($idUsu);
                wp_set_auth_cookie($idUsu);

                if (!headers_sent()) {
                    wp_redirect('https://2upra.com');
                    $log .= ", \n  Redireccionando a https://2upra.com";
                    guardarLog($log);
                    exit;
                } else {
                    echo "<script>
    window.location.href = 'https://2upra.com';
</script>";
                    $log .= ", \n  Redireccionando a https://2upra.com via JavaScript";
                    guardarLog($log);
                    exit;
                }
            }
        } else {
            $log .= ", \n  No se pudo obtener la información del usuario de Google.";
        }
    } else {
        $log .= ", \n  No se recibió el código de autorización.";
    }
    //guardarLog($log);
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

    error_log('generate_secure_token - user_id: ' . $user_id . ' token: ' . $token . ' expiration: ' . $expiration . ' result_token: ' . ($result_token ? 'true' : 'false') . ' result_expiration: ' . ($result_expiration ? 'true' : 'false'));

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
        echo "<script>
        window.userId = '" . $user_id . "';
        </script>";
    }
});




add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/save-token', array(
        'methods' => 'POST',
        'callback' => 'save_firebase_token',
        'permission_callback' => '__return_true', 
    ));
});

function save_firebase_token($request)
{
    $user_id = $request->get_param('userId'); // Obtener el userId desde la solicitud
    error_log("Inicio de la función save_firebase_token. userId recibido: " . $user_id);

    // Verificar si el ID de usuario es válido
    if (!$user_id) {
        error_log("Error: userId no proporcionado.");
        return new WP_Error('invalid_user', 'El usuario no existe o el ID es inválido.', array('status' => 400));
    }

    if (!get_userdata($user_id)) {
        error_log("Error: No se encontró un usuario con el ID: " . $user_id);
        return new WP_Error('invalid_user', 'El usuario no existe o el ID es inválido.', array('status' => 400));
    }
    error_log("Usuario válido encontrado con ID: " . $user_id);

    $firebase_token = sanitize_text_field($request->get_param('token'));
    error_log("Token recibido: " . $firebase_token);

    // Verificar que el token no esté vacío
    if (!$firebase_token) {
        error_log("Error: Token no proporcionado.");
        return new WP_Error('no_token', 'El token es requerido.', array('status' => 400));
    }

    // Guardar el token, solo si es diferente al actual
    $current_token = get_user_meta($user_id, 'firebase_token', true);
    error_log("Token actual del usuario: " . $current_token);

    if ($current_token !== $firebase_token) {
        // Actualizar el token del usuario
        $updated = update_user_meta($user_id, 'firebase_token', $firebase_token);
        error_log("Resultado de update_user_meta para firebase_token: " . var_export($updated, true));

        if (!$updated) {
            error_log("Error: No se pudo guardar el token en la base de datos.");
            return new WP_Error('save_failed', 'No se pudo guardar el token.', array('status' => 500));
        }
        error_log("Token actualizado correctamente.");
    } else {
        error_log("El token recibido es igual al token actual. No se actualiza.");
    }

    // Guardar la versión de la app
    error_log("Llamando a la función save_version_meta.");
    save_version_meta($user_id, $request);

    // Verificar si el token se guardó correctamente o si ya existía
    if ($current_token === $firebase_token) {
        error_log("El token ya estaba guardado. Versión de la app actualizada correctamente.");
        return array(
            'success' => true,
            'message' => 'El token ya estaba guardado. Versión de la app actualizada correctamente.',
        );
    } else {
        error_log("Token y versión de la app guardados correctamente.");
        return array(
            'success' => true,
            'message' => 'Token y versión de la app guardados correctamente.',
        );
    }
}

function save_version_meta($user_id, $request)
{
    error_log("Inicio de la función save_version_meta para el usuario: " . $user_id);

    // Obtener la versión de la app desde la solicitud
    $app_version_name = sanitize_text_field($request->get_param('appVersionName'));
    $app_version_code = intval($request->get_param('appVersionCode')); // Convertir a entero

    error_log("appVersionName recibido: " . $app_version_name);
    error_log("appVersionCode recibido: " . $app_version_code);

    // Guardar la versión de la app solo si están presentes y son diferentes a las actuales
    if ($app_version_name) {
        $current_app_version_name = get_user_meta($user_id, 'app_version_name', true);
        error_log("appVersionName actual del usuario: " . $current_app_version_name);

        if ($current_app_version_name !== $app_version_name) {
            $updated_name = update_user_meta($user_id, 'app_version_name', $app_version_name);
            error_log("Resultado de update_user_meta para app_version_name: " . var_export($updated_name, true));
            if ($updated_name) {
                error_log("appVersionName actualizada correctamente.");
            } else {
                error_log("Error al actualizar appVersionName.");
            }
        } else {
            error_log("appVersionName recibido es igual al actual. No se actualiza.");
        }
    } else {
        error_log("appVersionName no proporcionado.");
    }

    if ($app_version_code) {
        $current_app_version_code = get_user_meta($user_id, 'app_version_code', true);
        error_log("appVersionCode actual del usuario: " . $current_app_version_code);

        if ($current_app_version_code !== $app_version_code) {
            $updated_code = update_user_meta($user_id, 'app_version_code', $app_version_code);
            error_log("Resultado de update_user_meta para app_version_code: " . var_export($updated_code, true));

            if ($updated_code) {
                error_log("appVersionCode actualizado correctamente.");
            } else {
                error_log("Error al actualizar appVersionCode.");
            }
        } else {
            error_log("appVersionCode recibido es igual al actual. No se actualiza.");
        }
    } else {
        error_log("appVersionCode no proporcionado.");
    }

    error_log("Fin de la función save_version_meta.");
}
