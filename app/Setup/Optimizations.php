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
    add_filter('tiny_mce_plugins', __NAMESPACE__ . '\\disable_emojis_tinymce');
    add_filter('wp_resource_hints', __NAMESPACE__ . '\\disable_emojis_remove_dns_prefetch', 10, 2);
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
