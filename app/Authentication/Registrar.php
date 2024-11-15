<?

function registrar_usuario() {
    if (is_user_logged_in()) return '<div>Hola.</div>';
    
    $mensaje = '';
    if (isset($_POST['registrar_usuario_submit'])) {
        $nombre_usuario = str_replace(' ', '', sanitize_user($_POST['nombre_usuario'], true));
        $correo_usuario = sanitize_email($_POST['correo_usuario']);
        $contrasena_usuario = $_POST['contrasena_usuario'];
        $tipo_usuario = sanitize_text_field($_POST['tipo_usuario']);

        if (!username_exists($nombre_usuario) && !email_exists($correo_usuario)) {
            $user_id = wp_create_user($nombre_usuario, $contrasena_usuario, $correo_usuario);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'tipo_usuario', $tipo_usuario);
                if ($tipo_usuario === 'fan') update_user_meta($user_id, 'fan', true);
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                wp_redirect(home_url());
                exit;
            } else {
                $mensaje = '<div class="error-mensaje">Error al registrar el usuario: ' . $user_id->get_error_message() . '</div>';
            }
        } else {
            $mensaje = '<div class="error-mensaje">El nombre de usuario o el correo ya están en uso.</div>';
        }
    }

    ob_start();
    ?>
    <div class="PUWJVS">
        <form class="CXHMID" action="" method="post">
            <div class="XUSEOO">
                <label for="nombre_usuario">Nombre de Usuario:</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" required><br>
                <label for="correo_usuario">Correo Electrónico:</label>
                <input type="email" id="correo_usuario" name="correo_usuario" required><br>
                <label for="contrasena_usuario">Contraseña:</label>
                <input type="password" id="contrasena_usuario" name="contrasena_usuario" required><br>
                <label for="tipo_usuario">Tipo de Usuario:</label>
                <div id="userTypeSelector">
                    <div id="userTypeArtista" class="user-type-option" data-value="artista" onclick="selectUserType('artista')">
                        <div><? echo $GLOBALS['iconomusic1']; ?></div>
                        <div>Artista</div>
                    </div>
                    <div id="userTypeFan" class="user-type-option" data-value="fan" onclick="selectUserType('fan')">
                        <div><? echo $GLOBALS['iconoperfil1']; ?></div>
                        <div>Fan</div>
                    </div>
                </div>
                <input type="hidden" id="tipo_usuario" name="tipo_usuario" required>
                <p id="errorTipoUsuario" style="color: red; display: none;">Por favor, selecciona un tipo de usuario.</p>
                <? echo $mensaje; ?>
                <div class="XYSRLL">
                    <input class="R0A915 A1" type="submit" name="registrar_usuario_submit" value="Registrar" onclick="return validarSeleccion()">
                    <button type="button" class="R0A915 A1 boton-cerrar">Volver</button>
                </div>
            </div>
        </form>
        <div class="RFZJUH">
            <div class="HPUYVS" id="fondograno"><? echo $GLOBALS['iconologo1']; ?></div>
        </div>
    </div>
    <?
    return ob_get_clean();
}

function redirect_profile_spaces()
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/perfil/') !== false) {
        $request_uri = $_SERVER['REQUEST_URI'];
        $new_uri = str_replace(' ', '', $request_uri);
        $new_uri = str_replace('+', '', $new_uri);

        if ($new_uri !== $request_uri) {
            wp_redirect(home_url($new_uri), 301);
            exit;
        }
    }
}
add_action('init', 'redirect_profile_spaces');
