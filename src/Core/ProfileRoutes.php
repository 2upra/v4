<?php

namespace Kamples\Core;

class ProfileRoutes
{
    public function __construct()
    {
        add_action('template_redirect', [$this, 'handleRedirects']);
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('query_vars', [$this, 'addQueryVars']);
        add_filter('template_include', [$this, 'loadTemplate']);
        add_action('init', [$this, 'flushRules']);
    }

    public function handleRedirects()
    {
        // Redirección de búsqueda
        if (!is_front_page() && isset($_GET['search'])) {
            $search_query = sanitize_text_field($_GET['search']);
            if (!isset($_GET['redirected'])) {
                wp_redirect(home_url('/?search=' . urlencode($search_query) . '&redirected=1'));
                exit;
            }
        }

        $current_url = $_SERVER['REQUEST_URI'];

        // Caso 1: /perfil/ -> perfil actual
        if (rtrim($current_url, '/') === '/perfil' && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $clean_user_login = sanitize_title($current_user->user_login);
            wp_redirect(home_url("/perfil/{$clean_user_login}/"), 301);
            exit;
        }

        // Caso 2: Limpiar espacios en URL de perfil
        if (strpos($current_url, '/perfil/') !== false) {
            $path_parts = explode('/perfil/', $current_url);
            if (isset($path_parts[1])) {
                $username_with_spaces = trim(urldecode($path_parts[1]), '/');
                $clean_username = str_replace([' ', '+', '%20'], '', $username_with_spaces);
                if ($username_with_spaces !== $clean_username) {
                    wp_redirect(home_url("/perfil/{$clean_username}/"), 301);
                    exit;
                }
            }
        }

        // Caso 3: Author page -> perfil
        if (is_author()) {
            global $wp;
            $author_slug = $wp->query_vars['author_name'];
            $nuevo_url = home_url('/perfil/' . $author_slug);
            wp_redirect($nuevo_url, 301);
            exit();
        }

        // Caso 4: 404 -> intentar encontrar usuario por slug
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

    public function addRewriteRules()
    {
        add_rewrite_rule('^perfil/([^/]*)/?', 'index.php?profile_user=$matches[1]', 'top');
    }

    public function addQueryVars($vars)
    {
        $vars[] = 'profile_user';
        return $vars;
    }

    public function loadTemplate($template)
    {
        if (get_query_var('profile_user')) {
            $new_template = locate_template(['perfil.php']);
            if ('' != $new_template) {
                return $new_template;
            }
        }
        return $template;
    }

    public function flushRules()
    {
        if (get_option('rewrite_rules_flushed') != true) {
            flush_rewrite_rules();
            update_option('rewrite_rules_flushed', true);
        }
    }
}

new ProfileRoutes();
