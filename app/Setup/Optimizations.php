<?php

/**
 * Optimizations
 *
 * Este archivo agrupa funciones y hooks relacionados con la optimización
 * del rendimiento de WordPress.
 *
 * Ejemplos:
 * - Desactivar emojis
 * - Desactivar embeds
 * - Limpiar scripts/estilos innecesarios
 * - Optimizar la cabecera (wp_head)
 * - Optimizar la base de datos (revisiones, etc.)
 */

// Aquí se añadirán las funciones y hooks de optimización.


/**
 * Desactiva completamente los emojis de WordPress.
 */
function disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
    add_filter('wp_resource_hints', 'disable_emojis_remove_dns_prefetch', 10, 2);
}

/**
 * Filtra los plugins de TinyMCE para eliminar el de emojis.
 *
 * @param array $plugins Array de plugins activos.
 * @return array Array de plugins sin el de emojis.
 */
function disable_emojis_tinymce($plugins) {
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    } else {
        return array();
    }
}

/**
 * Elimina la URL de DNS prefetch para los emojis SVG de los resource hints.
 *
 * @param array  $urls          URLs para prefetch.
 * @param string $relation_type Tipo de relación ('dns-prefetch' o 'preconnect').
 * @return array URLs filtradas.
 */
function disable_emojis_remove_dns_prefetch($urls, $relation_type) {
    if ('dns-prefetch' === $relation_type) {
        /** This filter is documented in wp-includes/formatting.php */
        $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/13.0.1/svg/'); // Usando la URL del ejemplo original
        $urls = array_diff($urls, array($emoji_svg_url));
    }
    return $urls;
}


add_action('init', 'disable_emojis');

/**
 * Desactiva completamente los embeds de WordPress.
 */
function desactivar_embeds()
{
    // Deshabilitar scripts y estilos relacionados con embeds
    wp_dequeue_script('wp-embed');

    // Eliminar acciones relacionadas con embeds
    remove_action('rest_api_init', 'wp_oembed_register_route');
    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');

    // Desactivar shortcodes oembed
    add_filter('embed_oembed_discover', '__return_false');
    remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
}
add_action('init', 'desactivar_embeds', 9999);

/**
 * Elimina scripts y estilos innecesarios del frontend y backend.
 */
function eliminar_scripts_y_estilos()
{
    // Ejemplo: Eliminar el script de emoji (ya desactivado anteriormente)
    wp_dequeue_script('wp-emoji');
    wp_dequeue_style('wp-emoji');
    // Eliminar Gutenberg Block Library CSS
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-block-style'); // WooCommerce
    // Eliminar Dashicons en el frontend si no se usan
    if (! is_admin()) {
        wp_dequeue_style('dashicons');
    }
    // Eliminar estilos de Gutenberg en el frontend
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-block-style');
    // Eliminar estilos de la admin bar si no se usa
    if (! is_admin()) {
        wp_dequeue_style('admin-bar');
    }
}
add_action('wp_enqueue_scripts', 'eliminar_scripts_y_estilos', 100);
add_action('admin_enqueue_scripts', 'eliminar_scripts_y_estilos', 100);
