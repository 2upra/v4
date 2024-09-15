<?php


function procesarAudioLigero($post_id, $audio_id, $index)
{
    guardarLog("INICIO procesarAudioLigero");

    // Obtener el archivo de audio original
    $audio_path = get_attached_file($audio_id);
    guardarLog("Ruta del archivo de audio original: {$audio_path}");

    // Obtener las partes del camino del archivo
    $path_parts = pathinfo($audio_path);
    $unique_id = uniqid('2upra_');
    $base_path = $path_parts['dirname'] . '/' . $unique_id;

    // Procesar archivo de audio ligero (128 kbps)
    $nuevo_archivo_path_lite = $base_path . '_128k.mp3';
    $comando_lite = "/usr/bin/ffmpeg -i {$audio_path} -b:a 128k {$nuevo_archivo_path_lite}";
    guardarLog("Ejecutando comando: {$comando_lite}");
    exec($comando_lite, $output_lite, $return_var_lite);
    if ($return_var_lite !== 0) {
        guardarLog("Error al procesar audio ligero: " . implode("\n", $output_lite));
    }

    // Insertar archivos en la biblioteca de medios
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Archivo ligero
    $filetype_lite = wp_check_filetype(basename($nuevo_archivo_path_lite), null);
    $attachment_lite = array(
        'post_mime_type' => $filetype_lite['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', basename($nuevo_archivo_path_lite)),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id_lite = wp_insert_attachment($attachment_lite, $nuevo_archivo_path_lite, $post_id);
    guardarLog("ID de adjunto ligero: {$attach_id_lite}");
    $attach_data_lite = wp_generate_attachment_metadata($attach_id_lite, $nuevo_archivo_path_lite);
    wp_update_attachment_metadata($attach_id_lite, $attach_data_lite);
    
    // Determinar la clave meta a usar
    $meta_key = ($index == 1) ? "post_audio_lite" : "post_audio_lite_{$index}";
    update_post_meta($post_id, $meta_key, $attach_id_lite);

    // Extraer y guardar la duración del audio
    $duration_command = "/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$nuevo_archivo_path_lite}";
    guardarLog("Ejecutando comando para duración del audio: {$duration_command}");
    $duration_in_seconds = shell_exec($duration_command);
    guardarLog("Salida de ffprobe: '{$duration_in_seconds}'");

    // Limpiar y validar la duración del audio
    $duration_in_seconds = trim($duration_in_seconds);
    if (is_numeric($duration_in_seconds)) {
        $duration_in_seconds = (float)$duration_in_seconds;
        $duration_formatted = floor($duration_in_seconds / 60) . ':' . str_pad($duration_in_seconds % 60, 2, '0', STR_PAD_LEFT);
        update_post_meta($post_id, "audio_duration_{$index}", $duration_formatted);
        guardarLog("Duración del audio (formateada): {$duration_formatted}");
    } else {
        guardarLog("Duración del audio no válida para el archivo {$audio_path}");
    }
}