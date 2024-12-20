<?
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

function paginasIniciales1()
{
    // Verificar si las páginas ya fueron creadas
    if (get_option('paginasIniciales1') == '1') return;


    if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === false)) {
        update_option('paginasIniciales1', '1');
        return;
    }


    $paginas = array(
        'Inicio' => array(
            'plantilla' => 'TemplateInicio.php',
            'contenido' => 'Este es el contenido de la página de inicio.'
        ),
        'Colab' => array(
            'plantilla' => 'TemplateColab.php',
            'contenido' => ''
        ),
        'Dev' => array(
            'plantilla' => 'TemplateDev.php',
            'contenido' => ''
        ),
        'Colec' => array(
            'plantilla' => 'TemplateColec.php',
            'contenido' => ''
        ),
        'Feed' => array(
            'plantilla' => 'TemplateFeed.php',
            'contenido' => ''
        ),
        'FeedSample' => array(
            'plantilla' => 'TemplateFeedSample.php',
            'contenido' => ''
        ),
        'Inversor' => array(
            'plantilla' => 'TemplateInversor.php',
            'contenido' => ''
        ),
        'Music' => array(
            'plantilla' => 'TemplateMusic.php',
            'contenido' => ''
        ),
        'Prueba' => array(
            'plantilla' => 'TemplatePrueba.php',
            'contenido' => ''
        ),
        'Sample' => array(
            'plantilla' => 'TemplateSample.php',
            'contenido' => ''
        ),
        'Sello' => array(
            'plantilla' => 'TemplateSello.php',
            'contenido' => ''
        ),
        'T&Q' => array(
            'plantilla' => 'TemplateT&Q.php',
            'contenido' => ''
        ),
    );

    // Recorrer el array y crear las páginas
    $inicio_id = 0; // Variable para guardar el ID de la página de inicio
    foreach ($paginas as $titulo => $datos) {
        // Usar WP_Query en lugar de get_page_by_title
        $pagina_query = new WP_Query(array(
            'post_type' => 'page',
            'title'     => $titulo,
            'post_status' => 'any'
        ));

        if (!$pagina_query->have_posts()) {
            $nueva_pagina = array(
                'post_title'    => $titulo,
                'post_content'  => $datos['contenido'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'page_template' => $datos['plantilla']
            );

            $nueva_pagina_id = wp_insert_post($nueva_pagina);

            // Si la página creada es la de inicio, guardar su ID
            if ($titulo == 'Inicio') {
                $inicio_id = $nueva_pagina_id;
            }
        }

        // Liberar memoria
        wp_reset_postdata();
    }

    // Definir la página de inicio
    if ($inicio_id > 0) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $inicio_id);
    }

    // Marcar que las páginas ya fueron creadas
    update_option('paginasIniciales1', '1');
}

add_action('init', 'paginasIniciales1');


function headGeneric()
{
    if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === true)) {
        update_option('paginasIniciales1', '1');
        return;
    }
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


//wave

function scriptsOrdenados()
{
    $global_version = '0.2.234';
    $dev_mode = defined('LOCAL') && LOCAL;
    //$error_log = [];

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
        'genericAjax',
        'comentarios',
        'stripeCompra'
    ];

    $script_handles = [
        'gloria'            => '1.0.1',
        'ajaxPage'          => '5.0.11',
        'autorows'          => '1.0.1',
        'busqueda'          => '1.0.1',
        'fan'               => '1.0.36',
        'stripeAccion'      => '1.0.6',
        'reproductor'       => '2.1.2',
        'stripepro'         => '1.0.8',
        'progreso'          => '1.0.23',
        'modal'             => '1.0.22',
        'alert'             => '1.0.4',
        'submenu'           => '1.2.15',
        'descargas'         => '2.0.1',
        'pestanas'          => '1.1.10',
        'tagify'            => '2.0.1',
        'wavesDos'          => '1.0.1',
        'configPerfil'      => '1.0.14',
        'diferido-post'     => '4.0.0',
        'registro'          => '1.0.12',
        'colab'             => '1.0.2',
        'grained'           => '1.0.3',
        'subida'            => '1.1.21',
        'RS'                => '1.0.1',
        'tagsPosts'         => '1.0.1',
        'hashs'             => '1.0.1',
        'background'        => '1.0.1',
        'ajax-submit'       => '2.1.38',
        'formscript'        => '1.1.11',
        'genericAjax'       => '2.1.13',
        'wavejs'            => ['2.0.20', ['jquery', 'wavesurfer']],
        'inversores'        => '1.0.4',
        'likes'             => '2.0.1',
        'seguir'            => '2.0.1',
        'galleV2'           => '2.0.1',
        'cambiarVistas'     => '1.0.1',
        'contarVistaPost'   => '1.0.1',
        'notificaciones'    => '1.0.1',
        'comentarios'       => '1.0.1',
        'colec'             => '1.0.1',
        'stripeCompra'      => '1.0.1',
        'tooltips'          => '1.0.1',
    ];

    wp_enqueue_script('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.8.11', true);
    // Registro de la configuración inicial
    //$error_log[] = "Modo de desarrollo activado: " . ($dev_mode ? 'Sí' : 'No');
    //$error_log[] = "Versión global de scripts: " . $global_version;

    foreach ($script_handles as $handle => $data) {
        $version = $global_version;
        $deps = is_array($data) && isset($data[1]) ? $data[1] : [];

        if (!is_user_logged_in() && in_array($handle, $scripts_only_for_logged_in_users)) {
            //$error_log[] = "Usuario no logueado, omitiendo script: " . $handle;
            continue;
        }

        if ($dev_mode) {
            $version .= '.' . mt_rand();
        }

        $script_path = get_template_directory() . "/js/{$handle}.js";
        $script_url = get_template_directory_uri() . "/js/{$handle}.js";

        // Registro de cada script antes de verificar su existencia
        //$error_log[] = "Intentando cargar script: " . $handle . " desde " . $script_path;

        if (!file_exists($script_path)) {
            //$error_log[] = "Error: El archivo " . $handle . ".js no existe en la ruta: " . $script_path;
            continue;
        }

        wp_enqueue_script($handle, $script_url, $deps, $version, true);
        //$error_log[] = "Script " . $handle . " encolado correctamente con versión: " . $version;
    }


    // Scripts adicionales y localizaciones
    if (is_user_logged_in()) {
        $nonce = wp_create_nonce('wp_rest');
        wp_localize_script('galleV2', 'galleV2', [
            'nonce'     => $nonce,
            'apiUrl'    => esc_url_raw(rest_url('galle/v2/guardarMensaje/')),
            'emisor'    => get_current_user_id()
        ]);
        //$error_log[] = "Script galleV2 localizado con nonce y apiUrl para usuario logueado.";

        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns', ['chart-js'], null, true);
        //$error_log[] = "Scripts chart-js y chartjs-adapter-date-fns encolados para usuario logueado.";
    }

    wp_localize_script('ajaxPage', 'ajaxPage', ['logeado' => is_user_logged_in()]);
    //$error_log[] = "Script ajaxPage localizado.";



    wp_add_inline_script('genericAjax', 'const wpAdminUrl = "' . admin_url() . '";', 'before');
    //$error_log[] = "Script en línea para genericAjax añadido con wpAdminUrl.";

    // Localización de scripts adicionales
    $ajax_url = admin_url('admin-ajax.php');
    $script_localizations = [
        'subida'                => ['my_ajax_object', ['ajax_url' => $ajax_url]],
        'social-post-script'    => ['my_ajax_object', ['ajax_url' => $ajax_url, 'social_post_nonce' => wp_create_nonce('social-post-nonce')]],
        'my-ajax-script'        => ['ajax_params', ['ajax_url' => $ajax_url]],
        'reproductor'           => ['audioSettings', ['nonce' => wp_create_nonce('wp_rest')]],
        'wavejs'                => ['audioSettings', ['nonce' => wp_create_nonce('wp_rest'), 'encryptionKey' => $_ENV['AUDIOCLAVE'], 'key' => $_ENV['AUDIOCLAVE'], 'restUrl' => rest_url()]],
        'form-script'           => ['wpData', ['isAdmin' => current_user_can('administrator')]],
    ];

    foreach ($script_localizations as $handle => $data) {
        wp_localize_script($handle, $data[0], $data[1]);
        //$error_log[] = "Script " . $handle . " localizado con éxito.";
    }

    // Registro de errores
    if (!empty($error_log)) {
        $log_message = "Detalles de scriptsOrdenados:\n" . implode("\n", $error_log) . "\n";
        //error_log($log_message);
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
        var vh;
        if (window.visualViewport) {
            vh = window.visualViewport.height * 0.01;
        } else {
            vh = window.innerHeight * 0.01;
        }
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }

    document.addEventListener('DOMContentLoaded', function() {
        setVHVariable();

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', setVHVariable);
        } else {
            window.addEventListener('resize', setVHVariable);
        }
    });
EOD;
    wp_add_inline_script('script-base', $script_inline);
}
add_action('wp_enqueue_scripts', 'innerHeight');

