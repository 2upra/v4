<?php
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$stripe_init_path = ABSPATH . 'wp-content/stripe/init.php';

if (file_exists($stripe_init_path)) {
    require_once $stripe_init_path;
}
/*
composer install --ignore-platform-reqs
*/
require_once __DIR__ . '/vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    //error_log('Error al cargar el archivo .env: ' . $e->getMessage());
    if (!isset($_ENV['DATABASE_HOST'])) {
        $_ENV['DATABASE_HOST'] = 'localhost';
        $_ENV['AUDIOCLAVE'] = 'e1d78b9adf3466f98b7e53e1e7f21dfe723b1ccd0f93a09a2b9bdf3905a5fd07';
    }
}

define('STRIPE_ERROR_ENABLED', true);
define('SEO_LOG_ENABLED', true);
define('GUARDAR_LOG_ENABLED', true);

//
define('LOG_AUDIO_ENABLED', false);
define('RENDIMIENTO_ENABLED', true);
define('CHAT_LOG_ENABLED', false);
define('AUT_LOG_ENABLED', true);
define('LOG_ALGORITMO_ENABLED', false);
define('AJAX_POST_LOG_ENABLED', false);
define('IA_LOG_ENABLED', true);
define('POST_LOG_ENABLED', false);
define('STREAM_LOG_ENABLED', false);
define('INTERES_TABLE', "{$wpdb->prefix}interes");
define('POSTINLIMIT', 640);
/*
function debug_page_load_time() {
    $time = number_format((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2);
    error_log("Pagina cargada en: {$time}ms");
}
add_action('shutdown', 'debug_page_load_time');
*/




/*
function encolar_sw_js()
{
    wp_enqueue_script(
        'sw-js',
        home_url('/sw.js'), // Ruta absoluta desde la raiz del dominio
        array(),
        '1.0.2',
        true
    );
}
add_action('wp_enqueue_scripts', 'encolar_sw_js');
*/


//wave



function custom_site_icon($meta_tags)
{
    $meta_tags[] = sprintf('<link rel="icon" href="%s">', 'https://2upra.com/wp-content/themes/2upra3v/assets/icons/favicon-96x96.png');
    return $meta_tags;
}
add_filter('site_icon_meta_tags', 'custom_site_icon');


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

?>