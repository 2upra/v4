<?php

function enqueue_custom_scripts()
{
    // Scripts locales
    $scripts = [
        ['handle' => 'fan-script', 'src' => '/js/fan.js', 'version' => '1.0.36'],
        ['handle' => 'progreso-script', 'src' => '/js/progreso.js', 'version' => '1.0.23'],
        ['handle' => 'modal', 'src' => '/js/modal.js', 'version' => '1.0.22'],
        ['handle' => 'alert', 'src' => '/js/alert.js', 'version' => '1.0.4'],
        ['handle' => 'submenu', 'src' => '/js/submenu.js', 'version' => '1.2.15'],
        ['handle' => 'pestanas', 'src' => '/js/pestanas.js', 'version' => '1.1.10'],
        ['handle' => 'grafico', 'src' => '/js/grafico.js', 'version' => '1.0.23', 'deps' => ['jquery', 'lightweight-charts']],
        ['handle' => 'configPerfiljs', 'src' => '/js/configPerfil.js', 'version' => '1.0.14'],
        ['handle' => 'registro', 'src' => '/js/registro.js', 'version' => '1.0.12'],
        ['handle' => 'grain', 'src' => '/js/grained.js', 'version' => '1.0.3'],
        ['handle' => 'subida', 'src' => '/js/subida.js', 'version' => '1.1.21'],
        ['handle' => 'formScriptFront', 'src' => '/js/formSubirRola.js', 'version' => '4.1.53'],
        ['handle' => 'social-post-script', 'src' => '/js/ajax-submit.js', 'version' => '2.1.38'],
        ['handle' => 'form-script', 'src' => '/js/formscript.js', 'version' => '1.1.11'],
        ['handle' => 'estados', 'src' => '/js/estados.js', 'version' => '2.1.13'],
        ['handle' => 'wavejs', 'src' => 'js/wavejs.js', 'version' => '2.0.13'], 
    ];

    foreach ($scripts as $script) {
        wp_enqueue_script(
            $script['handle'],
            get_template_directory_uri() . $script['src'],
            isset($script['deps']) ? $script['deps'] : ['jquery'],
            $script['version'],
            true
        );
    }

    // Scripts externos
    // wp_enqueue_script('lightweight-charts', 'https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js', [], null, true);
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns', ['chart-js'], null, true);

    // Localización de scripts
    wp_localize_script('subida', 'my_ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
    wp_localize_script('social-post-script', 'my_ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'social_post_nonce' => wp_create_nonce('social-post-nonce'),
    ]);

    $is_admin = current_user_can('administrator') ? true : false;
    wp_localize_script('form-script', 'wpData', ['isAdmin' => $is_admin]);
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');




