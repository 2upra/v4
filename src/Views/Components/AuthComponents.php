<?php

namespace Kamples\Views\Components;

use Kamples\Services\Usuario\AuthService;

/**
 * Componentes de vistas de autenticación.
 * 
 * Renderiza formularios de login y registro.
 *
 * @since 1.0.0
 */
class AuthComponents
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = AuthService::obtenerInstancia();
    }

    /**
     * Renderiza el formulario de inicio de sesión.
     *
     * @return string HTML del formulario.
     */
    public function renderFormularioLogin(): string
    {
        if (is_user_logged_in()) {
            return '<div>Ya has iniciado sesión. ¿Quieres cerrar sesión? <a href="' . esc_url(wp_logout_url(home_url())) . '">Cerrar sesión</a></div>';
        }

        $mensaje = '';

        /* Procesar formulario de login */
        if (isset($_POST['iniciar_sesion_submit'])) {
            $user = $this->authService->iniciarSesion(
                $_POST['nombre_usuario_login'] ?? '',
                $_POST['contrasena_usuario_login'] ?? ''
            );

            if (!is_wp_error($user)) {
                if (!headers_sent()) {
                    wp_safe_redirect(home_url());
                    exit;
                } else {
                    echo "<script>window.location.href='" . home_url() . "';</script>";
                    exit;
                }
            } else {
                $mensaje = '<div class="error-mensaje">Error al iniciar sesión. Por favor, verifica tus credenciales.</div>';
            }
        }

        $googleOAuthUrl = $this->authService->obtenerUrlGoogleOAuth();

        ob_start();
?>
        <div class="PUWJVS">
            <form class="CXHMID" action="" method="post">
                <div class="XUSEOO">
                    <div class="XYSRLL">
                        <button type="button" class="R0A915 botonprincipal A1 A2" id="google-login-btn">
                            <?php echo $GLOBALS['Google'] ?? ''; ?>Iniciar sesión con Google
                        </button>

                        <script>
                            document.getElementById('google-login-btn').addEventListener('click', function() {
                                const googleOAuthURL = '<?php echo esc_js($googleOAuthUrl); ?>';

                                const isEmbeddedBrowser = () => {
                                    const ua = navigator.userAgent || navigator.vendor || window.opera;
                                    const knownEmbeddedUAs = ["Threads", "Barcelona", "Instagram", "FBAN", "FBAV", "Messenger", "Meta", "Facebook", "Line", "Twitter", "Snapchat", "TikTok"];
                                    return knownEmbeddedUAs.some(embedded => ua.includes(embedded)) ||
                                        /WebView/.test(ua);
                                };

                                const openInExternalBrowser = (url) => {
                                    const isAndroid = /Android/i.test(navigator.userAgent);
                                    const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);

                                    if (isAndroid) {
                                        try {
                                            const urlSinHttps = url.replace(/^https?:\/\//, '');
                                            window.location.href = `intent://${urlSinHttps}#Intent;scheme=https;package=com.android.chrome;end;`;
                                        } catch (e) {
                                            alert(`Por favor, abre este enlace en tu navegador:\n\n${url}`);
                                        }
                                    } else if (isIOS) {
                                        window.location.href = url;
                                    } else {
                                        alert(`Por favor, abre este enlace en tu navegador:\n\n${url}`);
                                    }
                                };

                                if (isEmbeddedBrowser()) {
                                    openInExternalBrowser(googleOAuthURL);
                                } else {
                                    window.location.href = googleOAuthURL;
                                }
                            });
                        </script>

                        <button type="button" class="R0A915 A1 boton-cerrar">Volver</button>
                        <p><a href="<?php echo esc_url(home_url('/tc/')); ?>">Política de privacidad</a></p>
                    </div>
                    <?php echo $mensaje; ?>
                </div>
            </form>
            <div class="RFZJUH">
                <div class="HPUYVS" id="fondograno"><?php echo $GLOBALS['iconologo1'] ?? ''; ?></div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el formulario de registro.
     *
     * @return string HTML del formulario.
     */
    public function renderFormularioRegistro(): string
    {
        if (is_user_logged_in()) {
            return '<div>Ya tienes una cuenta.</div>';
        }

        $mensaje = '';

        /* Procesar formulario de registro */
        if (isset($_POST['registrar_usuario_submit'])) {
            $userId = $this->authService->registrarUsuario(
                $_POST['nombre_usuario'] ?? '',
                $_POST['correo_usuario'] ?? '',
                $_POST['contrasena_usuario'] ?? '',
                $_POST['tipo_usuario'] ?? 'fan'
            );

            if (!is_wp_error($userId)) {
                wp_set_current_user($userId);
                wp_set_auth_cookie($userId);
                wp_redirect(home_url());
                exit;
            } else {
                $mensaje = '<div class="error-mensaje">' . esc_html($userId->get_error_message()) . '</div>';
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
                            <div><?php echo $GLOBALS['iconomusic1'] ?? ''; ?></div>
                            <div>Artista</div>
                        </div>
                        <div id="userTypeFan" class="user-type-option" data-value="fan" onclick="selectUserType('fan')">
                            <div><?php echo $GLOBALS['iconoperfil1'] ?? ''; ?></div>
                            <div>Fan</div>
                        </div>
                    </div>
                    <input type="hidden" id="tipo_usuario" name="tipo_usuario" required>
                    <p id="errorTipoUsuario" style="color: red; display: none;">Por favor, selecciona un tipo de usuario.</p>

                    <?php echo $mensaje; ?>

                    <div class="XYSRLL">
                        <input class="R0A915 A1" type="submit" name="registrar_usuario_submit" value="Registrar" onclick="return validarSeleccion()">
                        <button type="button" class="R0A915 A1 boton-cerrar">Volver</button>
                    </div>
                </div>
            </form>
            <div class="RFZJUH">
                <div class="HPUYVS" id="fondograno"><?php echo $GLOBALS['iconologo1'] ?? ''; ?></div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
