<?php

require_once ABSPATH . 'wp-content/stripe/init.php';
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function guardarLog($log)
{
    $log_option_name = 'wanlog_logs';
    $logs = get_option($log_option_name, []);
    $timestamped_log = date('Y-m-d H:i:s') . ' - ' . $log;

    array_unshift($logs, $timestamped_log);
    $logs = array_slice($logs, 0, 100);
    update_option($log_option_name, $logs);

    $log_file = '/var/www/wordpress/wp-content/themes/logsw.txt';
    file_put_contents($log_file, $timestamped_log . PHP_EOL, FILE_APPEND);

    $line_count = count(file($log_file));
    if ($line_count > 400) {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_lines = array_slice($lines, -400);
        file_put_contents($log_file, implode(PHP_EOL, $new_lines) . PHP_EOL);
    }
}

//Script principal
function scriptsOrdenados()
{
    $dev_mode = true;
    $error_log = [];

    $script_handles = [
        'ajaxPage' => '5.0.11',
        'autorows' => '1.0.1',
        'fan' => '1.0.36',
        'stripeAccion' => '1.0.6',
        'reproductor' => '2.1.2',
        'reporte' => '1.0.17',
        'stripepro' => '1.0.8',
        'progreso' => '1.0.23',
        'modal' => '1.0.22',
        'alert' => '1.0.4',
        'submenu' => '1.2.15',
        'pestanas' => '1.1.10',
        'tagify' => '2.0.1',
        'FormRS' => '1.0.1',
        'configPerfil' => '1.0.14',
        'registro' => '1.0.12',
        'colab' => '1.0.2',
        'grained' => '1.0.3',
        'subida' => '1.1.21',
        //'formSubirRola' => '4.1.56',
        'ajax-submit' => '2.1.38',
        'formscript' => '1.1.11',
        'genericAjax' => '2.1.13',
        'wavejs' => ['2.0.12', ['jquery', 'wavesurfer']],
        'inversores' => '1.0.4',
        'likes' => '2.0.1',
    ];

    foreach ($script_handles as $handle => $data) {
        $version = is_array($data) ? $data[0] : $data;
        $deps = is_array($data) && isset($data[1]) ? $data[1] : [];
        $version = $dev_mode ? $version . '.' . mt_rand() : $version;

        $script_url = get_template_directory_uri() . "/js/{$handle}.js";
        if (!file_exists(get_template_directory() . "/js/{$handle}.js")) {
            $error_log[] = "Error: El archivo {$handle}.js no existe.";
            continue;
        }

        wp_enqueue_script(
            $handle,
            $script_url,
            $deps,
            $version,
            true
        );
    }

    // Scripts externos
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns', ['chart-js'], null, true);
    wp_enqueue_script('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.7.3', true);
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

    // Registrar errores si los hay
    if (!empty($error_log)) {
        error_log("Errores en scriptsOrdenados: " . print_r($error_log, true));
    }
}

add_action('wp_enqueue_scripts', 'scriptsOrdenados');

function limpiarLogs()
{
    $log_files = array(
        ABSPATH . 'wp-content/themes/wanlog.txt',
        ABSPATH . 'wp-content/themes/wanlogAjax.txt',
        ABSPATH . 'wp-content/uploads/access_logs.txt',
        ABSPATH . 'wp-content/themes/logsw.txt',
        ABSPATH . 'wp-content/debug.log'
    );

    foreach ($log_files as $file) {
        if (file_exists($file)) {
            $file_size = filesize($file) / (1024 * 1024);
            if ($file_size > 1) {
                $lines = file($file);
                $lines = array_slice($lines, -1000);
                file_put_contents($file, implode('', $lines));
            }
        }
    }
}

// Programar la ejecución de la función
if (!wp_next_scheduled('clean_log_files_hook')) {
    wp_schedule_event(time(), 'hourly', 'clean_log_files_hook');
}
add_action('clean_log_files_hook', 'limpiarLogs');


function incluirArchivos($directorio)
{
    $ruta_completa = get_template_directory() . "/$directorio";

    $archivos = glob($ruta_completa . "*.php");
    foreach ($archivos as $archivo) {
        include_once $archivo;
    }

    $subdirectorios = glob($ruta_completa . "*/", GLOB_ONLYDIR);
    foreach ($subdirectorios as $subdirectorio) {
        $ruta_relativa = str_replace(get_template_directory() . '/', '', $subdirectorio);
        incluirArchivos($ruta_relativa);
    }
}

$directorios = [
    'app/',
];

foreach ($directorios as $directorio) {
    incluirArchivos($directorio);
}

function fonts()
{
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
    echo '<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;700&display=swap"></noscript>';
}
add_action('wp_head', 'fonts', 1);

// CARGAR LA BARRA DE CARGA
function loadingBar()
{
    echo '<style>
        #loadingBar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background-color: white; /* Color de la barra */
            transition: width 0.4s ease;
            z-index: 999999999999999;
        }
    </style>';

    echo '<div id="loadingBar"></div>';
}

add_action('wp_head', 'loadingBar');

//CALCULAR ALTURA CORRECTA CON SCRIPT
function scriptBasicos()
{
    wp_register_script('script-base', '');
    wp_enqueue_script('script-base');
    $script_inline = <<<EOD
document.addEventListener('DOMContentLoaded', function() {
    var backButton = document.getElementById('backButton');
    if(backButton) {
        backButton.addEventListener('click', function() {
            if (window.innerWidth <= 640) {
                document.querySelector('.galle-chat-text-block').style.display = 'none'; 
                document.querySelector('.user-conversations-block').style.display = 'flex'; 
                console.log('Mostrando la lista de conversaciones y ocultando el chat para dispositivos móviles.');
            }
        });
    }

    function setVHVariable() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }

    setVHVariable();
    window.addEventListener('resize', setVHVariable);
});
EOD;
    wp_add_inline_script('script-base', $script_inline);
}
add_action('wp_enqueue_scripts', 'scriptBasicos');
