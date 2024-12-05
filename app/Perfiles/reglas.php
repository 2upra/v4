<?

add_action('template_redirect', function () {
    // Verificar si estamos en la página de inicio y si el parámetro 'search' está en la URL
    if (!is_front_page() && isset($_GET['search'])) {
        $search_query = sanitize_text_field($_GET['search']); // Sanitiza el término de búsqueda

        // Evitar redirección infinita comprobando si ya estamos redirigiendo a esta URL
        if (!isset($_GET['redirected'])) {
            // Redirige a la página de inicio con el parámetro de búsqueda y una marca para evitar bucles
            wp_redirect(home_url('/?search=' . urlencode($search_query) . '&redirected=1'));
            exit;
        }
    }
});



add_action('template_redirect', 'handle_profile_redirects');
function handle_profile_redirects() {
    // Obtener la URL actual
    $current_url = $_SERVER['REQUEST_URI'];
    
    // Caso 1: Redirección de /perfil/ al perfil del usuario actual
    if (rtrim($current_url, '/') === '/perfil' && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $clean_user_login = sanitize_title($current_user->user_login);
        wp_redirect(home_url("/perfil/{$clean_user_login}/"), 301);
        exit;
    }
    
    // Caso 2: Corregir URLs con espacios en los perfiles
    if (strpos($current_url, '/perfil/') !== false && 
        (strpos($current_url, '%20') !== false || strpos($current_url, ' ') !== false)) {
        
        // Obtener el nombre de usuario de la URL
        $path_parts = explode('/perfil/', $current_url);
        if (isset($path_parts[1])) {
            $username = trim(urldecode($path_parts[1]), '/');
            
            // Crear slug limpio
            $clean_username = sanitize_title($username);
            
            // Redirigir a la URL limpia
            wp_redirect(home_url("/perfil/{$clean_username}/"), 301);
            exit;
        }
    }
}

// Función auxiliar para limpiar URLs
add_filter('sanitize_title', 'custom_sanitize_title', 10, 1);
function custom_sanitize_title($title) {
    // Convertir caracteres especiales y espacios a guiones
    $title = remove_accents($title);
    $title = strtolower($title);
    $title = preg_replace('/[^a-z0-9\-]/', '-', $title);
    $title = preg_replace('/-+/', '-', $title);
    $title = trim($title, '-');
    return $title;
}

// Añadir reglas de reescritura personalizadas
add_action('init', 'custom_rewrite_rules');
function custom_rewrite_rules()
{
    add_rewrite_rule('^perfil/([^/]*)/?', 'index.php?profile_user=$matches[1]', 'top');
}

// Añadir 'profile_user' al listado de query vars
add_filter('query_vars', 'custom_query_vars');
function custom_query_vars($vars)
{
    $vars[] = 'profile_user';
    return $vars;
}

// Usar plantilla personalizada para la URL de perfil
add_filter('template_include', 'custom_template_include');
function custom_template_include($template)
{
    if (get_query_var('profile_user')) {
        $new_template = locate_template(array('perfil.php'));
        if ('' != $new_template) {
            return $new_template;
        }
    }
    return $template;
}

// Limpiar caché de reglas de reescritura
add_action('init', 'flush_rewrite_rules_once');
function flush_rewrite_rules_once()
{
    if (get_option('rewrite_rules_flushed') != true) {
        flush_rewrite_rules();
        update_option('rewrite_rules_flushed', true);
    }
}
add_action('template_redirect', 'redirigir_author_a_perfil');
function redirigir_author_a_perfil()
{
    if (is_author()) {
        global $wp;
        $author_slug = $wp->query_vars['author_name'];
        $nuevo_url = home_url('/perfil/' . $author_slug);
        wp_redirect($nuevo_url, 301);
        exit();
    }
}
add_action('template_redirect', 'custom_user_profile_redirect');
function custom_user_profile_redirect()
{
    if (is_404()) {
        $requested_url = $_SERVER['REQUEST_URI'];
        $url_segments = explode('/', trim($requested_url, '/'));
        if (count($url_segments) == 1) {
            $user_slug = $url_segments[0];
            $user = get_user_by('slug', $user_slug);
            if ($user) {
                wp_redirect(home_url('/perfil/' . $user_slug), 301);
                exit;
            }
        }
    }
}
