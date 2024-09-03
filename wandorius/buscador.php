<?php

function shortcode_buscador_sample() {
    $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    ob_start();
    ?>
    <form method="GET" class="busqueda-sample"  id="search-form">
        <input type="text" name="search" id="dynamic-placeholder" placeholder="Busqueda" value="<?php echo esc_attr($search_term); ?>" class="campo-busqueda">
        <button type="submit" class="fa-search fas button-icon-left" style="display: none;"></button>
    </form>
    <div id="resultados-busqueda"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('buscador_sample', 'shortcode_buscador_sample');

function add_custom_js_to_footer() {
    wp_enqueue_script('custom-search-js', get_template_directory_uri() . '/js/busqueda.js', array(), '1.10.7', true);
}
add_action('wp_footer', 'add_custom_js_to_footer');