<?php
//MOSTRAR AUDIO
function wave($audio_url, $audio_id_lite, $post_id)
{
    if ($audio_url) :

        $wave = get_post_meta($post_id, 'waveform_image_url', true);
        $waveCargada = get_post_meta($post_id, 'waveCargada', true);
    ?>

        <div id="waveform-<?php echo $post_id; ?>" class="waveform-container without-image" postIDWave="<?php echo $post_id; ?>"
            data-audio-url="<?php echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"
            data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>">
            <div class="waveform-background" style="background-image: url('<?php echo $wave; ?>');"></div>
            <div class="waveform-message"></div>
            <div class="waveform-loading" style="display: none;">Cargando...</div>
        </div>

    <?php endif;
}


add_action('wp_ajax_save_waveform_image', 'save_waveform_image');
add_action('wp_ajax_nopriv_save_waveform_image', 'save_waveform_image');


function save_waveform_image()
{
    guardar_log('Iniciando la función save_waveform_image.');

    if (!isset($_FILES['image']) || !isset($_POST['post_id'])) {
        guardar_log('Datos incompletos: ' . print_r($_POST, true) . print_r($_FILES, true));
        wp_send_json_error('Datos incompletos');
        return;
    }

    $file = $_FILES['image'];
    $post_id = intval($_POST['post_id']);

    guardar_log('Archivo recibido: ' . print_r($file, true));

    // Verificar si waveCargada es false
    $wave_cargada = get_post_meta($post_id, 'waveCargada', true);
    if ($wave_cargada === 'false' || $wave_cargada === false) {
        // Si es false, eliminar la imagen anterior si existe
        $existing_attachment_id = get_post_meta($post_id, 'waveform_image_id', true);
        if ($existing_attachment_id) {
            wp_delete_attachment($existing_attachment_id, true);
            guardar_log('Imagen anterior eliminada: ' . $existing_attachment_id);
        }
    }

    // Agregar el ID del post al nombre del archivo para evitar duplicados
    add_filter('wp_handle_upload_prefilter', function ($file) use ($post_id) {
        $file['name'] = $post_id . '_' . $file['name'];
        return $file;
    });

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $attachment_id = media_handle_upload('image', $post_id);

    // Remover el filtro después de la carga
    remove_filter('wp_handle_upload_prefilter', function ($file) use ($post_id) {
        $file['name'] = $post_id . '_' . $file['name'];
        return $file;
    });

    if (is_wp_error($attachment_id)) {
        guardar_log('Error al subir la imagen: ' . $attachment_id->get_error_message());
        wp_send_json_error('Error al subir la imagen');
        return;
    }

    // Obtener la URL de la imagen
    $image_url = wp_get_attachment_url($attachment_id);

    // Obtener el tamaño de la imagen
    $file_path = get_attached_file($attachment_id);
    $file_size = size_format(filesize($file_path), 2);

    // Actualizar los metadatos del post
    update_post_meta($post_id, 'waveform_image_id', $attachment_id);
    update_post_meta($post_id, 'waveform_image_url', $image_url);
    update_post_meta($post_id, 'waveCargada', true);

    guardar_log('Imagen guardada correctamente - ID: ' . $attachment_id . ', URL: ' . $image_url);
    wp_send_json_success(array(
        'message' => 'Imagen guardada correctamente',
        'url' => $image_url,
        'size' => $file_size
    ));
}


function wavejs()
{

    wp_enqueue_script('wavejs', get_template_directory_uri() . '/js/wavejs.js', array('jquery', 'wavesurfer'), '2.0.12', true);

    wp_localize_script(
        'wavejs',
        'ajax_params',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        )
    );
}
add_action('wp_enqueue_scripts', 'wavejs');

function reset_waveform_metas() {
    guardar_log("Iniciando la función reset_waveform_metas.");

    $args = array(
        'post_type' => 'social_post', // Ajusta esto si necesitas otros tipos de post
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'waveCargada',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    guardar_log("WP_Query ejecutado. Número de posts encontrados: " . $query->found_posts);
    
    if ($query->have_posts()) {
        guardar_log("Entrando en el bucle de posts.");
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            guardar_log("Procesando el post ID $post_id.");
            
            // Resetear waveCargada a false
            $updated = update_post_meta($post_id, 'waveCargada', false);
            if ($updated) {
                guardar_log("Metadato 'waveCargada' para el post ID $post_id actualizado a false.");
            } else {
                guardar_log("Error al actualizar el metadato 'waveCargada' para el post ID $post_id.");
            }
            
            // Opcional: eliminar la imagen de waveform existente
            $existing_attachment_id = get_post_meta($post_id, 'waveform_image_id', true);
            if ($existing_attachment_id) {
                guardar_log("Imagen de waveform existente encontrada con ID $existing_attachment_id para el post ID $post_id.");
                if (wp_delete_attachment($existing_attachment_id, true)) {
                    guardar_log("Imagen de waveform con ID $existing_attachment_id eliminada para el post ID $post_id.");
                } else {
                    guardar_log("Error al eliminar la imagen de waveform con ID $existing_attachment_id para el post ID $post_id.");
                }
            } else {
                guardar_log("No se encontró ninguna imagen de waveform para el post ID $post_id.");
            }
            
            // Eliminar los metadatos relacionados con la waveform
            $deleted_image_id = delete_post_meta($post_id, 'waveform_image_id');
            $deleted_image_url = delete_post_meta($post_id, 'waveform_image_url');
            if ($deleted_image_id && $deleted_image_url) {
                guardar_log("Metadatos 'waveform_image_id' y 'waveform_image_url' eliminados para el post ID $post_id.");
            } else {
                guardar_log("Error al eliminar metadatos de waveform para el post ID $post_id.");
            }
        }
    } else {
        guardar_log("No se encontraron posts con el metadato 'waveCargada' igual a true.");
    }
    
    wp_reset_postdata();
    guardar_log("Finalizando la función reset_waveform_metas.");
}


