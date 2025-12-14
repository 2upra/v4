<?php

/**
 * Funciones principales del tema.
 * 
 * Este archivo ha sido refactorizado para seguir principios SOLID.
 * Las responsabilidades se han dividido en módulos dentro de /inc/.
 *
 * @package Theme_V4
 * @since 1.0.0
 * @see REFACTORIZACION.md para el historial de cambios
 */

// =============================================================================
// CARGA DE MÓDULOS DEL TEMA
// =============================================================================

// Configuración y constantes (debe cargarse primero)
require_once get_template_directory() . '/inc/Config/constants.php';

// Sistema de logging
require_once get_template_directory() . '/inc/Logging/Logger.php';

// =============================================================================
// DEPENDENCIAS EXTERNAS
// =============================================================================

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Stripe (opcional)
$stripeInitPath = ABSPATH . 'wp-content/stripe/init.php';
if (file_exists($stripeInitPath)) {
    require_once $stripeInitPath;
}

// Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// Variables de entorno
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    // Valores por defecto si no existe .env
    if (!isset($_ENV['DATABASE_HOST'])) {
        $_ENV['DATABASE_HOST'] = 'localhost';
        $_ENV['AUDIOCLAVE'] = 'e1d78b9adf3466f98b7e53e1e7f21dfe723b1ccd0f93a09a2b9bdf3905a5fd07';
    }
}
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
        'Biblioteca' => array(
            'plantilla' => 'TemplateBiblioteca.php',
            'contenido' => ''
        )
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
    <link rel="icon" href="<?php echo esc_url(site_url('/favicon.ico')); ?>" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo esc_url(site_url('/apple-icon-57x57.png')); ?>">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php echo esc_url(site_url('/apple-icon-60x60.png')); ?>">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo esc_url(site_url('/apple-icon-72x72.png')); ?>">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo esc_url(site_url('/apple-icon-76x76.png')); ?>">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo esc_url(site_url('/apple-icon-114x114.png')); ?>">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php echo esc_url(site_url('/apple-icon-120x120.png')); ?>">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo esc_url(site_url('/apple-icon-144x144.png')); ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo esc_url(site_url('/apple-icon-152x152.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(site_url('/apple-icon-180x180.png')); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url(site_url('/android-icon-192x192.png')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url(site_url('/favicon-32x32.png')); ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo esc_url(site_url('/favicon-96x96.png')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url(site_url('/favicon-16x16.png')); ?>">
    <link rel="manifest" href="<?php echo esc_url(site_url('/manifest.json')); ?>">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo esc_url(site_url('/ms-icon-144x144.png')); ?>">
    <meta name="theme-color" content="#ffffff">

    <!-- Etiquetas Open Graph para Facebook y otras plataformas -->
    <meta property="og:title" content="<?php echo get_the_title(); ?>" />
    <meta property="og:description" content="Social Media para artistas" />
    <meta property="og:image" content="<?php echo esc_url(wp_get_attachment_image_url(get_option('site_icon'), 'full')); ?>" />
    <meta property="og:url" content="<?php echo esc_url(home_url('/')); ?>" />
    <meta property="og:type" content="website" />

    <!-- Etiquetas de Twitter Cards -->
    <meta property="og:title" content="<?php echo get_the_title(); ?>" />
    <meta name="twitter:title" content="Social Media para artistas">
    <meta name="twitter:description" content="Descripción de tu página que aparecerá al compartir.">
    <meta name="twitter:image" content="<?php echo esc_url(wp_get_attachment_image_url(get_option('site_icon'), 'full')); ?>">
    <meta name="twitter:site" content="@wandorius" />

<?php
}
add_action('wp_head', 'headGeneric');

function preload_fonts()
{
    if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === true)) {
        return;
    }
    $theme_url = get_template_directory_uri();
    echo '<link rel="preload" href="' . esc_url($theme_url . '/../2upra3v/assets/Fonts/SourceSans3-Regular.woff2') . '" as="font" type="font/woff2" crossorigin>';
    echo '<link rel="preload" href="' . esc_url($theme_url . '/../2upra3v/assets/Fonts/SourceSans3-Bold.woff2') . '" as="font" type="font/woff2" crossorigin>';
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

// =============================================================================
// FUNCIONES DE LOGGING
// =============================================================================
// Las funciones de logging han sido movidas a: inc/Logging/Logger.php
// Las funciones originales (escribirLog, streamLog, seoLog, etc.) siguen 
// disponibles como wrappers para mantener compatibilidad con código existente.
// Ver la clase Logger para la nueva implementación orientada a objetos.
// =============================================================================

//wave


function scriptsOrdenados()
{
    $global_version = '0.2.386';
    $dev_mode = defined('LOCAL') && LOCAL;
    //$error_log = [];

    $scripts_only_for_logged_in_users = [
        'galleV2',
        'likes',
        'descargas',
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
        'stripeCompra',
        'task',
        'notas',
    ];

    $script_handles = [
        'gloria'            => '1.0.1',
        'ajaxPage'          => '5.0.11',
        'autorows'          => '1.0.1',
        'busqueda'          => '1.0.1',
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
        'masonary'          => '1.0.1',
        'taskEnter'         => '1.0.1',
        'taskmove'          => '1.0.1',
        'taskSesiones'      => '1.0.1',
        'task'              => '1.0.1',
        'icons'             => '1.0.1',
        'notas'             => '1.0.1',
        'filtros'           => '1.0.1',
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

    // Configuracion global de URLs para JavaScript
    $upload_dir = wp_upload_dir();
    wp_localize_script('ajaxPage', 'siteConfig', [
        'siteUrl' => site_url(),
        'homeUrl' => home_url(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => rest_url(),
        'wsUrl' => 'wss://' . $_SERVER['HTTP_HOST'] . '/ws',
        'uploadsUrl' => $upload_dir['baseurl'],
        'uploadsPath' => trailingslashit(site_url()) . 'wp-content/uploads',
        'themeUrl' => get_template_directory_uri(),
        'defaultAvatar' => get_template_directory_uri() . '/assets/images/perfildefault.jpg'
    ]);

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
    $theme_url = get_template_directory_uri();
    $meta_tags[] = sprintf('<link rel="icon" href="%s">', esc_url($theme_url . '/../2upra3v/assets/icons/favicon-96x96.png'));
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
