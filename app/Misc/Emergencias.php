<?

// Refactor(Org): User blocking/restriction functions moved to app/Services/UserService.php


function agregar_rol_restringido() {
    add_role('restringido', 'Usuario Restringido', array(
        'read' => true, // Solo puede leer
        'edit_posts' => false, // No puede crear o editar publicaciones
        'upload_files' => false, // No puede subir archivos
        'delete_posts' => false, // No puede eliminar publicaciones
    ));
}
//add_action('init', 'agregar_rol_restringido');


// Ejemplo de uso
$usuarios_a_bloquear = [
    'lxbfYeaa',        
    '185.198.69.118',
    '173.230.132.139',
    'ZAP'
];

//bloquear_y_eliminar_usuarios($usuarios_a_bloquear);
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
//add_action('wp_login_failed', 'registrar_intento_acceso_fallido');
