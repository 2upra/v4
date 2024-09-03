<?php 


function css_analyzer_page() {
    ?>
    <div class="wrap">
        <h1>CSS Analyzer</h1>
        <form method="post" action="">
            <?php wp_nonce_field('css_analyzer_action', 'css_analyzer_nonce'); ?>
            <input type="submit" name="analyze_css" class="button button-primary" value="Analizar y Optimizar CSS">
        </form>
        <?php
        if (get_option('css_analyzer_backup')) {
            ?>
            <form method="post" action="">
                <?php wp_nonce_field('css_analyzer_restore_action', 'css_analyzer_restore_nonce'); ?>
                <input type="submit" name="restore_css" class="button button-secondary" value="Restaurar CSS Original">
            </form>
            <?php
        }
        ?>
    </div>
    <?php

    if (isset($_POST['analyze_css']) && check_admin_referer('css_analyzer_action', 'css_analyzer_nonce')) {
        analyze_and_optimize_css();
    }

    if (isset($_POST['restore_css']) && check_admin_referer('css_analyzer_restore_action', 'css_analyzer_restore_nonce')) {
        restore_original_css();
    }
}

function analyze_and_optimize_css() {
    $custom_css = wp_get_custom_css();
    
    // Crear respaldo del CSS original
    update_option('css_analyzer_backup', $custom_css);
    
    $site_html = get_site_html();
    $used_css = find_used_css($custom_css, $site_html);

    // Actualizar el CSS personalizado con solo el CSS utilizado
    wp_update_custom_css_post($used_css);

    echo '<h2>CSS optimizado aplicado:</h2>';
    echo '<pre>' . esc_html($used_css) . '</pre>';
    echo '<p>Se ha creado un respaldo del CSS original. Puedes restaurarlo si es necesario.</p>';
}

function restore_original_css() {
    $original_css = get_option('css_analyzer_backup');
    if ($original_css) {
        wp_update_custom_css_post($original_css);
        delete_option('css_analyzer_backup');
        echo '<p>El CSS original ha sido restaurado.</p>';
    } else {
        echo '<p>No se encontró un respaldo del CSS original.</p>';
    }
}

function css_analyzer_menu_and_page() {
    add_menu_page('CSS Analyzer', 'CSS Analyzer', 'manage_options', 'css-analyzer', 'css_analyzer_page');
}

function get_site_html() {
    $html = '';
    $args = array(
        'post_type' => 'any',
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $html .= get_the_content();
        }
    }
    wp_reset_postdata();

    $html .= get_header();
    $html .= get_footer();

    return $html;
}

function find_used_css($css, $html) {
    $used_css = '';
    $css_rules = explode('}', $css);

    foreach ($css_rules as $rule) {
        $rule = trim($rule);
        if (empty($rule)) continue;

        $selector = explode('{', $rule)[0];
        $selector = trim($selector);

        if (strpos($html, $selector) !== false) {
            $used_css .= $rule . "}\n";
        }
    }

    return $used_css;
}

// Activar la función
add_action('admin_menu', 'css_analyzer_menu_and_page');