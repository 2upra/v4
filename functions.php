<?
if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-content/stripe/init.php';
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
//
define('STRIPE_ERROR_ENABLED', true);
define('SEO_LOG_ENABLED', true);
define('GUARDAR_LOG_ENABLED', true);

//
define('LOG_AUDIO_ENABLED', false);
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
    error_log("Página cargada en: {$time}ms");
}
add_action('shutdown', 'debug_page_load_time');
*/

// Añadir iconos personalizados en el <head> de todas las páginas
function headGeneric()
{
?>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://2upra.com/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="57x57" href="https://2upra.com/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="https://2upra.com/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="https://2upra.com/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="https://2upra.com/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="https://2upra.com/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="https://2upra.com/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="https://2upra.com/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="https://2upra.com/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://2upra.com/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="https://2upra.com/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://2upra.com/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="https://2upra.com/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://2upra.com/favicon-16x16.png">
    <link rel="manifest" href="https://2upra.com/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="https://2upra.com/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

    <!-- Etiquetas Open Graph para Facebook y otras plataformas -->
    <meta property="og:title" content="<?php echo get_the_title(); ?>" />
    <meta property="og:description" content="Social Media para artistas" />
    <meta property="og:image" content="https://i0.wp.com/2upra.com/wp-content/uploads/2024/11/Pinterest_Download-47-28-818x1024.jpg?quality=60&strip=all" />
    <meta property="og:url" content="https://2upra.com" />
    <meta property="og:type" content="website" />

    <!-- Etiquetas de Twitter Cards -->
    <meta property="og:title" content="<?php echo get_the_title(); ?>" />
    <meta name="twitter:title" content="Social Media para artistas">
    <meta name="twitter:description" content="Descripción de tu página que aparecerá al compartir.">
    <meta name="twitter:image" content="https://i0.wp.com/2upra.com/wp-content/uploads/2024/11/Pinterest_Download-47-28-818x1024.jpg?quality=60&strip=all">
    <meta name="twitter:site" content="@wandorius" />

<?php
}
add_action('wp_head', 'headGeneric');

function preload_fonts()
{
    echo '<link rel="preload" href="https://2upra.com/wp-content/themes/2upra3v/assets/Fonts/SourceSans3-Regular.woff2" as="font" type="font/woff2" crossorigin>';
    echo '<link rel="preload" href="https://2upra.com/wp-content/themes/2upra3v/assets/Fonts/SourceSans3-Bold.woff2" as="font" type="font/woff2" crossorigin>';
}
add_action('wp_head', 'preload_fonts', 1);


/*
function encolar_sw_js()
{
    wp_enqueue_script(
        'sw-js',
        home_url('/sw.js'), // Ruta absoluta desde la raíz del dominio
        array(),
        '1.0.2',
        true
    );
}
add_action('wp_enqueue_scripts', 'encolar_sw_js');
*/
function escribirLog($mensaje, $archivo, $max_lineas = 1000)
{
    // Verificaciones iniciales de seguridad
    if (!is_writable(dirname($archivo))) {
        return false;
    }

    try {
        // Formatear el mensaje
        if (is_object($mensaje) || is_array($mensaje)) {
            $mensaje = print_r($mensaje, true); // Más legible que json_encode
        }

        $timestamped_log = date('Y-m-d H:i:s') . ' - ' . $mensaje;

        // Usar un bloqueo de archivo para evitar problemas de concurrencia
        $fp = fopen($archivo, 'a');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $timestamped_log . PHP_EOL);

            // Gestionar el límite de líneas solo ocasionalmente (por ejemplo, 1 de cada 10 veces)
            if (rand(1, 10) === 1) {
                // Leer el archivo
                $lines = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (count($lines) > $max_lineas) {
                    // Mantener solo las últimas líneas
                    $lines = array_slice($lines, -$max_lineas);

                    // Reescribir el archivo
                    file_put_contents($archivo, implode(PHP_EOL, $lines) . PHP_EOL);
                }
            }

            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    } catch (Exception $e) {
        error_log("Error escribiendo log: " . $e->getMessage());
        return false;
    }
}

// sudo touch /var/www/wordpress/wp-content/themes/streamLog.log && sudo chown www-data:www-data /var/www/wordpress/wp-content/themes/streamLog.log && sudo chmod 664 /var/www/wordpress/wp-content/themes/streamLog.log

function streamLog($log)
{
    if (STREAM_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/streamLog.log');
    }
}


function seoLog($log)
{
    if (SEO_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/seoLog.log');
    }
}


function logAudio($log)
{
    if (LOG_AUDIO_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/logAudio.log');
    }
}

function chatLog($log)
{
    if (CHAT_LOG_ENABLED && current_user_can('administrator')) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/chat.log');
    }
}

function stripeError($log)
{
    if (STRIPE_ERROR_ENABLED && current_user_can('administrator')) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/stripeError.log');
    }
}

function autLog($log)
{
    escribirLog($log, '/var/www/wordpress/wp-content/themes/automaticPost.log');
}

function guardarLog($log)
{
    if (GUARDAR_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/logsw.txt');
    }
}

function logAlgoritmo($log)
{
    if (LOG_ALGORITMO_ENABLED && current_user_can('administrator')) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/logAlgoritmo.log', 100);
    }
}

function ajaxPostLog($log)
{
    if (AJAX_POST_LOG_ENABLED && current_user_can('administrator')) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/wanlogAjax.txt');
    }
}

function iaLog($log)
{
    if (IA_LOG_ENABLED && current_user_can('administrator')) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/iaLog.log');
    }
}

function postLog($log)
{
    if (POST_LOG_ENABLED && current_user_can('administrator')) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/wanlog.txt');
    }
}


function custom_deregister_jquery()
{
    // Verifica si es la página de inicio y si el usuario NO está logueado
    if (is_front_page() && !is_user_logged_in()) {
        // Desregistrar jQuery para que no se cargue
        wp_deregister_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'custom_deregister_jquery', 100); // Prioridad alta para asegurarse de que se ejecuta después de que otros scripts se hayan registrado.

function scriptsOrdenados()
{
    // Definir la versión global
    $global_version = '3.0.16'; // Cambia esta versión cuando desees actualizar todos los scripts
    // Verificar si el usuario actual es administrador
    $dev_mode = current_user_can('administrator');
    $error_log = [];

    // Definir scripts que no se deben cargar si el usuario no está logueado
    $scripts_only_for_logged_in_users = [
        'galleV2',
        'likes',
        'descargas',
        'fan',
        'RS',
        'progreso',
        'configPerfil',
        'stripeAccion',
        'likes',
        'autorows',
        'stripepro',
        'subida',
        'hashs',
        'ajax-submit',
        'formsscript',
        'notificaciones',
        'colec',
        'contarVistaPost',
        'seguir',
        'inversores',
        'genericAjax'
    ];

    $script_handles = [
        'gloria' => '1.0.1',
        'ajaxPage' => '5.0.11',
        'autorows' => '1.0.1',
        'fan' => '1.0.36',
        'stripeAccion' => '1.0.6',
        'reproductor' => '2.1.2',
        'stripepro' => '1.0.8',
        'progreso' => '1.0.23',
        'modal' => '1.0.22',
        'alert' => '1.0.4',
        'submenu' => '1.2.15',
        'descargas' => '2.0.1',
        'pestanas' => '1.1.10',
        'tagify' => '2.0.1',
        'wavesDos' => '1.0.1',
        'configPerfil' => '1.0.14',
        'diferido-post' => '4.0.0',
        'registro' => '1.0.12',
        'colab' => '1.0.2',
        'grained' => '1.0.3',
        'subida' => '1.1.21',
        'RS' => '1.0.1',
        'tagsPosts' => '1.0.1',
        'hashs' => '1.0.1',
        'background' => '1.0.1',
        //'formSubida' => '4.1.56',
        'ajax-submit' => '2.1.38',
        'formscript' => '1.1.11',
        'genericAjax' => '2.1.13',
        'wavejs' => ['2.0.19', ['jquery', 'wavesurfer']],
        'inversores' => '1.0.4',
        'likes' => '2.0.1',
        'seguir' => '2.0.1',
        'galleV2' => '2.0.1',
        'cambiarVistas' => '1.0.1',
        'contarVistaPost' => '1.0.1',
        'notificaciones' => '1.0.1',
        'colec' => '1.0.1',
    ];

    foreach ($script_handles as $handle => $data) {
        // Verificar si el script debería cargarse solo para usuarios logueados
        if (!is_user_logged_in() && in_array($handle, $scripts_only_for_logged_in_users)) {
            continue; // No cargar el script si el usuario no está logueado
        }

        // Usar la versión global en lugar de la definida en $script_handles
        $version = $global_version; // Esta es la única versión que usaremos para todos los scripts
        $deps = is_array($data) && isset($data[1]) ? $data[1] : [];
        $version = $dev_mode ? $version . '.' . mt_rand() : $version;

        // Verificar si el archivo del script existe
        $script_url = get_template_directory_uri() . "/js/{$handle}.js";
        if (!file_exists(get_template_directory() . "/js/{$handle}.js")) {
            $error_log[] = "Error: El archivo {$handle}.js no existe.";
            continue;
        }

        // Encolar el script
        wp_enqueue_script(
            $handle,
            $script_url,
            $deps,
            $version,
            true
        );
    }


    if (is_user_logged_in()) {
        $nonce = wp_create_nonce('wp_rest');
        wp_localize_script('galleV2', 'galleV2', array(
            'nonce' => $nonce,
            'apiUrl' => esc_url_raw(rest_url('galle/v2/guardarMensaje/')),
            'emisor' => get_current_user_id()
        ));
    }

    wp_localize_script('ajaxPage', 'ajaxPage', array(
        'logeado' => get_current_user_id() ? true : false
    ));
    if (is_user_logged_in()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns', ['chart-js'], null, true);
    }

    // Scripts externos


    // Verifica si es la página de inicio y si el usuario NO está logueado
    if (is_front_page() && !is_user_logged_in()) {
        // No cargar jQuery ni WaveSurfer.js
        wp_dequeue_script('jquery');
        wp_dequeue_script('wavesurfer');
    }
    wp_enqueue_script('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.7.10', true);
    wp_add_inline_script('genericAjax', 'const wpAdminUrl = "' . admin_url() . '";', 'before');

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

    wp_localize_script('reproductor', 'audioSettings', array(
        'nonce' => wp_create_nonce('wp_rest'),
    ));

    wp_localize_script('wavejs', 'audioSettings', array(
        'nonce' => wp_create_nonce('wp_rest'),
        'encryptionKey' => $_ENV['AUDIOCLAVE'],
        'key' => $_ENV['AUDIOCLAVE'],
        'restUrl' => rest_url()
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
            $file_size = filesize($file) / (1024 * 1024); // Size in MB

            if ($file_size > 1) {
                // Use SplFileObject for memory-efficient file handling
                try {
                    $temp_file = $file . '.temp';
                    $fp_out = fopen($temp_file, 'w');

                    if ($fp_out === false) {
                        continue;
                    }

                    $file_obj = new SplFileObject($file, 'r');

                    // Move file pointer to end
                    $file_obj->seek(PHP_INT_MAX);
                    $total_lines = $file_obj->key();

                    // Calculate start position for last 2000 lines
                    $start_line = max(0, $total_lines - 2000);

                    // Reset pointer
                    $file_obj->rewind();

                    $current_line = 0;
                    while (!$file_obj->eof()) {
                        if ($current_line >= $start_line) {
                            fwrite($fp_out, $file_obj->current());
                        }
                        $file_obj->next();
                        $current_line++;
                    }

                    fclose($fp_out);

                    // Replace original file with temp file
                    if (file_exists($temp_file)) {
                        unlink($file);
                        rename($temp_file, $file);
                    }
                } catch (Exception $e) {
                    // Log error or handle exception
                    error_log("Error processing log file {$file}: " . $e->getMessage());

                    // Clean up temp file if it exists
                    if (isset($temp_file) && file_exists($temp_file)) {
                        unlink($temp_file);
                    }
                }
            }
        }
    }
}

// Programar la ejecución de la función
if (!wp_next_scheduled('clean_log_files_hook')) {
    wp_schedule_event(time(), 'hourly', 'clean_log_files_hook');
}
add_action('clean_log_files_hook', 'limpiarLogs');


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
function innerHeight()
{
    wp_register_script('script-base', '');
    wp_enqueue_script('script-base');
    $script_inline = <<<EOD
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
add_action('wp_enqueue_scripts', 'innerHeight');
