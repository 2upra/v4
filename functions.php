<?php
require_once ABSPATH . 'wp-content/stripe/init.php';

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); 

function guardar_log($log) {
    $log_option_name = 'wanlog_logs';
    $logs = get_option($log_option_name, []);
    $timestamped_log = date('Y-m-d H:i:s') . ' - ' . $log;

    // Guardar en la opción de WordPress
    array_unshift($logs, $timestamped_log); // Añade al principio del array
    $logs = array_slice($logs, 0, 100); // Mantén solo los 100 más recientes
    update_option($log_option_name, $logs);

    // Guardar en el archivo
    $log_file = '/var/www/html/wp-content/themes/logsw.txt';
    file_put_contents($log_file, $timestamped_log . PHP_EOL, FILE_APPEND);

    // Verificar y truncar el archivo si supera las 400 líneas
    $line_count = count(file($log_file));
    if ($line_count > 400) {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_lines = array_slice($lines, -400); // Mantener solo las últimas 400 líneas
        file_put_contents($log_file, implode(PHP_EOL, $new_lines) . PHP_EOL);
    }
}

function clean_log_files() {
    $log_files = array(
        ABSPATH . 'wp-content/themes/wanlog.txt',
        ABSPATH . 'wp-content/themes/wanlogAjax.txt',
        ABSPATH . 'wp-content/uploads/access_logs.txt',
        ABSPATH . 'wp-content/themes/logsw.txt',
        ABSPATH . 'wp-content/debug.log'
    );
    
    foreach ($log_files as $file) {
        if (file_exists($file)) {
            $file_size = filesize($file) / (1024 * 1024); // Tamaño en MB
            if ($file_size > 1) {
                $lines = file($file);
                $lines = array_slice($lines, -400); // Mantener las últimas 300 líneas
                file_put_contents($file, implode('', $lines));
                error_log("Archivo limpiado: $file");
            } else {
                error_log("El archivo $file es menor que 1 MB. No se requiere acción.");
            }
        } else {
            error_log("El archivo $file no existe.");
        }
    }
}

// Programar la ejecución de la función
if (!wp_next_scheduled('clean_log_files_hook')) {
    wp_schedule_event(time(), 'hourly', 'clean_log_files_hook');
}
add_action('clean_log_files_hook', 'clean_log_files');


function incluir_archivos_recursivamente($directorio) {
    $ruta_completa = get_template_directory() . "/$directorio";

    $archivos = glob($ruta_completa . "*.php");
    foreach ($archivos as $archivo) {
        include_once $archivo;
    }

    $subdirectorios = glob($ruta_completa . "*/", GLOB_ONLYDIR);
    foreach ($subdirectorios as $subdirectorio) {
        $ruta_relativa = str_replace(get_template_directory() . '/', '', $subdirectorio);
        incluir_archivos_recursivamente($ruta_relativa);
    }
}

$directorios = [
    'wandorius/',
];

foreach ($directorios as $directorio) {
    incluir_archivos_recursivamente($directorio);
}


//SCRIPTS - NO TODOS INTENTE ORGANIZAR LOS QUE PUDE - ALGUNOS CON ESTA ESTRUCTURA NO FUNCIONABAN Y LOS SEPARE
//
function enqueue_and_localize_scripts($handle, $script_path, $dep, $ver, $in_footer, $object_name, $nonce_action)
{
    $src = (strpos($script_path, 'http://') === 0 || strpos($script_path, 'https://') === 0) ? $script_path : get_template_directory_uri() . $script_path;
    wp_enqueue_script($handle, $src, $dep, $ver, $in_footer);
    if (!empty($nonce_action)) {
        wp_localize_script($handle, $object_name, array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($nonce_action)
        )
        );
    }
}

function enqueue_borrar_comentario_script()
{
    enqueue_and_localize_scripts('borrar-comentario', '/js/borrar-comentario.js', ['jquery'], '1.0.23', true, 'ajax_var_borrar_comentario', 'borrar_comentario_nonce');
}

function enqueue_delete_post_script()
{
    enqueue_and_localize_scripts('delete-post', '/js/delete-post.js', ['jquery'], '1.0.22', true, 'ajax_var_delete', 'delete_post_nonce');
}

function enqueue_editar_comentario_script()
{
    enqueue_and_localize_scripts('editar-comentario', '/js/editar-comentario.js', ['jquery'], '1.0.8', true, 'editar_comentario', 'editar_comentario_nonce');
}


function enqueue_wavesurfer_script()
{
    enqueue_and_localize_scripts('wavesurfer', 'https://unpkg.com/wavesurfer.js', [], '7.7.3', true, '', '');
}







function enqueue_stripe_comprar_js_script()
{
    enqueue_and_localize_scripts('stripe-comprar-js', '/js/stripe.js', [], '1.0.20', true, '', '');
}

add_action('wp_enqueue_scripts', 'enqueue_borrar_comentario_script');
add_action('wp_enqueue_scripts', 'enqueue_delete_post_script');
add_action('wp_enqueue_scripts', 'enqueue_likes_script');
add_action('wp_enqueue_scripts', 'enqueue_editar_comentario_script');
add_action('wp_enqueue_scripts', 'enqueue_wavesurfer_script');
add_action('wp_enqueue_scripts', 'enqueue_stripe_comprar_js_script');

function agregar_google_fonts()
{
    // Preload para cargar la fuente lo antes posible
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
    echo '<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;700&display=swap"></noscript>';
}
add_action('wp_head', 'agregar_google_fonts', 1);








// 
function agregar_texto_flotante()
{

    if (is_page('galle')) {
        return;
    }

    ?>
    <style>
        #texto-flotante {
            position: fixed;
            bottom: 10px;
            right: 10px;
            line-height: 1px;
            text-align: end;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 10px;
            z-index: 999;
        }

        #texto-flotante a {
            text-decoration: none;
        }
    </style>
    <div id="texto-flotante">
        <p><a href="#" onclick="window.location='https://chat.whatsapp.com/IGHrIfvifHS9Fwz4ha6Uis';">En fase de desarrollo.
        </p></a>
    </div>
    <?php
}
add_action('wp_footer', 'agregar_texto_flotante');


function add_loading_bar()
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

add_action('wp_head', 'add_loading_bar');


//PERMITIR CIERTOS USUARIOS
/*
function verificar_acceso_usuarios() {
    $usuarios_permitidos = array('1ndoryu', 'temporal08', 'Geras7v7', 'MajesticScarab8');

    if (is_user_logged_in()) {
        $usuario_actual = wp_get_current_user();
        if (!in_array($usuario_actual->user_login, $usuarios_permitidos)) {
            wp_logout();
            wp_redirect(home_url('/seviene'));
            exit;
        }
    }
}
add_action('template_redirect', 'verificar_acceso_usuarios');
*/



/* EL SCRIPT 

jQuery(document).ready(function($) {
    function actualizarLogs() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'obtener_logs'
            },
            success: function(response) {
                if (response.success) {
                    var logsHtml = '';
                    response.data.forEach(function(log) {
                        logsHtml += '<p>' + log + '</p>';
                    });
                    $('#admin-logs').html(logsHtml);
                } else {
                    console.error('Error obteniendo los logs:', response.data);
                }
            },
            error: function(error) {
                console.error('Error en la petición AJAX:', error);
            }
        });
    }

    // Inicialmente carga los logs
    actualizarLogs();

    // Actualiza los logs cada 5 segundos
    setInterval(actualizarLogs, 5000);
});

*/


//SCRIPTS QUE FUNCIONAN BIEN SEPARADOS


// 
//SCRIPTS ESENCIAL AJAX - INTERCAMBIO DE PAGINA Y PESTAÑA
function ajax()
{
    wp_enqueue_script('mi-script-ajax', get_template_directory_uri() . '/js/ajax-page.js', array('jquery'), '2.0.47', true);
    wp_localize_script('mi-script-ajax', 'miAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'ajax');
//CALCULAR ALTURA CORRECTA CON SCRIPT
function agregar_scripts_personalizados1()
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
add_action('wp_enqueue_scripts', 'agregar_scripts_personalizados1');


function cargar_jquery()
{
    if (!is_admin()) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'cargar_jquery');



/*
function redirect_music_user() {
    if (is_page('music')) {
        if (isset($_GET['fb-edit']) && $_GET['fb-edit'] == '1') {
            return;
        }

        $current_user = wp_get_current_user();
        if (!is_admin() && $current_user->exists()) {
            $music_user = get_query_var('music_user', false);
            // Si no hay un usuario específico en la URL, redirige al usuario logueado
            if (!$music_user) {
                wp_redirect(home_url('/music/' . $current_user->user_login));
                exit;
            }
        }
    }
}
add_action('template_redirect', 'redirect_music_user');
*/
/*
function redirigir_singles_posts() {
    if (is_single() && !current_user_can('administrator')) {
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit;
    }
}
add_action('template_redirect', 'redirigir_singles_posts');
*/
/*
add_action('template_redirect', 'redirigir_si_no_logeado');
function redirigir_si_no_logeado() {
    $excepciones = array(
        '/registro',
        '/iniciar',
        '/samples',
        '/#colabs',
        '/#sample',
        '#colabs',
        '#sample',

    );

    if (!is_user_logged_in() && !is_front_page()) {
        $url_actual = wp_parse_url(home_url( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH);
        $excepcion_encontrada = false;

        foreach ($excepciones as $excepcion) {
            if (trailingslashit($url_actual) === trailingslashit($excepcion)) {
                $excepcion_encontrada = true;
                break;
            }
        }

        if (!$excepcion_encontrada) {
            wp_redirect(home_url());
            exit();
        }
    }
}

*/
















































// Impedir el acceso al área de administración y la visualización de la barra de herramientas para todos los usuarios excepto los administradores
function restrict_admin_area_and_toolbar()
{
    if (!current_user_can('administrator') && !wp_doing_ajax()) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'restrict_admin_area_and_toolbar');

// Ocultar la barra de herramientas para todos los usuarios excepto los administradores
function hide_admin_bar_for_non_admins()
{
    if (!current_user_can('administrator')) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action('after_setup_theme', 'hide_admin_bar_for_non_admins');



wp_localize_script( 'my-ajax-script', 'ajax_params', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));




function replace_deprecated_function() {
    remove_action('wp_footer', 'the_block_template_skip_link');
    add_action('wp_footer', 'wp_enqueue_block_template_skip_link');
}
add_action('after_setup_theme', 'replace_deprecated_function');

function redirigir_busqueda_invalida() {
    if (isset($_SERVER['REQUEST_URI'])) {
        $request_uri = $_SERVER['REQUEST_URI'];

        // Verifica si la URL contiene '?s=' (búsqueda)
        if (strpos($request_uri, '?s=') !== false) {
            // Redirige a la página de inicio
            wp_redirect(home_url());
            exit;
        }
    }
}
add_action('template_redirect', 'redirigir_busqueda_invalida');


?>