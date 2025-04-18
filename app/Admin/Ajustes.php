<?

// Redirigir URLs con /tag/ a búsqueda personalizada


// Desactivar envío de correos
add_filter('wp_mail', fn($args) => []);

// Desactivar barra de admin para administradores
function desactivar_barra_admin_para_admin()
{
    if (current_user_can('administrator')) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action('after_setup_theme', 'desactivar_barra_admin_para_admin');
// Ocultar elementos de la barra de admin
function ocultar_elementos_barra_admin()
{
    echo '<style type="text/css">
        #wp-admin-bar-wp-logo, #wp-admin-bar-customize, #wp-admin-bar-updates, 
        #wp-admin-bar-comments, #wp-admin-bar-new-content, #wp-admin-bar-wpseo-menu, 
        #wp-admin-bar-edit { display: none !important; }
    </style>';
}
add_action('admin_head', 'ocultar_elementos_barra_admin');
add_action('wp_head', 'ocultar_elementos_barra_admin');

function fix_really_simple_ssl_textdomain_late()
{
    // Solo carga el dominio de texto si el plugin está activo
    if (function_exists('_load_textdomain_just_in_time')) {
        load_plugin_textdomain('really-simple-ssl', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}
add_action('plugins_loaded', 'fix_really_simple_ssl_textdomain_late');

// Personalizar estilos de la barra de admin (admin y front)
function personalizar_estilos_wp_admin_bar()
{
    echo '<style type="text/css">
        #wpadminbar {
            direction: ltr; color: #ffffff !important; font-size: 11px !important;
            font-weight: 200 !important; font-family: -apple-system, BlinkMacSystemFont, 
            "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
            line-height: 2.46153846 !important; height: 32px !important; position: fixed !important;
            top: 0 !important; left: 0 !important; width: 100% !important; min-width: 600px !important;
            z-index: 99999 !important; background: #000000 !important;
        }
    </style>';
}
add_action('admin_head', 'personalizar_estilos_wp_admin_bar');
add_action('wp_head', 'personalizar_estilos_wp_admin_bar');

function nonAdminRedirect()
{
    if (!current_user_can('administrator') && !wp_doing_ajax()) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'nonAdminRedirect');

// Refactor(Org): Función ocultarBarraAdmin() movida a app/Setup/ThemeSetup.php

function remplazarFuncionObsoleta()
{
    remove_action('wp_footer', 'the_block_template_skip_link');
    add_action('wp_footer', 'wp_enqueue_block_template_skip_link');
}
add_action('after_setup_theme', 'remplazarFuncionObsoleta');


// Refactor(Org): Función mimesPermitidos movida a app/Setup/ThemeSetup.php

/*
nada de esto esta funcionando para mentener la sesion abierta infinitamente
*/

function mantener_sesion_activa()
{
    if (is_user_logged_in()) {
        setcookie(TEST_COOKIE, 'wordpress_test_cookie', time() + 1421150815, SITECOOKIEPATH, COOKIE_DOMAIN);

        if (isset($_COOKIE[AUTH_COOKIE])) {
            setcookie(AUTH_COOKIE, $_COOKIE[AUTH_COOKIE], time() + 1421150815, SITECOOKIEPATH, COOKIE_DOMAIN);
        }

        if (isset($_COOKIE[SECURE_AUTH_COOKIE])) {
            setcookie(SECURE_AUTH_COOKIE, $_COOKIE[SECURE_AUTH_COOKIE], time() + 1421150815, SITECOOKIEPATH, COOKIE_DOMAIN);
        }

        if (isset($_COOKIE[LOGGED_IN_COOKIE])) {
            setcookie(LOGGED_IN_COOKIE, $_COOKIE[LOGGED_IN_COOKIE], time() + 1421150815, SITECOOKIEPATH, COOKIE_DOMAIN);
        }
    }
}

add_action('init', 'mantener_sesion_activa');

function mantenerSesion() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado.');
    }
    $log = "Sesión mantenida activa para el usuario " . get_current_user_id();
    guardarLog($log);
    wp_send_json_success();
}

add_action('wp_ajax_mantener_sesion_viva', 'mantenerSesion');

function tiempo_expiracion_cookies($expira, $userId, $recordar) {
    // 315360000 segundos = 10 años.
    $log = "Tiempo de expiración de la cookie para el usuario $userId: 315360000 segundos";
    guardarLog($log);
    return 315360000; 
}
add_filter('auth_cookie_expiration', 'tiempo_expiracion_cookies', 99, 3);


function agregarReglaReescritura() {
    add_rewrite_rule('^sample/([0-9]+)/?$', 'index.php?p=$matches[1]&post_type=social_post', 'top');
}
add_action('init', 'agregarReglaReescritura', 10, 0);

function modificarConsultaPrincipal($consulta) {
    if (!is_admin() && $consulta->is_main_query() && $consulta->get('p') && $consulta->get('post_type') === 'social_post') {
        $consulta->set('name', '');
    }
}
add_action('pre_get_posts', 'modificarConsultaPrincipal');

function forzarPlantillaSocialPost($template) {
    global $wp_query;
    if (isset($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] === 'social_post' && isset($wp_query->query_vars['p'])) {
        $plantilla = locate_template('single-social_post.php');
        if ($plantilla) {
            return $plantilla;
        }
    }
    return $template;
}
add_filter('template_include', 'forzarPlantillaSocialPost');
