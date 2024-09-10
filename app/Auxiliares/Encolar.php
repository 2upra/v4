<?php


function script_stripeacciones()
{
    wp_enqueue_script('script_stripeacciones', get_template_directory_uri() . '/js/stripeacciones.js', array(), '1.0.6', true);
}
add_action('wp_enqueue_scripts', 'script_stripeacciones');

function script_stripe_pro() {
    wp_enqueue_script('script_stripe_pro', get_template_directory_uri() . '/js/stripepro.js', array(), '1.0.8', true);
}
add_action('wp_enqueue_scripts', 'script_stripe_pro');

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('reporte', get_template_directory_uri() . '/js/reporte.js', ['jquery'], '1.0.17', true);
    wp_localize_script('reporte', 'miAjax', ['ajaxurl' => admin_url('admin-ajax.php')]);
});
function cargar_reproductor_js() {
    wp_enqueue_script('reproductor-audio', get_template_directory_uri() . '/js/reproductor.js', [], '2.1.2', true);
}
add_action('wp_enqueue_scripts', 'cargar_reproductor_js');


function enqueue_custom_scripts()
{
    $script_handles = [
        'fan' => '1.0.36',
        'progreso' => '1.0.23',
        'modal' => '1.0.22',
        'alert' => '1.0.4',
        'submenu' => '1.2.15',
        'pestanas' => '1.1.10',
        'tagify' => '2.0.1',
        //'grafico' => ['1.0.23', ['jquery', 'lightweight-charts']],
        'configPerfil' => '1.0.14',
        'registro' => '1.0.12',
        'grained' => '1.0.3',
        'subida' => '1.1.21',
        'formSubirRola' => '4.1.54',
        'ajax-submit' => '2.1.38',
        'formscript' => '1.1.11',
        'estados' => '2.1.13',
        'wavejs' => ['2.0.12', ['jquery', 'wavesurfer']],
        'inversores' => '1.0.4',
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


