<?php

/**
 * Optimización de WordPress
 * 
 * Desactiva funcionalidades innecesarias de WordPress para mejorar rendimiento
 *
 * @package Kamples\Core
 * @since 1.0.0
 */

namespace Kamples\Core;

class OptimizacionWP
{
    private static ?OptimizacionWP $instancia = null;

    private function __construct()
    {
        $this->inicializarOptimizaciones();
    }

    /**
     * Obtiene la instancia única
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa todas las optimizaciones
     */
    private function inicializarOptimizaciones(): void
    {
        $this->desactivarHeartbeat();
        $this->desactivarEmojis();
        $this->desactivarEmbeds();
        $this->eliminarScriptsInnecesarios();
        $this->desactivarFeeds();
        $this->desactivarAutosave();
        $this->eliminarWidgetsInnecesarios();
        $this->limpiarFooter();
        $this->eliminarAjustesDiscusion();
        $this->desactivarSoportesBloques();
        $this->eliminarVersionWP();
    }

    /**
     * Desactiva el script heartbeat
     */
    private function desactivarHeartbeat(): void
    {
        add_action('init', function () {
            wp_deregister_script('heartbeat');
        });
    }

    /**
     * Desactiva los emojis de WordPress
     */
    private function desactivarEmojis(): void
    {
        add_action('init', function () {
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        });
    }

    /**
     * Desactiva los embeds de WordPress
     */
    private function desactivarEmbeds(): void
    {
        add_action('init', function () {
            wp_dequeue_script('wp-embed');
            remove_action('rest_api_init', 'wp_oembed_register_route');
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            add_filter('embed_oembed_discover', '__return_false');
            remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
        }, 9999);
    }

    /**
     * Elimina scripts y estilos innecesarios
     */
    private function eliminarScriptsInnecesarios(): void
    {
        $callback = function () {
            wp_dequeue_script('wp-emoji');
            wp_dequeue_style('wp-emoji');
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('wc-block-style');

            if (!is_admin()) {
                wp_dequeue_style('dashicons');
                wp_dequeue_style('admin-bar');
            }
        };

        add_action('wp_enqueue_scripts', $callback, 100);
        add_action('admin_enqueue_scripts', $callback, 100);
        add_filter('use_block_editor_for_post', '__return_false', 10);
    }

    /**
     * Desactiva las feeds RSS
     */
    private function desactivarFeeds(): void
    {
        $desactivar = function () {
            wp_die(__('Las feeds RSS están deshabilitadas.'));
        };

        add_action('do_feed', $desactivar, 1);
        add_action('do_feed_rdf', $desactivar, 1);
        add_action('do_feed_rss', $desactivar, 1);
        add_action('do_feed_rss2', $desactivar, 1);
        add_action('do_feed_atom', $desactivar, 1);

        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'feed_links', 2);
    }

    /**
     * Desactiva el autosave
     */
    private function desactivarAutosave(): void
    {
        add_action('wp_print_scripts', function () {
            wp_deregister_script('autosave');
        });
    }

    /**
     * Elimina widgets innecesarios
     */
    private function eliminarWidgetsInnecesarios(): void
    {
        add_action('widgets_init', function () {
            unregister_widget('WP_Widget_Calendar');
            unregister_widget('WP_Widget_Meta');
            unregister_widget('WP_Widget_Search');
        }, 11);
    }

    /**
     * Limpia el footer de WordPress
     */
    private function limpiarFooter(): void
    {
        add_action('init', function () {
            remove_action('wp_footer', 'wp_admin_bar_render', 1000);
            remove_action('wp_footer', 'wp_footer');
        });
    }

    /**
     * Elimina la página de ajustes de discusión
     */
    private function eliminarAjustesDiscusion(): void
    {
        add_action('admin_menu', function () {
            remove_menu_page('options-discussion.php');
        });
    }

    /**
     * Desactiva todos los soportes de bloques
     */
    private function desactivarSoportesBloques(): void
    {
        add_filter('block_type_metadata_settings', function ($settings, $name) {
            $soportesADesactivar = [
                'align',
                'alignWide',
                'anchor',
                'color',
                'customClassName',
                'html',
                'typography',
                'spacing',
                'border',
                'gradients',
                'responsive',
                'fontSize',
                'links',
                'inserter',
                'multiple',
                'reusable',
                'lock'
            ];

            if (isset($settings['supports']) && is_array($settings['supports'])) {
                foreach ($soportesADesactivar as $soporte) {
                    if (isset($settings['supports'][$soporte])) {
                        unset($settings['supports'][$soporte]);
                    }
                }
            }
            return $settings;
        }, 10, 2);
    }

    /**
     * Elimina la versión de WordPress del HTML
     */
    private function eliminarVersionWP(): void
    {
        add_filter('the_generator', '__return_empty_string');
    }
}
