<? 

function subirImagenDesdeURL($image_url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Usa la función media_sideload_image para subir la imagen al servidor
    $media = media_sideload_image($image_url, $post_id, null, 'id');

    if (is_wp_error($media)) {
        return false;
    }

    return $media;
}

function adjuntarArchivo($newPostId, $fileUrl) {
    global $wpdb;
    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE guid = %s",
        $fileUrl
    ));

    if (!$attachment_id) {
        $uploads_dir = wp_upload_dir();
        $file_path = str_replace($uploads_dir['baseurl'], $uploads_dir['basedir'], $fileUrl);

        if (file_exists($file_path)) {
            $mime_type = mime_content_type($file_path);
            $data = [
                'guid'           => $fileUrl,
                'post_mime_type' => $mime_type,
                'post_title'     => wp_basename($file_path),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $attachment_id = wp_insert_attachment($data, $file_path, $newPostId);

            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $attach_data);
            }
        } else {
            guardarLog("Archivo físico no encontrado para adjuntar.");
            return false;
        }
    }

    if ($attachment_id) {
        update_post_meta($newPostId, 'colabFileId', $attachment_id);
        update_post_meta($newPostId, 'colabFileUrl', $fileUrl);
        $mime_type = get_post_mime_type($attachment_id); 
        if (strpos($mime_type, 'audio') !== false) {
            $audio_id = $attachment_id; 
            $index = 1; 
            procesarAudioLigero($newPostId, $audio_id, $index);
        }

        guardarLog("Archivo adjuntado con ID: $attachment_id");
        return true;
    }

    return false;
}