<?

function iniciar_sesion()
{
    if (is_user_logged_in()) return '<div>Ya has iniciado sesión. ¿Quieres cerrar sesión? <a href="' . wp_logout_url(home_url()) . '">Cerrar sesión</a></div>';

    $mensaje = '';
    if (isset($_POST['iniciar_sesion_submit'])) {
        $user = wp_signon(array(
            'user_login' => sanitize_user($_POST['nombre_usuario_login']),
            'user_password' => $_POST['contrasena_usuario_login']
        ), false);

        if (!is_wp_error($user)) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            wp_redirect('https://2upra.com');
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
                    <button type="button" class="R0A915 botonprincipal A1 A2" id="google-login-btn"><? echo $GLOBALS['Google']; ?>Iniciar sesión con Google</button>

                    <script>
                        document.getElementById('google-login-btn').addEventListener('click', function() {
                            window.location.href = 'https://accounts.google.com/o/oauth2/auth?' +
                                'client_id=84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com&' + // Aquí agregamos el "&"
                                'redirect_uri=https://2upra.com/google-callback&' +
                                'response_type=code&' +
                                'scope=email profile';
                        });
                    </script>

                    <button type="button" class="R0A915 A1 boton-cerrar">Volver</button>
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


/*
ayudame a poner guardarLogs para ver porque falla handle_google_callback para iniciar seccion con google
te muestro el boton para iniciar seccion
<button type="button" class="R0A915 botonprincipal A1 A2" id="google-login-btn"><? echo $GLOBALS['Google']; ?>Iniciar sesión con Google</button>

<script>
    document.getElementById('google-login-btn').addEventListener('click', function() {
        window.location.href = 'https://accounts.google.com/o/oauth2/auth?' +
            'client_id=84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com&' + // Aquí agregamos el "&"
            'redirect_uri=https://2upra.com/google-callback&' +
            'response_type=code&' +
            'scope=email profile';
    });
</script>

*/
function descargar_archivo_drive($file_id, $file_name, $access_token, $folder_path)
{
    guardarLog('descargar archivo iniciado');
    $download_url = "https://www.googleapis.com/drive/v3/files/{$file_id}?alt=media&access_token={$access_token}";
    $response = wp_remote_get($download_url, array('timeout' => 120));
    if (!is_wp_error($response)) {
        $file_path = $folder_path . '/' . sanitize_file_name($file_name);
        file_put_contents($file_path, $response['body']);
        error_log("Archivo {$file_name} descargado correctamente.");
    } else {
        error_log("Error al descargar el archivo: {$file_name}");
    }
}

function sincronizar_drive_con_vps($access_token, $folder_path)
{
    $response = wp_remote_get('https://www.googleapis.com/drive/v3/files?access_token=' . $access_token);
    if (is_wp_error($response)) {
        error_log('Error al obtener archivos de Google Drive.');
        return;
    }

    $files = json_decode($response['body'])->files;
    $local_files = scandir($folder_path);
    $local_files = array_diff($local_files, array('.', '..')); 

    $drive_file_names = array_map(function($file) {
        return sanitize_file_name($file->name);
    }, $files);

    foreach ($files as $file) {
        descargar_archivo_drive($file->id, $file->name, $access_token, $folder_path);
    }
    /*
    mantener comentado esto
    foreach ($local_files as $local_file) {
        if (!in_array($local_file, $drive_file_names)) {
            unlink($folder_path . '/' . $local_file);
            error_log("Archivo {$local_file} eliminado de la VPS porque no está en Google Drive.");
        }
    }
    */
}


function handle_google_callback()
{
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

        // Obtener información del usuario
        $user_info_response = wp_remote_get('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $access_token);
        $user_info = json_decode($user_info_response['body']);

        if ($user_info && isset($user_info->email)) {
            $email = $user_info->email;
            $name = $user_info->name;

            if ($user = get_user_by('email', $email)) {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                wp_redirect('https://2upra.com');
                exit;
            } else {
                $random_password = wp_generate_password();
                $user_id = wp_create_user($name, $random_password, $email);
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
            }

            // Solo sincronizar si el usuario es el administrador (por ejemplo, tú mismo)
            if ($email == 'andoryyu@gmail.com') {
                // Sincronizar los archivos de Google Drive con una carpeta en VPS
                $folder_path = '/var/www/html/wp-content/uploads/drive_sync';
                sincronizar_drive_con_vps($access_token, $folder_path);
            }

            wp_redirect('https://2upra.com');
            exit;
        }
    }
}

add_action('init', 'handle_google_callback');