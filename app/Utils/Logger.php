<?php

namespace App\Utils;

//esto funciona cuando es local, tiene que sar el rror log de wp pro defecto spara todos los archivos 
function escribirLog($mensaje, $archivo = '', $maxlineas = 10000)
{

    // Intentar usar el error_log de WordPress por defecto
    if (is_object($mensaje) || is_array($mensaje)) {
        error_log(print_r($mensaje, true));
    } else {
        error_log($mensaje);
    }

    // Si se especifico un archivo y no estamos en local, intentamos escribir en el
    if (!empty($archivo) && (!defined('LOCAL') || !LOCAL)) {
        try {
            if (!is_writable(dirname($archivo))) {
                error_log("escribirLog: No se puede escribir en el directorio: " . dirname($archivo));
                return false;
            }

            if (is_object($mensaje) || is_array($mensaje)) {
                $mensaje = print_r($mensaje, true);
            }

            $log = date('Y-m-d H:i:s') . ' - ' . $mensaje;

            $fp = fopen($archivo, 'a');
            if ($fp) {
                if (flock($fp, LOCK_EX)) {
                    fwrite($fp, $log . PHP_EOL);

                    // Limitar el tamano del archivo, pero solo si se especifico un archivo
                    if (rand(1, 10000) === 1) {
                        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        if (count($lineas) > $maxlineas) {
                            $lineas = array_slice($lineas, -$maxlineas);
                            file_put_contents($archivo, implode(PHP_EOL, $lineas) . PHP_EOL);
                        }
                    }

                    flock($fp, LOCK_UN);
                } else {
                    error_log("escribirLog: No se pudo obtener el bloqueo del archivo: $archivo");
                }
                fclose($fp);
            } else {
                error_log("escribirLog: No se pudo abrir el archivo: $archivo");
            }
        } catch (\Exception $e) { // Use fully qualified name for Exception in namespace
            error_log("escribirLog: Excepcion capturada: " . $e->getMessage());
            return false;
        }
    }

    return true;
}
// sudo touch /var/www/wordpress/wp-content/themes/streamLog.log && sudo chown www-data:www-data /var/www/wordpress/wp-content/themes/rendimiento.log && sudo chmod 664 /var/www/wordpress/wp-content/themes/rendimiento.log
// tail -f /var/www/wordpress/wp-content/themes/rendimiento.log
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

function rendimientoLog($log)
{
    if (RENDIMIENTO_ENABLED) {
        escribirLog($log, '/var/www/wordpress/wp-content/themes/rendimiento.log');
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
