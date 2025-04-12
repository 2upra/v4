<?php
// Maneja la lógica y el hook para la tarea cron de limpieza de logs.

// Refactor: Función movida desde app/Utils/Logger.php
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

// Refactor: Hook movido desde app/Utils/Logger.php
add_action('clean_log_files_hook', 'limpiarLogs');
