<?php

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

                    $file_obj = new \SplFileObject($file, 'r'); // Use global namespace for SplFileObject

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
                } catch (\Exception $e) { // Use global namespace for Exception
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

// Programar la ejecucion de la funcion
if (!wp_next_scheduled('clean_log_files_hook')) {
    wp_schedule_event(time(), 'hourly', 'clean_log_files_hook');
}
// Note: The function is defined globally in this file, so we call it without namespace.
// The include mechanism in functions.php makes it available globally.
add_action('clean_log_files_hook', 'limpiarLogs');

/**
 * Registra un resumen de los puntos calculados para el feed personalizado de un usuario.
 * Llama a logAlgoritmo para registrar los detalles.
 *
 * @param int   $userId        ID del usuario.
 * @param array $resumenPuntos Array asociativo [post_id => puntos].
 */
function logResumenDePuntos($userId, $resumenPuntos)
{
    // Llama a logAlgoritmo, que ya está definida en este archivo (o incluida globalmente)
    logAlgoritmo("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($resumenPuntos));
    $resumen_formateado = [];
    foreach ($resumenPuntos as $post_id => $puntos) {
        $resumen_formateado[] = "$post_id:$puntos";
    }
    logAlgoritmo("Resumen de puntos - " . implode(', ', $resumen_formateado));
}
