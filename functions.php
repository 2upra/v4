<?

require_once ABSPATH . 'wp-content/stripe/init.php';
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Paso 1: Definir las variables de configuración para cada log
define('LOG_AUDIO_ENABLED', false); // Cambia a true para habilitar logAudio
define('CHAT_LOG_ENABLED', false);    // Cambia a true para habilitar chatLog
define('STRIPE_ERROR_ENABLED', true); // Cambia a true para habilitar stripeError
define('AUT_LOG_ENABLED', false);      // Cambia a true para habilitar autLog
define('GUARDAR_LOG_ENABLED', false);  // Cambia a true para habilitar guardarLog
define('LOG_ALGORITMO_ENABLED', false); // Cambia a true para habilitar logAlgoritmo
define('AJAX_POST_LOG_ENABLED', false); // Cambia a true para habilitar ajaxPostLog
define('IA_LOG_ENABLED', false);        // Cambia a true para habilitar iaLog
define('POST_LOG_ENABLED', false);      // Cambia a true para habilitar postLog

function escribirLog($mensaje, $archivo, $max_lineas = 200) {
    if (is_object($mensaje) || is_array($mensaje)) {
        $mensaje = json_encode($mensaje);
    }

    $timestamped_log = date('Y-m-d H:i:s') . ' - ' . $mensaje;
    file_put_contents($archivo, $timestamped_log . PHP_EOL, FILE_APPEND);

    // Limitar la cantidad de líneas en el archivo
    $line_count = count(file($archivo));
    if ($line_count > $max_lineas) {
        $lines = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_lines = array_slice($lines, -$max_lineas);
        file_put_contents($archivo, implode(PHP_EOL, $new_lines) . PHP_EOL);
    }
}

function logAudio($log) {
    if (LOG_AUDIO_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/logAudio.log', 100000);
    }
}

function chatLog($log) {
    if (CHAT_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/chat.log');
    }
}

function stripeError($log) {
    if (STRIPE_ERROR_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/stripeError.log');
    }
}

function autLog($log) {
    if (AUT_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/automaticPost.log');
    }
}

function guardarLog($log) {
    if (GUARDAR_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/logsw.txt');
    }
}

function logAlgoritmo($log) {
    if (LOG_ALGORITMO_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/logAlgoritmo.log', 20);
    }
}

function ajaxPostLog($log) {
    if (AJAX_POST_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/wanlogAjax.txt');
    }
}

function iaLog($log) {
    if (IA_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/iaLog.log');
    }
}

function postLog($log) {
    if (POST_LOG_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/wanlog.txt');
    }
}

function scriptsOrdenados()
{
    $dev_mode = true;
    $error_log = [];

    // Definir scripts que no se deben cargar si el usuario no está logueado
    $scripts_only_for_logged_in_users = [
        'galleV2',
        'likes',
        'descargas',
        'fan',
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
        'wavejs' => ['2.0.12', ['jquery', 'wavesurfer']],
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

        $version = is_array($data) ? $data[0] : $data;
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

    // Localización de scripts solo si el usuario está logueado
    if (is_user_logged_in()) {
        $nonce = wp_create_nonce('wp_rest');
        wp_localize_script('galleV2', 'galleV2', array(
            'nonce' => $nonce,
            'apiUrl' => esc_url_raw(rest_url('galle/v2/guardarMensaje/')),
            'emisor' => get_current_user_id()
        ));
    }

    // Scripts externos
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns', ['chart-js'], null, true);
    wp_enqueue_script('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.7.8', true);
    wp_enqueue_script('jquery');
    wp_add_inline_script('genericAjax', 'const wpAdminUrl = "' . admin_url() . '";', 'before');

    wp_localize_script('notificaciones', 'datosNotificaciones', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'usuarioID' => get_current_user_id()
    ));

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

    wp_localize_script('wavejs', 'audioSettings', array(
        'nonce' => wp_create_nonce('wp_rest'),
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
            $file_size = filesize($file) / (1024 * 1024);
            if ($file_size > 1) {
                $lines = file($file);
                $lines = array_slice($lines, -2000);
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


function custom_site_icon($meta_tags) {
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

function desactivar_todos_soportes_bloques( $settings, $name ) {
    // Lista completa de soportes a desactivar
    $soportes_a_desactivar = array(
        'align',
        'alignWide',
        'anchor',
        'color',
        'customClassName',
        'html',
        'typography',
        'spacing',
        'border',
        'gradients',
        'responsive',
        'fontSize',
        'links',
        'inserter',
        'multiple',
        'reusable',
        'lock',
    );

    if ( isset( $settings['supports'] ) && is_array( $settings['supports'] ) ) {
        foreach ( $soportes_a_desactivar as $soporte ) {
            if ( isset( $settings['supports'][ $soporte ] ) ) {
                unset( $settings['supports'][ $soporte ] );
            }
        }
    }

    return $settings;
}
add_filter( 'block_type_metadata_settings', 'desactivar_todos_soportes_bloques', 10, 2 );

function desactivar_emojis() {
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'desactivar_emojis' );

function eliminar_scripts_y_estilos() {
    // Ejemplo: Eliminar el script de emoji (ya desactivado anteriormente)
    wp_dequeue_script( 'wp-emoji' );
    wp_dequeue_style( 'wp-emoji' );

    // Eliminar Gutenberg Block Library CSS
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' ); // WooCommerce

    // Eliminar Dashicons en el frontend si no se usan
    if ( ! is_admin() ) {
        wp_dequeue_style( 'dashicons' );
    }

    // Eliminar estilos de Gutenberg en el frontend
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' );

    // Eliminar estilos de la admin bar si no se usa
    if ( ! is_admin() ) {
        wp_dequeue_style( 'admin-bar' );
    }
}
add_action( 'wp_enqueue_scripts', 'eliminar_scripts_y_estilos', 100 );
add_action( 'admin_enqueue_scripts', 'eliminar_scripts_y_estilos', 100 );
add_filter( 'use_block_editor_for_post', '__return_false', 10 );

function desactivar_embeds() {
    // Deshabilitar scripts y estilos relacionados con embeds
    wp_dequeue_script( 'wp-embed' );
    
    // Eliminar acciones relacionadas con embeds
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    
    // Desactivar shortcodes oembed
    add_filter( 'embed_oembed_discover', '__return_false' );
    remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
}
add_action( 'init', 'desactivar_embeds', 9999 );

function eliminar_version_wp() {
    return '';
}
add_filter( 'the_generator', 'eliminar_version_wp' );

function desactivar_feeds() {
    wp_die( __('Las feeds RSS están deshabilitadas.') );
}

// Elimina las feeds principales
add_action('do_feed', 'desactivar_feeds', 1);
add_action('do_feed_rdf', 'desactivar_feeds', 1);
add_action('do_feed_rss', 'desactivar_feeds', 1);
add_action('do_feed_rss2', 'desactivar_feeds', 1);
add_action('do_feed_atom', 'desactivar_feeds', 1);

// Elimina la generación de enlaces en el encabezado
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);

// Limitar el número de revisiones
define('WP_POST_REVISIONS', 1);

// Deshabilitar autosaves
define('AUTOSAVE_INTERVAL', 300 ); // Intervalo en segundos (5 minutos)

// O para deshabilitar completamente:
function desactivar_autosave() {
    wp_deregister_script( 'autosave' );
}
add_action( 'wp_print_scripts', 'desactivar_autosave' );

function eliminar_comentarios_support() {
    // Quitar soporte de comentarios de tipos de post
    foreach ( get_post_types() as $post_type ) {
        if ( post_type_supports( $post_type, 'comments' ) ) {
            remove_post_type_support( $post_type, 'comments' );
            remove_post_type_support( $post_type, 'trackbacks' );
        }
    }

    // Cerrar comentarios en tiempo real
    add_filter( 'comments_open', '__return_false', 20, 2 );
    add_filter( 'pings_open', '__return_false', 20, 2 );

    // Eliminar menú de comentarios
    remove_menu_page( 'edit-comments.php' );

    // Eliminar widgets de comentarios
    add_action( 'widgets_init', function(){
        unregister_widget( 'WP_Widget_Recent_Comments' );
    });

    // Eliminar enlaces de comentarios en el pie de página
    add_filter( 'wp_footer', function(){
        // Implementa según tu tema
    });
}
add_action( 'init', 'eliminar_comentarios_support' );

function eliminar_widgets_innecesarios() {
    unregister_widget( 'WP_Widget_Calendar' );
    unregister_widget( 'WP_Widget_Meta' );
    unregister_widget( 'WP_Widget_Search' );
    // Añade otros widgets según necesites
}
add_action( 'widgets_init', 'eliminar_widgets_innecesarios', 11 );

function limpiar_footer_wordpress() {
    remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
    remove_action( 'wp_footer', 'wp_footer' );
}
add_action( 'init', 'limpiar_footer_wordpress' );

// Desactivar ajustes de discusión en la administración
function eliminar_ajustes_discusion() {
    remove_menu_page( 'options-discussion.php' );
}
add_action( 'admin_menu', 'eliminar_ajustes_discusion' );