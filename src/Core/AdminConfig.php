<?php

/**
 * Configuración del administrador y WordPress
 * 
 * Centraliza hooks, filtros y configuraciones del panel de administración
 *
 * @package Kamples\Core
 * @since 1.0.0
 */

namespace Kamples\Core;

class AdminConfig
{
    private static bool $inicializado = false;

    /**
     * Inicializa todas las configuraciones de administración
     */
    public static function inicializar(): void
    {
        if (self::$inicializado) {
            return;
        }

        self::$inicializado = true;

        /* Configuración de barra de admin */
        add_action('after_setup_theme', [self::class, 'ocultarBarraAdmin']);
        add_action('admin_head', [self::class, 'ocultarElementosBarraAdmin']);
        add_action('wp_head', [self::class, 'ocultarElementosBarraAdmin']);
        add_action('admin_head', [self::class, 'personalizarEstilosBarraAdmin']);
        add_action('wp_head', [self::class, 'personalizarEstilosBarraAdmin']);

        /* Configuración de acceso admin */
        add_action('admin_init', [self::class, 'redirigirNoAdmin']);

        /* Correos */
        add_filter('wp_mail', fn($args) => []);

        /* MIMES permitidos */
        add_filter('upload_mimes', [self::class, 'mimesPermitidos']);

        /* Sesiones */
        add_action('init', [self::class, 'mantenerSesionActiva']);
        add_action('wp_ajax_mantener_sesion_viva', [self::class, 'manejarMantenerSesion']);
        add_filter('auth_cookie_expiration', [self::class, 'tiempoExpiracionCookies'], 99, 3);

        /* Reescritura de URLs */
        add_action('init', [self::class, 'agregarReglasReescritura'], 10, 0);
        add_action('pre_get_posts', [self::class, 'modificarConsultaPrincipal']);
        add_filter('template_include', [self::class, 'forzarPlantillaSocialPost']);

        /* Fixes diversos */
        add_action('after_setup_theme', [self::class, 'remplazarFuncionObsoleta']);
        add_action('plugins_loaded', [self::class, 'fixTextdomainSSL']);
    }

    /**
     * Ocultar barra de admin para no administradores
     */
    public static function ocultarBarraAdmin(): void
    {
        if (!current_user_can('administrator')) {
            add_filter('show_admin_bar', '__return_false');
        }
    }

    /**
     * Ocultar elementos innecesarios de la barra de admin
     */
    public static function ocultarElementosBarraAdmin(): void
    {
        echo '<style type="text/css">
            #wp-admin-bar-wp-logo, #wp-admin-bar-customize, #wp-admin-bar-updates, 
            #wp-admin-bar-comments, #wp-admin-bar-new-content, #wp-admin-bar-wpseo-menu, 
            #wp-admin-bar-edit { display: none !important; }
        </style>';
    }

    /**
     * Personalizar estilos de la barra de admin
     */
    public static function personalizarEstilosBarraAdmin(): void
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

    /**
     * Redirigir no administradores fuera del admin
     */
    public static function redirigirNoAdmin(): void
    {
        if (!current_user_can('administrator') && !wp_doing_ajax()) {
            wp_redirect(home_url());
            exit;
        }
    }

    /**
     * Tipos MIME permitidos para subida
     */
    public static function mimesPermitidos(array $mimes): array
    {
        $mimes['flp'] = 'application/octet-stream';
        $mimes['zip'] = 'application/zip';
        $mimes['rar'] = 'application/x-rar-compressed';
        $mimes['cubase'] = 'application/octet-stream';
        $mimes['proj'] = 'application/octet-stream';
        $mimes['aiff'] = 'audio/aiff';
        $mimes['midi'] = 'audio/midi';
        $mimes['ptx'] = 'application/octet-stream';
        $mimes['sng'] = 'application/octet-stream';
        $mimes['aup'] = 'application/octet-stream';
        $mimes['omg'] = 'application/octet-stream';
        $mimes['rpp'] = 'application/octet-stream';
        $mimes['xpm'] = 'image/x-xpixmap';
        $mimes['tst'] = 'application/octet-stream';

        return $mimes;
    }

    /**
     * Mantener sesión activa por mucho tiempo
     */
    public static function mantenerSesionActiva(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        $expiracion = time() + 1421150815;

        setcookie(TEST_COOKIE, 'wordpress_test_cookie', $expiracion, SITECOOKIEPATH, COOKIE_DOMAIN);

        if (isset($_COOKIE[AUTH_COOKIE])) {
            setcookie(AUTH_COOKIE, $_COOKIE[AUTH_COOKIE], $expiracion, SITECOOKIEPATH, COOKIE_DOMAIN);
        }

        if (isset($_COOKIE[SECURE_AUTH_COOKIE])) {
            setcookie(SECURE_AUTH_COOKIE, $_COOKIE[SECURE_AUTH_COOKIE], $expiracion, SITECOOKIEPATH, COOKIE_DOMAIN);
        }

        if (isset($_COOKIE[LOGGED_IN_COOKIE])) {
            setcookie(LOGGED_IN_COOKIE, $_COOKIE[LOGGED_IN_COOKIE], $expiracion, SITECOOKIEPATH, COOKIE_DOMAIN);
        }
    }

    /**
     * Handler AJAX para mantener sesión viva
     */
    public static function manejarMantenerSesion(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuario no autenticado.');
        }
        wp_send_json_success();
    }

    /**
     * Tiempo de expiración de cookies (10 años)
     */
    public static function tiempoExpiracionCookies($expira, $userId, $recordar): int
    {
        return 315360000;
    }

    /**
     * Agregar reglas de reescritura para samples
     */
    public static function agregarReglasReescritura(): void
    {
        add_rewrite_rule('^sample/([0-9]+)/?$', 'index.php?p=$matches[1]&post_type=social_post', 'top');
    }

    /**
     * Modificar consulta principal para social posts por ID
     */
    public static function modificarConsultaPrincipal($consulta): void
    {
        if (!is_admin() && $consulta->is_main_query() && $consulta->get('p') && $consulta->get('post_type') === 'social_post') {
            $consulta->set('name', '');
        }
    }

    /**
     * Forzar plantilla para social posts
     */
    public static function forzarPlantillaSocialPost($template): string
    {
        global $wp_query;
        if (
            isset($wp_query->query_vars['post_type']) &&
            $wp_query->query_vars['post_type'] === 'social_post' &&
            isset($wp_query->query_vars['p'])
        ) {
            $plantilla = locate_template('single-social_post.php');
            if ($plantilla) {
                return $plantilla;
            }
        }
        return $template;
    }

    /**
     * Remplazar función obsoleta de skip link
     */
    public static function remplazarFuncionObsoleta(): void
    {
        remove_action('wp_footer', 'the_block_template_skip_link');
        add_action('wp_footer', 'wp_enqueue_block_template_skip_link');
    }

    /**
     * Fix para textdomain de Really Simple SSL
     */
    public static function fixTextdomainSSL(): void
    {
        if (function_exists('_load_textdomain_just_in_time')) {
            load_plugin_textdomain('really-simple-ssl', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }
    }
}
