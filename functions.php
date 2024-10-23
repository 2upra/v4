<?

require_once ABSPATH . 'wp-content/stripe/init.php';
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function escribirLog($mensaje, $archivo, $max_lineas = 2000) {
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

function chatLog($log) {
    escribirLog($log, '/var/www/wordpress/wp-content/themes/chat.log');
}

function stripeError($log) {
    escribirLog($log, '/var/www/wordpress/wp-content/themes/stripeError.log');
}

function autLog($log) {
    escribirLog($log, '/var/www/wordpress/wp-content/themes/automaticPost.log');
}

function guardarLog($log) {
    escribirLog($log, '/var/www/wordpress/wp-content/themes/logsw.txt');
}

function logAlgoritmo($log) {
    escribirLog($log, '/var/www/wordpress/wp-content/themes/logAlgoritmo.log', 20); // Máximo de 100 líneas
}

function ajaxPostLog($log) {
    escribirLog($log, '/var/www/wordpress/wp-content/themes/wanlogAjax.txt');
}

function iaLog($log) {
    escribirLog($log, '/var/www/wordpress/wp-content/themes/iaLog.log');
}

function postLog($log) {
    escribirLog($log, '/var/www/wordpress/wp-content/themes/wanlog.txt');
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

// wavejs.js?ver=2.0.12.375203239:84  Spinner no está definido. Asegúrate de que la librería esté cargada correctamente. intente agregarlo manualmente en spin.js pero parece que no esta funcionando 
    $script_handles = [
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
        'galleV2' => '2.0.1',
        'cambiarVistas' => '1.0.1',
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
    wp_enqueue_script('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.7.6', true);
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
