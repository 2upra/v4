<?php

// Redirigir a la página de perfil del usuario actual
add_action('template_redirect', 'redirect_to_user_profile');
function redirect_to_user_profile() {
    if (is_user_logged_in() && is_page('perfil')) {
        $current_user = wp_get_current_user();
        wp_redirect(site_url('/perfil/' . $current_user->user_login));
        exit;
    }
}

// Añadir reglas de reescritura personalizadas
add_action('init', 'custom_rewrite_rules');
function custom_rewrite_rules() {
    add_rewrite_rule('^perfil/([^/]*)/?', 'index.php?profile_user=$matches[1]', 'top');
}

// Añadir 'profile_user' al listado de query vars
add_filter('query_vars', 'custom_query_vars');
function custom_query_vars($vars) {
    $vars[] = 'profile_user';
    return $vars;
}

// Usar plantilla personalizada para la URL de perfil
add_filter('template_include', 'custom_template_include');
function custom_template_include($template) {
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
function flush_rewrite_rules_once() {
    if (get_option('rewrite_rules_flushed') != true) {
        flush_rewrite_rules();
        update_option('rewrite_rules_flushed', true);
    }
}
add_action('template_redirect', 'redirigir_author_a_perfil');
function redirigir_author_a_perfil() {
    if (is_author()) {
        global $wp;
        $author_slug = $wp->query_vars['author_name'];
        $nuevo_url = home_url('/perfil/' . $author_slug);
        wp_redirect($nuevo_url, 301);
        exit();
    }
}
add_action('template_redirect', 'custom_user_profile_redirect');
function custom_user_profile_redirect() {
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



