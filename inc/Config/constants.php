<?php

/**
 * Constantes y configuración global del tema.
 * 
 * Este archivo centraliza todas las constantes utilizadas en el tema,
 * facilitando su mantenimiento y modificación.
 *
 * @package Theme_V4
 * @subpackage Config
 * @since 1.0.0
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

/*
 * ENTORNO DE DESARROLLO
 * 
 * Definir LOCAL si no está definido previamente (ej. en wp-config.php).
 * Se intenta detectar automáticamente basado en el dominio.
 */
if (!defined('LOCAL')) {
    $is_local = false;
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        if (strpos($host, '.local') !== false || strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            $is_local = true;
        }
    }
    define('LOCAL', $is_local);
}

/* 
 * NIVELES DE LOG
 * 
 * DEBUG (0)   - Información detallada para desarrollo
 * INFO (1)    - Información general del flujo de la aplicación
 * WARNING (2) - Situaciones inesperadas pero no críticas
 * ERROR (3)   - Errores que requieren atención
 * CRITICAL (4)- Fallos graves que pueden causar caída del sistema
 * OFF (5)     - Desactivar logs completamente
 */
define('LOG_LEVEL_DEBUG', 0);
define('LOG_LEVEL_INFO', 1);
define('LOG_LEVEL_WARNING', 2);
define('LOG_LEVEL_ERROR', 3);
define('LOG_LEVEL_CRITICAL', 4);
define('LOG_LEVEL_OFF', 5);

/**
 * Nivel de log global por defecto.
 * En producción debería ser LOG_LEVEL_WARNING o superior.
 * En desarrollo puede ser LOG_LEVEL_DEBUG.
 */
define('LOG_LEVEL_DEFAULT', LOG_LEVEL_INFO);

/**
 * Configuración de canales de log.
 * 
 * Cada canal tiene su propio nivel y archivo.
 * Esto permite control granular: activar logs de IA pero desactivar los de chat, etc.
 * 
 * Estructura: 'canal' => ['enabled' => bool, 'level' => int, 'file' => string, 'adminOnly' => bool]
 */
define('LOG_CHANNELS', [
    'stream' => [
        'enabled'   => false,
        'level'     => LOG_LEVEL_INFO,
        'adminOnly' => false,
    ],
    'seo' => [
        'enabled'   => true,
        'level'     => LOG_LEVEL_INFO,
        'adminOnly' => false,
    ],
    'audio' => [
        'enabled'   => false,
        'level'     => LOG_LEVEL_DEBUG,
        'adminOnly' => false,
    ],
    'rendimiento' => [
        'enabled'   => true,
        'level'     => LOG_LEVEL_INFO,
        'adminOnly' => false,
    ],
    'chat' => [
        'enabled'   => false,
        'level'     => LOG_LEVEL_DEBUG,
        'adminOnly' => true,
    ],
    'stripe' => [
        'enabled'   => true,
        'level'     => LOG_LEVEL_WARNING,
        'adminOnly' => true,
    ],
    'automatico' => [
        'enabled'   => true,
        'level'     => LOG_LEVEL_INFO,
        'adminOnly' => false,
    ],
    'guardar' => [
        'enabled'   => true,
        'level'     => LOG_LEVEL_INFO,
        'adminOnly' => false,
    ],
    'algoritmo' => [
        'enabled'   => false,
        'level'     => LOG_LEVEL_DEBUG,
        'adminOnly' => true,
    ],
    'ajaxPost' => [
        'enabled'   => false,
        'level'     => LOG_LEVEL_DEBUG,
        'adminOnly' => true,
    ],
    'ia' => [
        'enabled'   => true,
        'level'     => LOG_LEVEL_INFO,
        'adminOnly' => true,
    ],
    'post' => [
        'enabled'   => false,
        'level'     => LOG_LEVEL_DEBUG,
        'adminOnly' => true,
    ],
    'refactor' => [
        'enabled'   => true,
        'level'     => LOG_LEVEL_INFO,
        'adminOnly' => false,
    ],
]);

/**
 * Configuración de auto-limpieza de logs.
 */
define('LOG_AUTO_CLEANUP_ENABLED', true);
define('LOG_MAX_FILE_SIZE_MB', 5);
define('LOG_MAX_LINES_DEFAULT', 10000);
define('LOG_MAX_LINES_REDUCED', 100);
define('LOG_CLEANUP_PROBABILITY', 10000);

/**
 * Rutas de logs.
 * 
 * Centralizamos las rutas de los archivos de log para facilitar
 * su modificación y evitar hardcodear rutas en múltiples lugares.
 */
define('LOG_BASE_PATH', '/var/www/wordpress/wp-content/themes/');

define('LOG_FILES', [
    'stream'      => LOG_BASE_PATH . 'streamLog.log',
    'seo'         => LOG_BASE_PATH . 'seoLog.log',
    'audio'       => LOG_BASE_PATH . 'logAudio.log',
    'rendimiento' => LOG_BASE_PATH . 'rendimiento.log',
    'chat'        => LOG_BASE_PATH . 'chat.log',
    'stripe'      => LOG_BASE_PATH . 'stripeError.log',
    'automatico'  => LOG_BASE_PATH . 'automaticPost.log',
    'guardar'     => LOG_BASE_PATH . 'logsw.txt',
    'algoritmo'   => LOG_BASE_PATH . 'logAlgoritmo.log',
    'ajaxPost'    => LOG_BASE_PATH . 'wanlogAjax.txt',
    'ia'          => LOG_BASE_PATH . 'iaLog.log',
    'post'        => LOG_BASE_PATH . 'wanlog.txt',
    'refactor'    => LOG_BASE_PATH . 'refactor.log',
]);

/* 
 * CONSTANTES LEGACY (mantener compatibilidad)
 * 
 * Estas constantes se mantienen para compatibilidad con código antiguo.
 * Eventualmente deberían eliminarse y usar LOG_CHANNELS directamente.
 */
define('STRIPE_ERROR_ENABLED', LOG_CHANNELS['stripe']['enabled']);
define('SEO_LOG_ENABLED', LOG_CHANNELS['seo']['enabled']);
define('GUARDAR_LOG_ENABLED', LOG_CHANNELS['guardar']['enabled']);
define('LOG_AUDIO_ENABLED', LOG_CHANNELS['audio']['enabled']);
define('RENDIMIENTO_ENABLED', LOG_CHANNELS['rendimiento']['enabled']);
define('CHAT_LOG_ENABLED', LOG_CHANNELS['chat']['enabled']);
define('AUT_LOG_ENABLED', LOG_CHANNELS['automatico']['enabled']);
define('LOG_ALGORITMO_ENABLED', LOG_CHANNELS['algoritmo']['enabled']);
define('AJAX_POST_LOG_ENABLED', LOG_CHANNELS['ajaxPost']['enabled']);
define('IA_LOG_ENABLED', LOG_CHANNELS['ia']['enabled']);
define('POST_LOG_ENABLED', LOG_CHANNELS['post']['enabled']);
define('STREAM_LOG_ENABLED', LOG_CHANNELS['stream']['enabled']);

/* 
 * RUTAS LEGACY (mantener compatibilidad)
 */
define('LOG_STREAM', LOG_FILES['stream']);
define('LOG_SEO', LOG_FILES['seo']);
define('LOG_AUDIO', LOG_FILES['audio']);
define('LOG_RENDIMIENTO', LOG_FILES['rendimiento']);
define('LOG_CHAT', LOG_FILES['chat']);
define('LOG_STRIPE_ERROR', LOG_FILES['stripe']);
define('LOG_AUTOMATIC_POST', LOG_FILES['automatico']);
define('LOG_GUARDAR', LOG_FILES['guardar']);
define('LOG_ALGORITMO', LOG_FILES['algoritmo']);
define('LOG_AJAX_POST', LOG_FILES['ajaxPost']);
define('LOG_IA', LOG_FILES['ia']);
define('LOG_POST', LOG_FILES['post']);

/**
 * Configuración de Base de Datos.
 */
global $wpdb;
define('INTERES_TABLE', "{$wpdb->prefix}interes");

/**
 * Límites de la aplicación.
 */
define('POSTINLIMIT', 640);

/**
 * Versión global de scripts.
 * 
 * Se utiliza para cache busting en los assets.
 */
define('THEME_SCRIPTS_VERSION', '0.2.386');
