<?php
/*
add_action('admin_menu', 'ra_add_admin_menu');

function ra_add_admin_menu() {
    add_menu_page('Recuperar Archivos', 'Recuperar Archivos', 'manage_options', 'recuperar-archivos', 'ra_admin_page');
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
                foreach ($lost_files as $file) {
                    echo "<li>$file</li>";
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
            $lost_files = json_decode(stripslashes($_POST['lost_files']), true);
            ra_recover_files($lost_files);
        }
        ?>
    </div>
    <?php
}

// Función para escanear archivos perdidos
function ra_scan_lost_files() {
    global $wpdb;
    $lost_files = array();

    $attachments = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'");

    foreach ($attachments as $attachment) {
        $file_path = $attachment->meta_value;
        $full_path = wp_upload_dir()['basedir'] . '/' . $file_path;

        if (!file_exists($full_path)) {
            $possible_path = preg_replace('/^(\d{4})\/(\d{2})\//', '', $file_path);
            $possible_full_path = wp_upload_dir()['basedir'] . '/' . $possible_path;

            if (file_exists($possible_full_path)) {
                $lost_files[$attachment->post_id] = $file_path;
            }
        }
    }

    return $lost_files;
}

// Función para recuperar archivos
function ra_recover_files($lost_files) {
    foreach ($lost_files as $post_id => $file_path) {
        $old_path = wp_upload_dir()['basedir'] . '/' . $file_path;
        $new_path = preg_replace('/^(\d{4})\/(\d{2})\//', '', $file_path);
        $new_full_path = wp_upload_dir()['basedir'] . '/' . $new_path;

        if (file_exists($new_full_path)) {
            $dir = dirname($old_path);
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
            if (copy($new_full_path, $old_path)) {
                update_post_meta($post_id, '_wp_attached_file', $file_path);
                echo "<p>Archivo recuperado: $file_path</p>";
            } else {
                echo "<p>Error al recuperar: $file_path</p>";
            }
        }
    }
}
*/
