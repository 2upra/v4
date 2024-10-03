<?php

function iniciar_sesion() {
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
                    <button type="button" class="R0A915 A1 A2 boton-registro">Registrarme</button>
                    <button type="button" class="R0A915 A1 boton-cerrar">Volver</button>
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
