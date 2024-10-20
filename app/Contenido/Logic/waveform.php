<?
function save_waveform_image() {
    if (!isset($_FILES['image']) || !isset($_POST['post_id'])) {
        wp_send_json_error('Datos incompletos');
        return;
    }

    $file = $_FILES['image'];
    $post_id = intval($_POST['post_id']);

    // Eliminar la imagen anterior si waveCargada es false.
    if (get_post_meta($post_id, 'waveCargada', true) === 'false') {
        $existing_attachment_id = get_post_meta($post_id, 'waveform_image_id', true);
        if ($existing_attachment_id) {
            wp_delete_attachment($existing_attachment_id, true);
        }
    }

    // Agregar el ID del post al nombre del archivo para evitar duplicados.
    add_filter('wp_handle_upload_prefilter', function ($file) use ($post_id) {
        $file['name'] = $post_id . '_' . $file['name'];
        return $file;
    });

    // Subir la imagen.
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Obtener el autor del post y asignar la imagen a él.
    $author_id = get_post_field('post_author', $post_id);
    $attachment_id = media_handle_upload('image', $post_id, array('post_author' => $author_id));

    // Remover el filtro.
    remove_filter('wp_handle_upload_prefilter', function ($file) use ($post_id) {
        $file['name'] = $post_id . '_' . $file['name'];
        return $file;
    });

    // Manejar errores de subida.
    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Error al subir la imagen');
        return;
    }

    // Obtener la URL y el tamaño de la imagen.
    $image_url = wp_get_attachment_url($attachment_id);
    $file_path = get_attached_file($attachment_id);
    $file_size = size_format(filesize($file_path), 2);

    // Actualizar los metadatos del post.
    update_post_meta($post_id, 'waveform_image_id', $attachment_id);
    update_post_meta($post_id, 'waveform_image_url', $image_url);
    update_post_meta($post_id, 'waveCargada', true);

    wp_send_json_success(array(
        'message' => 'Imagen guardada correctamente',
        'url' => $image_url,
        'size' => $file_size
    ));
}

function reset_waveform_metas()
{
    guardarLog("Iniciando la función reset_waveform_metas.");

    $args = array(
        'post_type' => 'social_post',
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
    guardarLog("WP_Query ejecutado. Número de posts encontrados: " . $query->found_posts);

    if ($query->have_posts()) {
        guardarLog("Entrando en el bucle de posts.");
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            guardarLog("Procesando el post ID $post_id.");

            // Resetear waveCargada a false.
            update_post_meta($post_id, 'waveCargada', false);

            // Eliminar la imagen de waveform existente.
            $existing_attachment_id = get_post_meta($post_id, 'waveform_image_id', true);
            if ($existing_attachment_id) {
                wp_delete_attachment($existing_attachment_id, true);
            }

            // Eliminar los metadatos relacionados con la waveform.
            delete_post_meta($post_id, 'waveform_image_id');
            delete_post_meta($post_id, 'waveform_image_url');
        }
    } else {
        guardarLog("No se encontraron posts con el metadato 'waveCargada' igual a true.");
    }

    wp_reset_postdata();
    guardarLog("Finalizando la función reset_waveform_metas.");
}

// Registrar las acciones AJAX.
add_action('wp_ajax_save_waveform_image', 'save_waveform_image');
add_action('wp_ajax_nopriv_save_waveform_image', 'save_waveform_image');
