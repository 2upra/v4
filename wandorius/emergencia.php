<?php

function bloquear_y_eliminar_usuarios($usuarios) {
    foreach ($usuarios as $usuario) {
        $user = null;
        
        // Determinar el tipo de identificación del usuario
        if (is_numeric($usuario)) {
            $user = get_user_by('id', $usuario);
        } elseif (filter_var($usuario, FILTER_VALIDATE_IP)) {
            $user = get_user_by('ip', $usuario);
        } elseif (is_email($usuario)) {
            $user = get_user_by('email', $usuario);
        } else {
            $user = get_user_by('login', $usuario);
        }

        if ($user) {
            // Eliminar comentarios del usuario
            $comments = get_comments(array('user_id' => $user->ID));
            foreach ($comments as $comment) {
                wp_delete_comment($comment->comment_ID, true);
            }

            // Eliminar posts del usuario
            $posts = get_posts(array(
                'author' => $user->ID,
                'post_type' => 'any',
                'numberposts' => -1
            ));
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }

            // Bloquear usuario
            wp_update_user(array('ID' => $user->ID, 'role' => 'blocked'));
            wp_update_user(array('ID' => $user->ID, 'user_status' => 1));

            // Bloquear IP si se proporcionó
            if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                bloquear_ip($usuario);
            }
        } else {
            // Si no se encuentra el usuario pero es una IP válida, bloquearla
            if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                bloquear_ip($usuario);
            } else {
                error_log("No se pudo encontrar o bloquear al usuario: $usuario");
            }
        }
    }
}

function bloquear_ip($ip) {
    $htaccess = ABSPATH . '/.htaccess';
    $deny = "\n# Bloqueo de IP\nDeny from $ip\n";
    if (file_exists($htaccess)) {
        file_put_contents($htaccess, $deny, FILE_APPEND);
    }
}

// Ejemplo de uso
$usuarios_a_bloquear = [
    'lxbfYeaa',        
    '185.198.69.118',
    '173.230.132.139',
    'ZAP'
];

bloquear_y_eliminar_usuarios($usuarios_a_bloquear);
/*
function restringir_acceso_admin() {
    $user = wp_get_current_user();
    $allowed_ip = '104.28.203.220';  // Reemplaza con tu IP

    if ($user->ID !== 1 || $_SERVER['REMOTE_ADDR'] !== $allowed_ip) {
        wp_die('Acceso denegado');
    }
}
add_action('admin_init', 'restringir_acceso_admin');

add_filter('xmlrpc_enabled', '__return_false');
*/


function registrar_intento_acceso_fallido($username) {
    $log_file = ABSPATH . '/wp-content/uploads/access_logs.txt';
    $ip = $_SERVER['REMOTE_ADDR'];
    $time = date('Y-m-d H:i:s');
    $log_entry = "Intento fallido de acceso por usuario: $username, IP: $ip, Fecha: $time\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
add_action('wp_login_failed', 'registrar_intento_acceso_fallido');