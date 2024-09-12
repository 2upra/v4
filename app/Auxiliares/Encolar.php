<?php

function scriptsOrdenados()
{
    $script_handles = [
        'fan' => '1.0.36',
        'stripeAccion' => '1.0.6',
        'reproductor' => '2.1.2',
        'reporte' => '1.0.17',
        'ajaxPage' => '4.0.1',
        'stripepro' => '1.0.8',
        'progreso' => '1.0.23',
        'modal' => '1.0.22',
        'alert' => '1.0.4',
        'submenu' => '1.2.15',
        'pestanas' => '1.1.10',
        'tagify' => '2.0.1',
        'configPerfil' => '1.0.14',
        'registro' => '1.0.12',
        'colab' => '1.0.2',
        'grained' => '1.0.3',
        'subida' => '1.1.21',
        'formSubirRola' => '4.1.56',
        'ajax-submit' => '2.1.38',
        'formscript' => '1.1.11',
        'estados' => '2.1.13',
        'wavejs' => ['2.0.12', ['jquery', 'wavesurfer']],
        'inversores' => '1.0.4',
        'likes' => '2.0.1',
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
    wp_enquere_script('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.7.3', true);
    wp_enqueue_script('jquery');

    // Localización de scripts
    $ajax_url = admin_url('admin-ajax.php');
    wp_localize_script('subida', 'my_ajax_object', ['ajax_url' => $ajax_url]);
    wp_localize_script('social-post-script', 'my_ajax_object', [
        'ajax_url' => $ajax_url,
        'social_post_nonce' => wp_create_nonce('social-post-nonce'),
    ]);
    wp_localize_script('my-ajax-script', 'ajax_params', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));

    wp_localize_script('wavejs', 'ajax_params', ['ajaxurl' => $ajax_url]);
    wp_localize_script('form-script', 'wpData', ['isAdmin' => current_user_can('administrator')]);
}

add_action('wp_enqueue_scripts', 'scriptsOrdenados');
