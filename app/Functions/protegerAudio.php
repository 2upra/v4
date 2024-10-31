<?

add_filter('pre_delete_attachment', function($delete, $post) {
    if (strpos(get_attached_file($post), '/audio/') !== false) {
        logAudio("Intento de eliminación de archivo de audio: " . get_attached_file($post));
        return false;
    }
    return $delete;
}, 10, 2);


//No se que hace esto exactamente, pero, podemos verificar la existencia de todos los adjuntos de los post que contengan un audio en esa carpeta a ver si carga
function verificarAudio() {
    $upload_dir = wp_upload_dir();
    $audio_dir = $upload_dir['basedir'] . '/audio/';
    
    if (!is_dir($audio_dir)) {
        logAudio("Directorio de audio no existe: $audio_dir");
        return;
    }

    // Verificar archivos MP3 en el directorio
    $mp3_files = glob($audio_dir . "*.mp3");
    if (empty($mp3_files)) {
        logAudio("No se encontraron archivos MP3 en el directorio");
        return;
    }

    foreach ($mp3_files as $file) {
        if (!file_exists($file)) {
            logAudio("Archivo MP3 no encontrado: $file");
            continue;
        }

        // Verificar permisos
        $perms = fileperms($file);
        if (($perms & 0644) !== 0644) {
            logAudio("Permisos incorrectos en archivo: $file - Permisos actuales: " . decoct($perms & 0777));
        }

        // Verificar si el archivo está vinculado a un post
        $attachment_id = attachment_url_to_postid(str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file));
        if (!$attachment_id) {
            logAudio("Archivo no vinculado a ningún post: $file");
        } else {
            // Verificar el post padre
            $attachment = get_post($attachment_id);
            if ($attachment->post_parent) {
                $parent = get_post($attachment->post_parent);
                if (!$parent || $parent->post_status !== 'publish') {
                    logAudio("Post padre no publicado o no existe para: $file");
                }
            }
        }

        // Verificar tamaño del archivo
        $filesize = filesize($file);
        if ($filesize < 1024) { // menos de 1KB
            logAudio("Archivo sospechosamente pequeño: $file - Tamaño: $filesize bytes");
        }
    }

    // Verificar archivos WAV correspondientes
    foreach ($mp3_files as $mp3_file) {
        $wav_file = str_replace('_lite.mp3', '.wav', $mp3_file);
        if (!file_exists($wav_file)) {
            logAudio("Archivo WAV correspondiente no encontrado: $wav_file");
        }
    }
}

function programar_verificacion_audio() {
    if (!wp_next_scheduled('verificar_audio_diario')) {
        wp_schedule_event(time(), 'daily', 'verificar_audio_diario');
    }
}
add_action('wp', 'programar_verificacion_audio');
add_action('verificar_audio_diario', 'verificarAudio');
add_action('init', 'verificarAudio');