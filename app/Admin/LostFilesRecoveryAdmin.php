<?php
/**
 * Lost Files Recovery Admin
 *
 * Contendrá la lógica y UI para la recuperación de archivos perdidos.
 * Originalmente parte de app/Misc/recuperar_perdidos.php.
 *
 * @package Admin
 */

// Acción realizada: Mover funciones de recuperación de archivos perdidos desde app/Misc/recuperar_perdidos.php

function ra_add_admin_menu() {
    # add_menu_page('Recuperar Archivos', 'Recuperar Archivos', 'manage_options', 'recuperar-archivos', 'ra_admin_page');
}

// Página de administración
function ra_admin_page() {
    ?>
    <div class="wrap">
        <h1>Recuperar Archivos Perdidos</h1>
        <form method="post" action="">
            <?php wp_nonce_field('ra_scan_action', 'ra_scan_nonce'); ?>
            <input type="submit" name="ra_scan" class="button button-primary" value="Escanear archivos perdidos">
        </form>
        <?php
        if (isset($_POST['ra_scan']) && check_admin_referer('ra_scan_action', 'ra_scan_nonce')) {
            $lost_files = ra_scan_lost_files();
            if (!empty($lost_files)) {
                echo "<h2>Archivos perdidos encontrados:</h2>";
                echo "<ul>";
                foreach ($lost_files as $post_id => $file) { // Changed to key=>value
                    // // Displaying post ID along with the file path might be useful
                    // echo "<li>Post ID: " . esc_html($post_id) . " - Archivo: " . esc_html($file) . "</li>";
                    echo "<li>" . esc_html($file) . "</li>"; // Kept original display format
                }
                echo "</ul>";
                ?>
                <form method="post" action="">
                    <?php wp_nonce_field('ra_recover_action', 'ra_recover_nonce'); ?>
                    <input type="hidden" name="lost_files" value="<?php echo esc_attr(json_encode($lost_files)); ?>">
                    <input type="submit" name="ra_recover" class="button button-primary" value="Recuperar archivos">
                </form>
                <?php
            } else {
                echo "<p>No se encontraron archivos perdidos.</p>";
            }
        }
        if (isset($_POST['ra_recover']) && check_admin_referer('ra_recover_action', 'ra_recover_nonce')) {
            // // Ensure stripslashes is needed; modern WP often handles this. Assuming it is for now.
            $lost_files_json = isset($_POST['lost_files']) ? stripslashes($_POST['lost_files']) : '[]';
            $lost_files = json_decode($lost_files_json, true);
            if (is_array($lost_files)) {
                 ra_recover_files($lost_files);
            } else {
                 echo "<p>Error: Datos de archivos perdidos inválidos.</p>";
            }
        }
        ?>
    </div>
    <?php
}

// Función para escanear archivos perdidos
function ra_scan_lost_files() {
    global $wpdb;
    $lost_files = array();

    // // Consider adding pagination or limits for large sites
    $attachments = $wpdb->get_results(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'"
    );

    if ($attachments) {
        $upload_dir_info = wp_upload_dir();
        $basedir = $upload_dir_info['basedir'];

        foreach ($attachments as $attachment) {
            if (empty($attachment->meta_value)) {
                continue; // Skip if meta value is empty
            }

            $file_path = $attachment->meta_value;
            $full_path = $basedir . '/' . $file_path;

            // // Normalize slashes for cross-platform compatibility
            $full_path = wp_normalize_path($full_path);

            if (!file_exists($full_path)) {
                // // Check if the file exists without the date-based folder structure
                // // Example: uploads/file.jpg instead of uploads/2023/10/file.jpg
                $possible_path = preg_replace('/^(\d{4})\/(\d{2})\//', '', $file_path);
                if ($possible_path !== $file_path) { // Ensure regex actually changed the path
                    $possible_full_path = $basedir . '/' . $possible_path;
                    $possible_full_path = wp_normalize_path($possible_full_path);

                    if (file_exists($possible_full_path)) {
                        // // Store the original problematic path keyed by post_id
                        $lost_files[$attachment->post_id] = $file_path;
                    }
                }
            }
        }
    }

    return $lost_files;
}

// Función para recuperar archivos
function ra_recover_files($lost_files) {
    if (!is_array($lost_files) || empty($lost_files)) {
        echo "<p>No hay archivos para recuperar o formato inválido.</p>";
        return;
    }

    $upload_dir_info = wp_upload_dir();
    $basedir = $upload_dir_info['basedir'];

    foreach ($lost_files as $post_id => $file_path) {
        $old_path = $basedir . '/' . $file_path; // The path WP *thinks* the file should be at
        $old_path = wp_normalize_path($old_path);

        // // Derive the path where the file *actually* is (without date folders)
        $current_path_relative = preg_replace('/^(\d{4})\/(\d{2})\//', '', $file_path);
        $current_full_path = $basedir . '/' . $current_path_relative;
        $current_full_path = wp_normalize_path($current_full_path);

        // // Double-check the file actually exists at the 'current' location before trying to copy
        if (file_exists($current_full_path)) {
            $dir = dirname($old_path);
            if (!file_exists($dir)) {
                // // Attempt to create the directory structure (e.g., 2023/10/)
                if (!wp_mkdir_p($dir)) {
                     error_log("Error creando directorio: " . $dir); // Log error
                     echo "<p>Error al crear directorio para: " . esc_html($file_path) . "</p>";
                     continue; // Skip to next file
                }
            }

            // // Attempt to copy the file to the location WP expects
            if (copy($current_full_path, $old_path)) {
                // // Update the post meta just to be sure, though it should already be $file_path
                // // This step might be redundant if the meta value is already correct,
                // // but confirms the link between post and the now-correctly-placed file.
                update_post_meta($post_id, '_wp_attached_file', $file_path);

                // // Generate attachment metadata (thumbnails, etc.)
                // // This is crucial as the file might have been missing metadata
                $attach_data = wp_generate_attachment_metadata($post_id, $old_path);
                wp_update_attachment_metadata($post_id, $attach_data);

                echo "<p>Archivo recuperado y metadatos regenerados para: " . esc_html($file_path) . "</p>";
            } else {
                 error_log("Error copiando archivo: de " . $current_full_path . " a " . $old_path); // Log error
                 echo "<p>Error al copiar archivo para recuperar: " . esc_html($file_path) . "</p>";
            }
        } else {
             error_log("Archivo fuente no encontrado en la ubicación esperada para recuperación: " . $current_full_path); // Log error
             echo "<p>Error: Archivo fuente no encontrado para recuperar: " . esc_html($current_path_relative) . "</p>";
        }
    }
     echo "<p>Proceso de recuperación completado.</p>";
}

// Hook para añadir el menú de administración
# add_action('admin_menu', 'ra_add_admin_menu');

?>