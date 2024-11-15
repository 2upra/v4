<?

function analyze_existing_files()
{
    // Directorio de carga de WordPress
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];

    // Directorio para archivos duplicados
    $duplicate_dir = $base_dir . '/duplicates';
    if (!file_exists($duplicate_dir)) {
        mkdir($duplicate_dir, 0755, true);
    }

    // Obtener todos los archivos
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    global $wpdb;
    $table_name = $wpdb->prefix . 'file_hashes';
    $pending_table = $wpdb->prefix . 'pending_moves';

    // Crear tabla de movimientos pendientes si no existe
    $wpdb->query("CREATE TABLE IF NOT EXISTS $pending_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_path VARCHAR(255) NOT NULL,
        new_path VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending'
    )");

    $batch_size = 50; // Número de archivos a procesar por lote
    $processed = 0;

    foreach ($files as $file) {
        // Saltar directorios
        if ($file->isDir()) {
            continue;
        }

        $file_path = $file->getPathname();
        $relative_path = str_replace($base_dir . '/', '', $file_path);

        // Verificar si ya tiene hash
        $existing_hash = $wpdb->get_var($wpdb->prepare(
            "SELECT file_hash FROM $table_name WHERE file_url LIKE %s",
            '%' . $wpdb->esc_like($relative_path)
        ));

        if (!$existing_hash) {
            // Generar hash
            $file_hash = md5_file($file_path);

            // Verificar si es un duplicado
            $duplicate = $wpdb->get_var($wpdb->prepare(
                "SELECT file_url FROM $table_name WHERE file_hash = %s",
                $file_hash
            ));

            if ($duplicate) {
                // Agregar a la lista de pendientes
                $new_path = $duplicate_dir . '/' . basename($file_path);
                $wpdb->insert(
                    $pending_table,
                    array(
                        'file_path' => $file_path,
                        'new_path' => $new_path
                    ),
                    array('%s', '%s')
                );
                log_duplicados("Archivo duplicado pendiente de mover: $file_path");
            } else {
                // Guardar el nuevo hash
                $wpdb->insert(
                    $table_name,
                    array(
                        'file_hash' => $file_hash,
                        'file_url' => $relative_path,
                        'upload_date' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
                log_duplicados("Nuevo hash generado para: $file_path");
            }
        }

        $processed++;
        if ($processed >= $batch_size) {
            log_duplicados("Procesados $batch_size archivos. Pausa para evitar sobrecarga.");
            sleep(1); // Pausa de 1 segundo
            $processed = 0;
        }
    }
    log_duplicados("Análisis de archivos existentes completado");
}

function log_duplicados($mensaje)
{
    $log_file = WP_CONTENT_DIR . '/file_analysis_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $mensaje\n", FILE_APPEND);
}

function mostrar_archivos_pendientes()
{
    global $wpdb;
    $pending_table = $wpdb->prefix . 'pending_moves';

    if (isset($_POST['process_pending'])) {
        $file_id = intval($_POST['file_id']);
        $file = $wpdb->get_row($wpdb->prepare("SELECT * FROM $pending_table WHERE id = %d", $file_id));

        if ($file) {
            rename($file->file_path, $file->new_path);
            $wpdb->update($pending_table, array('status' => 'processed'), array('id' => $file_id));
            echo "Archivo movido con éxito.";
        }
    }

    $pending_files = $wpdb->get_results("SELECT * FROM $pending_table WHERE status = 'pending'");

    echo "<h2>Archivos pendientes de mover</h2>";
    foreach ($pending_files as $file) {
        echo "<form method='post'>";
        echo "<p>{$file->file_path} -> {$file->new_path}</p>";
        echo "<input type='hidden' name='file_id' value='{$file->id}'>";
        echo "<input type='submit' name='process_pending' value='Procesar'>";
        echo "</form>";
    }
}

function check_missing_images()
{
    $posts = get_posts(array('post_type' => 'any', 'posts_per_page' => -1));
    $missing_images = [];

    foreach ($posts as $post) {
        $post_content = $post->post_content;
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post_content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $image_url) {
                $image_path = str_replace(site_url('/'), ABSPATH, $image_url);
                if (!file_exists($image_path)) {
                    $missing_images[] = array(
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'image_url' => $image_url
                    );
                }
            }
        }
    }

    return $missing_images;
}

function analizar_y_recuperar_archivos_perdidos()
{
    global $wpdb;

    // Activar la depuración de WordPress
    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }
    if (!defined('WP_DEBUG_LOG')) {
        define('WP_DEBUG_LOG', true);
    }
    if (!defined('WP_DEBUG_DISPLAY')) {
        define('WP_DEBUG_DISPLAY', true);
    }

    error_log("Iniciando análisis de archivos perdidos...");

    // Obtener todos los archivos adjuntos de la base de datos
    $attachments = $wpdb->get_results("SELECT ID, guid FROM {$wpdb->posts} WHERE post_type = 'attachment'");

    $archivos_faltantes = 0;
    $archivos_recuperables = 0;
    $ejemplos_recuperacion = [];

    foreach ($attachments as $attachment) {
        $file_path = get_attached_file($attachment->ID);

        if (!file_exists($file_path)) {
            $archivos_faltantes++;

            // Verificar si el archivo existe en wp-content/uploads
            $upload_dir = wp_upload_dir();
            $relative_path = str_replace($upload_dir['basedir'] . '/', '', $file_path);
            $alternative_path = $upload_dir['basedir'] . '/' . $relative_path;

            if (file_exists($alternative_path)) {
                $archivos_recuperables++;
                if (count($ejemplos_recuperacion) < 10) {
                    $ejemplos_recuperacion[] = [
                        'id' => $attachment->ID,
                        'ruta_original' => $file_path,
                        'ruta_recuperacion' => $alternative_path
                    ];
                }
            }
        }
    }

    error_log("Análisis completado.");
    error_log("Archivos faltantes: " . $archivos_faltantes);
    error_log("Archivos recuperables: " . $archivos_recuperables);

    if (!empty($ejemplos_recuperacion)) {
        error_log("Ejemplos de recuperación (máximo 10):");
        foreach ($ejemplos_recuperacion as $ejemplo) {
            error_log("ID: {$ejemplo['id']} - Original: {$ejemplo['ruta_original']} - Recuperación: {$ejemplo['ruta_recuperacion']}");
        }
    }

    return [
        'faltantes' => $archivos_faltantes,
        'recuperables' => $archivos_recuperables,
        'ejemplos' => $ejemplos_recuperacion
    ];
}