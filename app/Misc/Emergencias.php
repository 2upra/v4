<?

// Refactor(Org): User blocking/restriction functions moved to app/Services/UserService.php

// Refactor(Org): Moved function agregar_rol_restringido() and its hook to app/Admin/Permisos.php


// Ejemplo de uso
$usuarios_a_bloquear = [
    'lxbfYeaa',        
    '185.198.69.118',
    '173.230.132.139',
    'ZAP'
];

//bloquear_y_eliminar_usuarios($usuarios_a_bloquear);
/*
// Refactor(Org): Moved to app/Services/UserService.php
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

// Refactor: Moved function registrar_intento_acceso_fallido and its hook to app/Utils/Logger.php


