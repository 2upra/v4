<?php


function enqueue_custom_scripts()
{
    $script_handles = [
        'fan-script' => '1.0.36',
        'progreso-script' => '1.0.23',
        'modal' => '1.0.22',
        'alert' => '1.0.4',
        'submenu' => '1.2.15',
        'pestanas' => '1.1.10',
        'grafico' => ['1.0.23', ['jquery', 'lightweight-charts']],
        'configPerfiljs' => '1.0.14',
        'registro' => '1.0.12',
        'grain' => '1.0.3',
        'subida' => '1.1.21',
        'formScriptFront' => '4.1.53',
        'social-post-script' => '2.1.38',
        'form-script' => '1.1.11',
        'estados' => '2.1.13',
        'wavejs' => ['2.0.12', ['jquery', 'wavesurfer']],
    ];

    foreach ($script_handles as $handle => $data) {
        $version = is_array($data) ? $data[0] : $data;
        $deps = is_array($data) && isset($data[1]) ? $data[1] : [];

        wp_enqueue_script(
            $handle,
            get_template_directory_uri() . "/js/{$handle}.js",
            $deps,
            $version,
            true
        );
    }

    // Scripts externos
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns', ['chart-js'], null, true);

    // Localización de scripts
    $ajax_url = admin_url('admin-ajax.php');
    wp_localize_script('subida', 'my_ajax_object', ['ajax_url' => $ajax_url]);
    wp_localize_script('social-post-script', 'my_ajax_object', [
        'ajax_url' => $ajax_url,
        'social_post_nonce' => wp_create_nonce('social-post-nonce'),
    ]);

    wp_localize_script('wavejs', 'ajax_params', ['ajaxurl' => $ajax_url]);
    wp_localize_script('form-script', 'wpData', ['isAdmin' => current_user_can('administrator')]);
}

add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


