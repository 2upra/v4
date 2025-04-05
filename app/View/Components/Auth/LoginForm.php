<?php
// Refactor(Org): Función iniciar_sesion() movida desde app/Authentication/Iniciar.php

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
?>
