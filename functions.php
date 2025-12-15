<?php

/**
 * Funciones principales del tema.
 * 
 * Este archivo ha sido refactorizado para seguir principios SOLID.
 * Las responsabilidades se han dividido en módulos dentro de /inc/.
 *
 * @package Kamples
 * @since 1.0.0
 * @see REFACTORIZACION.md para el historial de cambios
 */

// =============================================================================
// CARGA DE MÓDULOS DEL TEMA
// =============================================================================

// Suprimir advertencias de deprecación (PHP 8.4 compatibility)
error_reporting(E_ALL & ~E_DEPRECATED);

// Configuración y constantes (debe cargarse primero)
require_once get_template_directory() . '/inc/Config/constants.php';

// Sistema de logging
require_once get_template_directory() . '/inc/Logging/Logger.php';

// Gestión de scripts y estilos
require_once get_template_directory() . '/inc/Setup/scripts.php';

// =============================================================================
// AUTOLOADER PARA /src/ (código refactorizado con namespaces)
// =============================================================================

// Cargar autoloader PSR-4 para namespace Kamples
require_once get_template_directory() . '/src/autoload.php';

// =============================================================================
// INICIALIZACIÓN DE SERVICIOS REFACTORIZADOS
// =============================================================================

// Registrar Custom Post Types y estados
\Kamples\Core\PostTypes::inicializar();

// Servicio de gestión de slugs
\Kamples\Services\PostSlugService::inicializar();

// Inicializar Controladores (incluye PostController, StreamController, etc.)
require_once get_template_directory() . '/src/Controllers/init.php';



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



// DEBUG TEMPORAL: Verificación de usuario
// REPARACIÓN DE ROLES: Ejecutar con ?fix_roles=1
function reparar_roles_admin()
{
    if (isset($_GET['fix_roles'])) {
        require_once(ABSPATH . 'wp-admin/includes/schema.php');

        // 1. Intentar restaurar roles por defecto si están muy dañados
        if (!get_role('administrator')) {
            populate_roles();
            echo "Roles por defecto repoblados.<br>";
        }

        // 2. Asegurar capabilities críticas al rol 'administrator'
        $role = get_role('administrator');
        if ($role) {
            $caps = array(
                'manage_options',
                'edit_dashboard',
                'edit_theme_options',
                'activate_plugins',
                'install_plugins',
                'update_plugins',
                'delete_plugins',
                'update_core',
                'list_users',
                'remove_users',
                'add_users',
                'promote_users',
                'edit_users',
                'create_users',
                'delete_users',
                'unfiltered_html'
            );

            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
            echo "Capacidades críticas añadidas al rol Administrator.<br>";
        } else {
            echo "Error crítico: No se encuentra el rol Administrator.<br>";
        }

        // 3. Forzar capabilities al usuario actual (o ID 1)
        $user_id = get_current_user_id();
        if (!$user_id) $user_id = 1; // Fallback si no hay sesión iniciada pero accedemos

        $user = new WP_User($user_id);
        $user->add_role('administrator');
        $user->add_cap('administrator');
        $user->add_cap('manage_options');

        echo "<strong>Reparación finalizada para usuario ID $user_id.</strong><br>";
        echo "Intenta acceder ahora al wp-admin.<br>";

        // Verificar estado final
        echo "<pre>";
        print_r($user->allcaps);
        echo "</pre>";

        die();
    }
}
add_action('init', 'reparar_roles_admin');


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

// =============================================================================
// SCRIPTS Y ESTILOS
// =============================================================================
// La gestión de scripts ha sido movida a: inc/Setup/scripts.php
// Ver la clase ScriptsManager para la implementación.
// =============================================================================

// =============================================================================
// CARGA DE ARCHIVOS DEL TEMA
// =============================================================================

/**
 * Incluir recursivamente todos los archivos PHP de un directorio.
 * 
 * Esta función es necesaria para cargar /app/ mientras se refactoriza.
 * Una vez que todo esté en /src/ con namespaces, se usará autoloading.
 *
 * @param string $directorio Ruta relativa del directorio a cargar.
 * @return void
 */
function incluirArchivos($directorio)
{
    $rutaCompleta = get_template_directory() . "/{$directorio}";

    // Incluir archivos PHP del directorio actual
    $archivos = glob($rutaCompleta . "*.php");
    foreach ($archivos as $archivo) {
        include_once $archivo;
    }

    // Procesar subdirectorios recursivamente
    $subdirectorios = glob($rutaCompleta . "*/", GLOB_ONLYDIR);
    foreach ($subdirectorios as $subdirectorio) {
        $rutaRelativa = str_replace(get_template_directory() . '/', '', $subdirectorio);
        incluirArchivos($rutaRelativa);
    }
}

// Cargar archivos del tema
$directorios = [
    'src/',
    'app/',              // Código legacy pendiente de refactorizar
];

foreach ($directorios as $directorio) {
    incluirArchivos($directorio);
}

// =============================================================================
// FUNCIONES DE UI
// =============================================================================

/**
 * Renderizar la barra de carga superior.
 *
 * @return void
 */
function loadingBar()
{
    echo '<style>
        #loadingBar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background-color: white;
            transition: width 0.4s ease;
            z-index: 999999999999999;
        }
    </style>';

    echo '<div id="loadingBar"></div>';
}
add_action('wp_head', 'loadingBar');

/**
 * Personalizar el icono del sitio.
 *
 * @param array $metaTags Tags de meta existentes.
 * @return array Tags modificados.
 */
function custom_site_icon($metaTags)
{
    $themeUrl = get_template_directory_uri();
    $metaTags[] = sprintf(
        '<link rel="icon" href="%s">',
        esc_url($themeUrl . '/../2upra3v/assets/icons/favicon-96x96.png')
    );
    return $metaTags;
}
add_filter('site_icon_meta_tags', 'custom_site_icon');

// =============================================================================
// TAREAS PROGRAMADAS (CRON)
// =============================================================================

/**
 * Limpiar archivos de log grandes.
 * 
 * Mantiene solo las últimas 2000 líneas de archivos mayores a 1MB.
 *
 * @return void
 */
function limpiarLogs()
{
    $logFiles = [
        ABSPATH . 'wp-content/themes/wanlog.txt',
        ABSPATH . 'wp-content/themes/wanlogAjax.txt',
        ABSPATH . 'wp-content/uploads/access_logs.txt',
        ABSPATH . 'wp-content/themes/logsw.txt',
        ABSPATH . 'wp-content/debug.log'
    ];

    /* 
     * Procesar solo UN archivo por ejecución para evitar timeout.
     * El índice se guarda en transient y rota entre archivos.
     */
    $indice = (int) get_transient('limpiar_logs_indice');
    $indice = $indice % count($logFiles);
    set_transient('limpiar_logs_indice', $indice + 1, HOUR_IN_SECONDS);

    $file = $logFiles[$indice];

    if (!file_exists($file)) {
        return;
    }

    $fileSizeMb = filesize($file) / (1024 * 1024);

    /* Solo limpiar si supera 1MB */
    if ($fileSizeMb <= 1) {
        return;
    }

    try {
        /* 
         * Para archivos muy grandes (>5MB), simplemente truncar a las últimas líneas
         * usando tail del sistema si está disponible, o truncar directamente.
         */
        if ($fileSizeMb > 5) {
            /* Truncar agresivamente: mantener solo últimos 500KB */
            $fp = fopen($file, 'r+');
            if ($fp) {
                $keepBytes = 500 * 1024;
                $fileSize = filesize($file);
                if ($fileSize > $keepBytes) {
                    fseek($fp, -$keepBytes, SEEK_END);
                    /* Avanzar hasta el próximo salto de línea para no cortar a mitad */
                    fgets($fp);
                    $content = fread($fp, $keepBytes);
                    ftruncate($fp, 0);
                    fseek($fp, 0);
                    fwrite($fp, $content);
                }
                fclose($fp);
            }
            return;
        }

        /* Para archivos entre 1-5MB, usar el método original pero optimizado */
        $tempFile = $file . '.temp';
        $linesToKeep = 1000;

        /* Leer solo las últimas N líneas usando file() con límite de memoria */
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        $totalLines = count($lines);
        if ($totalLines <= $linesToKeep) {
            return;
        }

        /* Tomar solo las últimas líneas */
        $lastLines = array_slice($lines, -$linesToKeep);

        /* Escribir al archivo temporal */
        file_put_contents($tempFile, implode(PHP_EOL, $lastLines) . PHP_EOL);

        /* Reemplazar el original */
        if (file_exists($tempFile)) {
            unlink($file);
            rename($tempFile, $file);
        }
    } catch (Exception $e) {
        error_log("Error limpiando log {$file}: " . $e->getMessage());

        if (isset($tempFile) && file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }
}

// Programar limpieza de logs cada hora
if (!wp_next_scheduled('clean_log_files_hook')) {
    wp_schedule_event(time(), 'hourly', 'clean_log_files_hook');
}
add_action('clean_log_files_hook', 'limpiarLogs');
