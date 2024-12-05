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



add_action('template_redirect', 'fix_profile_url_spaces');
function fix_profile_url_spaces() {
    // Obtener la URL actual completa
    $current_url = $_SERVER['REQUEST_URI'];
    
    // Verificar si la URL contiene '/perfil/' y espacios codificados
    if (strpos($current_url, '/perfil/') !== false && 
        (strpos($current_url, '%20') !== false || strpos($current_url, ' ') !== false)) {
        
        // Decodificar la URL para manejar caracteres especiales
        $decoded_url = urldecode($current_url);
        
        // Reemplazar espacios por guiones
        $fixed_url = str_replace(array(' ', '%20'), '-', $decoded_url);
        
        // Asegurarse de que la URL está correctamente codificada
        $fixed_url = rtrim($fixed_url, '/') . '/';
        
        // Redirigir a la URL corregida
        wp_redirect(home_url($fixed_url), 301);
        exit;
    }
}

// Función adicional para manejar otros casos de redirección de perfil
add_action('template_redirect', 'redirect_to_user_profile');
function redirect_to_user_profile() {
    if (is_user_logged_in() && strpos($_SERVER['REQUEST_URI'], '/perfil/') !== false) {
        $current_user = wp_get_current_user();
        $clean_user_login = sanitize_title($current_user->user_login);
        
        // Obtener la parte del slug después de /perfil/
        $request_uri = $_SERVER['REQUEST_URI'];
        $path_parts = explode('/perfil/', $request_uri);
        if (isset($path_parts[1])) {
            $current_slug = trim($path_parts[1], '/');
            $expected_slug = $clean_user_login;
            
            if ($current_slug !== $expected_slug) {
                wp_redirect(home_url("/perfil/{$expected_slug}/"), 301);
                exit;
            }
        }
    }
}

// Opcional: Agregar soporte para limpieza de URLs con caracteres especiales
add_filter('sanitize_title', 'custom_sanitize_title', 10, 3);
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
