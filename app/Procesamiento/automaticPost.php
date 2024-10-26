<?

#Auxiliares








// ETAPA 2 - PROCESAR EL AUDIO ENCONTRADO
//////////////////////////////////////////////////////////////////////////////

function autProcesarAudio($audio_path)
{
    //guardarLog("--Inicio de la función autProcesarAudio.--");

    // Verificar si el archivo existe
    if (!file_exists($audio_path)) {
        //guardarLog("Archivo no encontrado: $audio_path");
        return;
    }

    // Obtener partes del path
    $path_parts = pathinfo($audio_path);
    $directory = realpath($path_parts['dirname']);
    if ($directory === false) {
        //guardarLog("Directorio inválido: {$path_parts['dirname']}");
        return;
    }
    $extension = strtolower($path_parts['extension']);
    $basename = $path_parts['filename'];

    //guardarLog("Ruta inicial: $audio_path, Directorio: $directory, Basename: $basename, Extensión: $extension");

    // Obtener ID del archivo por la ruta directa
    $file_id = obtenerFileIDPorURL($audio_path);
    if ($file_id === false) {
        //guardarLog("File ID no encontrado para la ruta: $audio_path");
        return;
    } else {
        //guardarLog("File ID obtenido: $file_id");
    }

    // Ruta temporal para eliminar metadatos
    $temp_path = "$directory/{$basename}_temp.$extension";

    // 1. Eliminar metadatos con ffmpeg
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c copy " . escapeshellarg($temp_path) . " -y";
    //guardarLog("Comando para eliminar metadatos: $comando_strip_metadata");
    exec($comando_strip_metadata, $output_strip, $return_strip);
    if ($return_strip !== 0) {
        //guardarLog("Error al eliminar metadatos: " . implode(" | ", $output_strip));
        return;
    }

    // Reemplazar archivo original
    if (!rename($temp_path, $audio_path)) {
        //guardarLog("No se pudo reemplazar el archivo original.");
        return;
    }
    //guardarLog("Metadatos eliminados del archivo: $audio_path");

    // 2. Crear versión lite en MP3 a 128 kbps
    $lite_path = "$directory/{$basename}_lite.mp3";
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k " . escapeshellarg($lite_path) . " -y";
    //guardarLog("Comando para crear versión lite: $comando_lite");
    exec($comando_lite, $output_lite, $return_lite);
    if ($return_lite !== 0) {
        //guardarLog("Error al crear versión lite: " . implode(" | ", $output_lite));
        return;
    }
    //guardarLog("Versión lite creada: $lite_path");

    // 3. Obtener nombre limpio por IA
    $nombre_limpio = generarNombreAudio($lite_path);
    if (empty($nombre_limpio)) {
        //guardarLog("Nombre limpio inválido.");
        return;
    }
    //guardarLog("Nombre limpio generado: $nombre_limpio");

    // 4. Renombrar archivo original
    $nuevo_nombre_original = "$directory/$nombre_limpio.$extension";
    if (!rename($audio_path, $nuevo_nombre_original)) {
        //guardarLog("No se pudo renombrar el archivo original.");
        return;
    }
    //guardarLog("Archivo original renombrado: $nuevo_nombre_original");

    // 5. Renombrar archivo lite
    $nuevo_nombre_lite = "$directory/{$nombre_limpio}_lite.mp3";
    if (!rename($lite_path, $nuevo_nombre_lite)) {
        //guardarLog("No se pudo renombrar el archivo lite.");
        return;
    }
    //guardarLog("Archivo lite renombrado: $nuevo_nombre_lite");

    // 6. Mover el archivo lite al directorio de uploads
    $uploads_dir = wp_upload_dir();
    $target_dir_audio = trailingslashit($uploads_dir['basedir']) . "audio/";

    // Crear directorio 'audio' si no existe
    if (!file_exists($target_dir_audio)) {
        if (!wp_mkdir_p($target_dir_audio)) {
            //guardarLog("No se pudo crear el directorio de uploads/audio.");

            return;
        }
    }

    $target_path_lite = $target_dir_audio . "{$nombre_limpio}_lite.mp3";

    // Mover archivo lite
    if (!rename($nuevo_nombre_lite, $target_path_lite)) {
        //guardarLog("No se pudo mover el archivo lite al directorio de uploads.");
        return;
    }
    //guardarLog("Archivo lite movido al directorio de uploads: $target_path_lite");


    // 7. Enviar rutas a crearAutPost
    //guardarLog("Enviando rutas a crearAutPost: Original - $nuevo_nombre_original, Lite - $target_path_lite");
    crearAutPost($nuevo_nombre_original, $target_path_lite, $file_id, $lite_path);
    //guardarLog("Archivos enviados a crearAutPost.");

    //guardarLog("--Fin de la función autProcesarAudio.--");
}

function generarNombreAudio($audio_path_lite)
{
    // Verificar que el archivo de audio exista
    if (!file_exists($audio_path_lite)) {
        iaLog("ERROR: El archivo de audio no existe en la ruta especificada: {$audio_path_lite}");
        return '2upra_Error El archivo de audio no existe';
    }

    // Obtener el nombre del archivo, la carpeta contenedora y la carpeta un nivel arriba
    $nombre_archivo = pathinfo($audio_path_lite, PATHINFO_FILENAME);
    $carpeta = basename(dirname($audio_path_lite));
    $carpeta_abuela = basename(dirname(dirname($audio_path_lite))); // Obtener la carpeta un nivel más arriba

    // Preparar el prompt para la IA incluyendo tanto el nombre del archivo como las carpetas
    $prompt = "El archivo '{$nombre_archivo}' está en la carpeta '{$carpeta}', y a su vez esta carpeta está en '{$carpeta_abuela}'. Te lo enseño para que lo tomes en cuenta. A veces tendrá sentido el nombre y otras no, pero es importante considerarlo. A veces vienen con nombres de marcas, páginas, etc, hay que ignorar eso. Escucha este audio y por favor, genera un nombre corto que lo represente. Por lo general son samples, como un kick, snare, sample o efectos, vocales, percusiones, pero puede ser cualquier cosa etc. Importante: solo responde el nombre, no agregues nada adicional. Estás en un entorno automatizado, no hables con el usuario, solo estoy pidiendo el nombre corto como respuesta. Ignora la palabra lite. Muchas veces los nombres deben manterse en ingles como sean mas comunes, por ejemplo snare, kick, sample, hi hats, etc. No uses acentos en las letras.";

    try {
        // Registrar el prompt enviado a la IA
        iaLog("INFO: Enviando prompt a la IA: $prompt");

        // Generar el nombre usando la IA
        $nombre_generado = generarDescripcionIA($audio_path_lite, $prompt);

        // Registrar la respuesta de la IA
        iaLog("INFO: Respuesta de la IA: $nombre_generado");

        // Verificar si se obtuvo una respuesta válida
        if ($nombre_generado) {
            // Limpiar la respuesta obtenida (eliminar espacios en blanco al inicio y al final)
            $nombre_generado_limpio = trim($nombre_generado);
            $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- ]/', '', $nombre_generado_limpio);

            // Limitar el nombre a 60 caracteres
            $nombre_generado_limpio = substr($nombre_generado_limpio, 0, 70);

            // Añadir el identificador único '2upra_' al inicio
            $nombre_final = '2upra_' . $nombre_generado_limpio;

            // Generar una ID aleatoria única de 4 caracteres (letras y números)
            $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);

            // Añadir la ID única al final del nombre
            $nombre_final_con_id = $nombre_final . '_' . $id_unica;

            // Asegurarse de que el nombre completo no exceda los 60 caracteres
            $nombre_final_con_id = substr($nombre_final_con_id, 0, 60);

            //guardarLog("INFO: Nombre final generado: $nombre_final_con_id");
            return $nombre_final_con_id;
        } else {
            iaLog("ERROR: No se recibió una respuesta válida de la IA para el archivo de audio: {$audio_path_lite}");
            return '2upra_Error Respuesta inesperada de la API Detalles ' . basename($audio_path_lite) . '.wav';
        }
    } catch (Exception $e) {
        // Capturar y registrar cualquier excepción que ocurra durante el proceso
        iaLog("EXCEPCIÓN: " . $e->getMessage());
        return '2upra_Error Excepción durante la generación del nombre';
    }
}


function rehacerNombreAudio($post_id, $archivo_audio)
{
    // Verificar si el archivo de audio existe
    if (!file_exists($archivo_audio)) {
        iaLog("El archivo de audio no existe en la ruta especificada: {$archivo_audio}");
        return null;
    }

    $user_id = get_current_user_id();

    if (!user_can($user_id, 'administrator')) {
        return;
    }

    // Obtener el contenido del post
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
    }
    iaLog("Contenido del post obtenido para el post ID: {$post_id}");

    // Obtener el nombre del archivo a partir de la ruta
    $nombre_archivo = pathinfo($archivo_audio, PATHINFO_FILENAME);

    // Crear el prompt para la IA con el nombre del archivo incluido
    $prompt = "El archivo se llama '{$nombre_archivo}' es un nombre viejo porque el usuario ha cambiado o mejorado la descripción, la descripción nueva que escribió el usuario es '{$post_content}'. Escucha este audio y por favor, genera un nombre corto que lo represente tomando en cuenta la descripción que generó el usuario. Por lo general son samples, loop, fx, one shot, etc. Imporante: solo responde el nombre, no agregues nada adicional, estas en un entorno automatizado, no hables con el usuario, solo estoy pidiendo el nombre corto como respuesta.";

    // Generar el nombre usando la IA
    $nombre_generado = generarDescripcionIA($archivo_audio, $prompt);

    // Verificar si se obtuvo una respuesta válida
    if ($nombre_generado) {
        // Limpiar el nombre generado
        $nombre_generado_limpio = trim($nombre_generado);
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- ]/', '', $nombre_generado_limpio);
        $nombre_generado_limpio = substr($nombre_generado_limpio, 0, 60);
        $nombre_final = '2upra_' . $nombre_generado_limpio;
        $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $nombre_final_con_id = $nombre_final . '_' . $id_unica;
        $nombre_final_con_id = substr($nombre_final_con_id, 0, 60);

        iaLog("Nombre generado: {$nombre_final_con_id}");

        // Obtener los IDs de los adjuntos desde los metadatos del post
        $attachment_id_audio = get_post_meta($post_id, 'post_audio', true);
        $attachment_id_audio_lite = get_post_meta($post_id, 'post_audio_lite', true);

        // Verificar que los IDs de adjunto existan
        if (!$attachment_id_audio) {
            iaLog("No se encontró el meta 'post_audio' para el post ID: {$post_id}");
            return null;
        }

        if (!$attachment_id_audio_lite) {
            iaLog("No se encontró el meta 'post_audio_lite' para el post ID: {$post_id}");
            return null;
        }

        // Renombrar los archivos adjuntos
        $renombrado_audio = renombrar_archivo_adjunto($attachment_id_audio, $nombre_final_con_id, false);
        if (!$renombrado_audio) {
            iaLog("Falló al renombrar el archivo 'post_audio' para el post ID: {$post_id}");
            return null;
        }

        $renombrado_audio_lite = renombrar_archivo_adjunto($attachment_id_audio_lite, $nombre_final_con_id, true);
        if (!$renombrado_audio_lite) {
            iaLog("Falló al renombrar el archivo 'post_audio_lite' para el post ID: {$post_id}");
            return null;
        }

        // Actualizar la meta 'rutaOriginal' o buscar en subcarpetas si no existe la ruta
        $ruta_original = get_post_meta($post_id, 'rutaOriginal', true);
        if ($ruta_original && file_exists($ruta_original)) {
            $directorio_original = pathinfo($ruta_original, PATHINFO_DIRNAME);
        } else {
            $directorio_original = buscarArchivoEnSubcarpetas("/home/asley01/MEGA/Waw/X", basename($ruta_original));
        }

        if ($directorio_original) {
            $ext_extension = pathinfo($ruta_original, PATHINFO_EXTENSION);
            $nueva_ruta_original = $directorio_original . '/' . $nombre_final_con_id . '.' . $ext_extension;

            if (rename($ruta_original, $nueva_ruta_original)) {
                update_post_meta($post_id, 'rutaOriginal', $nueva_ruta_original);
                iaLog("Meta 'rutaOriginal' actualizada a: {$nueva_ruta_original}");
                guardarLog("Archivo renombrado en el servidor de {$ruta_original} a {$nueva_ruta_original}");
            } else {
                guardarLog("Error en renombrar archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                iaLog("Error al renombrar el archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                update_post_meta($post_id, 'rutaOriginalPerdida', true);
            }
        } else {
            iaLog("No se encontró 'rutaOriginal' ni en la meta ni en las subcarpetas para el post ID: {$post_id}");
            update_post_meta($post_id, 'rutaPerdida', true);
        }

        // Actualizar la URL en base de datos si tiene idHash_audioId
        $id_hash_audio = get_post_meta($post_id, 'idHash_audioId', true);
        if ($id_hash_audio) {
            $nueva_url_audio = wp_get_attachment_url($attachment_id_audio);
            actualizarUrlArchivo($id_hash_audio, $nueva_url_audio);
            iaLog("URL de 'post_audio' actualizada para el hash ID: {$id_hash_audio}");
        } else {
            iaLog("Meta 'idHash_audioId' no existe para el post ID: {$post_id}");
        }

        iaLog("Renombrado completado exitosamente para el post ID: {$post_id}");
        update_post_meta($post_id, 'Verificado', true);

        return $nombre_final_con_id;
    } else {
        iaLog("No se recibió una respuesta válida de la IA para el archivo de audio: {$archivo_audio}");
        return null;
    }
}

// Función auxiliar para buscar archivos en subcarpetas, filtrando solo archivos de audio válidos
function buscarArchivoEnSubcarpetas($directorio_base, $nombre_archivo)
{
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio_base));
    foreach ($iterador as $archivo) {
        // Obtener la extensión y el nombre del archivo
        $extension = strtolower($archivo->getExtension());
        $nombre = $archivo->getFilename();

        // Ignorar archivos que no sean .wav o .mp3 y que no empiecen con "2upra"
        if (!in_array($extension, ['wav', 'mp3']) || strpos($nombre, '2upra') !== 0) {
            continue;
        }

        // Si el nombre coincide exactamente con el archivo buscado, devolver la ruta del directorio
        if ($nombre === $nombre_archivo) {
            return $archivo->getPath();
        }
    }
    return false;
}


function renombrar_archivo_adjunto($attachment_id, $nuevo_nombre, $es_lite = false)
{
    // Obtener el path completo del archivo adjunto
    $ruta_archivo = get_attached_file($attachment_id);
    if (!file_exists($ruta_archivo)) {
        iaLog("El archivo adjunto con ID {$attachment_id} no existe en la ruta: {$ruta_archivo}");
        return false;
    }

    // Obtener la carpeta y la extensión del archivo
    $carpeta = pathinfo($ruta_archivo, PATHINFO_DIRNAME);
    $extension = pathinfo($ruta_archivo, PATHINFO_EXTENSION);
    if ($es_lite) {
        $nuevo_nombre .= '_lite';
    }
    $nueva_ruta = $carpeta . '/' . $nuevo_nombre . '.' . $extension;

    // Renombrar el archivo
    if (!rename($ruta_archivo, $nueva_ruta)) {
        iaLog("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        guardarLog("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        return false;
    }

    iaLog("Archivo renombrado de {$ruta_archivo} a {$nueva_ruta}");
    guardarLog("Archivo renombrado en el servidor de {$ruta_archivo} a {$nueva_ruta}");

    // Actualizar la ruta del adjunto en la base de datos
    $wp_filetype = wp_check_filetype(basename($nueva_ruta), null);
    $attachment_data = array(
        'ID' => $attachment_id,
        'post_name' => sanitize_title($nuevo_nombre),
        'guid' => home_url('/') . str_replace(ABSPATH, '', $nueva_ruta),
    );

    // Actualizar el post del adjunto
    if (function_exists('wp_update_post')) {
        wp_update_post($attachment_data);
    }

    update_attached_file($attachment_id, $nueva_ruta);

    return true;
}







function crearAutPost($nuevo_nombre_original, $nuevo_nombre_lite, $file_id, $lite_path)
{
    $autor_id = 44;

    // Obtener el nombre del archivo, la carpeta contenedora y la carpeta abuela
    $nombre_archivo = pathinfo($lite_path, PATHINFO_FILENAME);
    $carpeta = basename(dirname($lite_path));
    $carpeta_abuela = basename(dirname(dirname($lite_path))); // Obtener la carpeta un nivel más arriba

    // Preparar el prompt con información de la ruta original y las carpetas
    $prompt = "Genera una descripción corta para el siguiente archivo de audio. Puede ser un sample, un fx, un loop, un sonido de un kick, puede ser cualquier cosa. El propósito es que la descripción sea corta (solo responde con la descripción, no digas nada adicional). Te doy ejemplos: Sample oscuro phonk, Fx de explosión, kick de house, sonido de sintetizador, piano melodía, guitarra acústica sample. Los nombres o descripciones algunas veces deben manterse en ingles como sean mas comunes, por ejemplo snare, kick, sample, hi hats, etc \n\n" .
        "Te muestro la ruta original del archivo, su carpeta y la carpeta abuela, ya que esta información puede ser relevante para determinar sobre qué trata el audio, no agregues informacion del archivo en la descripcion e ignora la palabra lite: \n" .
        "Archivo: '{$nombre_archivo}'\nCarpeta: '{$carpeta}'\nCarpeta abuela: '{$carpeta_abuela}'.";

    // Aquí puedes continuar el flujo para enviar este prompt a la IA y procesar la respuesta

    $descripcion = generarDescripcionIA($nuevo_nombre_lite, $prompt);

    if (is_wp_error($descripcion) || empty($descripcion)) {
        return new WP_Error('descripcion_generacion_fallida', 'No se pudo generar una descripción para el audio.');
    }

    $titulo = mb_substr($descripcion, 0, 60);
    $contenido = $descripcion;
    $post_data = [
        'post_title'    => $titulo,
        'post_content'  => $contenido,
        'post_status'   => 'publish',
        'post_author'   => $autor_id,
        'post_type'     => 'social_post',
    ];

    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        return $post_id;
    }
    $index = 1;
    update_post_meta($post_id, 'postAut', true);
    analizarYGuardarMetasAudio($post_id, $nuevo_nombre_lite, $index, $nombre_archivo, $carpeta, $carpeta_abuela);

    $audio_original_id = adjuntarArchivoAut($nuevo_nombre_original, $post_id, $file_id);
    if (is_wp_error($audio_original_id)) {
        wp_delete_post($post_id, true);
        return $audio_original_id;
    }

    $audio_lite_id = adjuntarArchivoAut($nuevo_nombre_lite, $post_id);
    if (is_wp_error($audio_lite_id)) {
        return $audio_lite_id;
    }

    // Guardar las rutas originales
    update_post_meta($post_id, 'rutaOriginal', $nuevo_nombre_original);
    update_post_meta($post_id, 'rutaLiteOriginal', $nuevo_nombre_lite);

    update_post_meta($post_id, 'post_audio', $audio_original_id);
    
    update_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
    update_post_meta($post_id, 'paraDescarga', true);

    //INFORMACION NUEVA PARA CARPETAS Y FUNCIONALIDAD DE KITS
    update_post_meta($post_id, 'nombreOriginal', $nombre_archivo);
    update_post_meta($post_id, 'carpetaOriginal', $carpeta);
    update_post_meta($post_id, 'carpetaAbuelaOriginal', $carpeta_abuela);

    return $post_id;
}

function adjuntarArchivoAut($archivo, $post_id, $file_id = null)
{

    if ($file_id !== null) {
        update_post_meta($post_id, 'idHash_audioId', $file_id);
    }

    // Variables para manejo de archivos temporales
    $es_url = filter_var($archivo, FILTER_VALIDATE_URL);
    $archivo_temp = '';

    if ($es_url) {
        // Descargar el archivo desde la URL a una ubicación temporal
        $temp_dir = sys_get_temp_dir();
        $nombre_archivo = basename(parse_url($archivo, PHP_URL_PATH));
        $archivo_temp = tempnam($temp_dir, 'upload_') . '_' . sanitize_file_name($nombre_archivo);

        $contenido = @file_get_contents($archivo);
        if ($contenido === false) {
            return new WP_Error('error_descarga', 'No se pudo descargar el archivo desde la URL: ' . esc_html($archivo));
        }

        // Guardar el contenido descargado en el archivo temporal
        if (file_put_contents($archivo_temp, $contenido) === false) {
            return new WP_Error('error_guardar_temporal', 'No se pudo guardar el archivo descargado en: ' . esc_html($archivo_temp));
        }

        $archivo_procesar = $archivo_temp;
    } else {
        // Asumir que es una ruta de archivo local
        $archivo_procesar = $archivo;
    }

    // Verificar si el archivo existe
    if (!file_exists($archivo_procesar)) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        return new WP_Error('archivo_no_encontrado', 'El archivo especificado no existe: ' . esc_html($archivo));
    }

    // Obtener información de la ruta de uploads
    $wp_upload_dir = wp_upload_dir();

    // Determinar si el archivo está dentro del directorio de uploads
    $ruta_relativa = '';
    if (strpos(realpath($archivo_procesar), realpath($wp_upload_dir['basedir'])) === 0) {
        // Archivo está dentro de uploads
        $ruta_relativa = str_replace(realpath($wp_upload_dir['basedir']) . DIRECTORY_SEPARATOR, '', realpath($archivo_procesar));
    }

    // Si el archivo no está en uploads, copiarlo al directorio de uploads
    if (empty($ruta_relativa)) {
        $filename = basename($archivo_procesar);
        $filename = sanitize_file_name($filename);
        $unique_filename = wp_unique_filename($wp_upload_dir['path'], $filename);
        $destino = trailingslashit($wp_upload_dir['path']) . $unique_filename;

        if (!copy($archivo_procesar, $destino)) {
            // Eliminar el archivo temporal si existe
            if ($es_url && file_exists($archivo_temp)) {
                @unlink($archivo_temp);
            }
            return new WP_Error('error_copia_archivo', 'No se pudo copiar el archivo al directorio de cargas.');
        }
    } else {
        // El archivo ya está en uploads
        $unique_filename = basename($archivo_procesar);
        $destino = $archivo_procesar;
    }

    // Obtener el tipo de archivo
    $filetype = wp_check_filetype($unique_filename, null);
    if (!$filetype['type']) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        // Si el archivo fue copiado a uploads, eliminarlo
        if (!empty($ruta_relativa)) {
            @unlink($destino);
        }
        return new WP_Error('tipo_archivo_no_soportado', 'El tipo de archivo no es soportado: ' . esc_html($unique_filename));
    }

    // Preparar los datos del adjunto
    $attachment = [
        'guid'           => $wp_upload_dir['url'] . '/' . $unique_filename,
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($unique_filename, PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    // Insertar el adjunto en la base de datos
    $attach_id = wp_insert_attachment($attachment, $destino, $post_id);

    if (is_wp_error($attach_id)) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        // Si el archivo fue copiado a uploads, eliminarlo
        if (!empty($ruta_relativa)) {
            @unlink($destino);
        }
        return $attach_id;
    }

    // Generar y actualizar los metadatos del adjunto
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $destino);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Obtener la URL del adjunto
    $adjunto_url = wp_get_attachment_url($attach_id);

    // Si se proporcionó un file_id, actualizar la URL usando actualizarUrlArchivo
    if (! is_null($file_id)) {
        // Asegúrate de que la función actualizarUrlArchivo esté definida
        if (function_exists('actualizarUrlArchivo')) {
            $resultado_actualizacion = actualizarUrlArchivo($file_id, $adjunto_url);

            // Verificar si la actualización fue exitosa
            if (is_wp_error($resultado_actualizacion)) {

                wp_delete_attachment($attach_id, true);
                return new WP_Error('actualizacion_url_fallida', 'No se pudo actualizar la URL del archivo: ' . $resultado_actualizacion->get_error_message());
            }
        } else {
            // Manejar el caso donde actualizarUrlArchivo no está definida
            wp_delete_attachment($attach_id, true);
            return new WP_Error('funcion_no_definida', 'La función actualizarUrlArchivo no está definida.');
        }
    }

    // Eliminar el archivo temporal si existe
    if ($es_url && file_exists($archivo_temp)) {
        @unlink($archivo_temp);
    }

    return $attach_id;
}

function rehacerDescripcionAccion($post_id)
{
    // Obtener el ID del audio lite del metadato 'post_audio_lite'
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);

    // Verificar si existe el ID
    if ($audio_lite_id) {
        // Obtener la ruta completa del archivo adjunto (audio lite)
        $archivo_audio = get_attached_file($audio_lite_id);

        if ($archivo_audio) {
            // Llamar a la función para rehacer la descripción con el archivo de audio
            rehacerDescripcionAudio($post_id, $archivo_audio);
            rehacerNombreAudio($post_id, $archivo_audio);

            iaLog("Descripción del audio actualizada para el post ID: {$post_id} con archivo de audio en la ruta {$archivo_audio}");
        } else {
            iaLog("No se pudo obtener la ruta del archivo de audio lite para el post ID: {$post_id}");
        }
    } else {
        iaLog("No se encontró el metadato 'post_audio_lite' para el post ID: {$post_id}");
    }
}



function buscar_archivo_recursivo($dir, $filename)
{
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            $result = buscar_archivo_recursivo($path, $filename);
            if ($result !== false) {
                return $result;
            }
        } elseif ($file === $filename) {
            return $path;
        }
    }
    return false;
}

function obtenerFileIDPorURL($url)
{
    global $wpdb;

    $file_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}file_hashes WHERE file_url = %s",
            $url
        )
    );

    if ($file_id !== null) {
        return (int) $file_id;
    } else {
        //guardarLog("No se encontró File ID para la URL: $url");
        return false;
    }
}
/*
function actualizar_metas_posts_social() {
    // Define los argumentos para la consulta de posts
    $args = array(
        'post_type'      => 'social_post',
        'meta_key'       => 'postAut',
        'meta_value'     => '1',
        'posts_per_page' => -1, // Obtener todos los posts
        'fields'         => 'ids', // Solo IDs para eficiencia
    );

    $query = new WP_Query($args);

    if ( !$query->have_posts() ) {
        //guardarLog('No se encontraron posts de tipo social_post con postAut=1.');
        return;
    }

    // Directorio base donde se buscarán los archivos originales
    $directorio_base = '/home/asley01/MEGA/Waw/X/';

    foreach ( $query->posts as $post_id ) {
        // Verificar si los metadatos ya existen
        $rutaOriginal = get_post_meta( $post_id, 'rutaOriginal', true );
        $rutaLiteOriginal = get_post_meta( $post_id, 'rutaLiteOriginal', true );
        $idHash_audioId = get_post_meta( $post_id, 'idHash_audioId', true );

        // Solo procesar si al menos uno de los metadatos está vacío
        if ( $rutaOriginal && $rutaLiteOriginal && $idHash_audioId ) {
            continue; // Todos los metadatos están presentes, omitir
        }

        // Obtener el ID de adjunto de post_audio
        $post_audio_id = get_post_meta( $post_id, 'post_audio', true );
        if ( !$post_audio_id ) {
            //guardarLog("Post ID $post_id: No se encontró 'post_audio'.");
        }

        // Obtener el ID de adjunto de post_audio_lite
        $post_audio_lite_id = get_post_meta( $post_id, 'post_audio_lite', true );
        if ( !$post_audio_lite_id ) {
            //guardarLog("Post ID $post_id: No se encontró 'post_audio_lite'.");
        }

        // Actualizar 'rutaOriginal' si falta
        if ( !$rutaOriginal && $post_audio_id ) {
            $adjunto = get_post( $post_audio_id );
            if ( $adjunto ) {
                $filename = basename( get_attached_file( $post_audio_id ) );
                $ruta_completa = buscar_archivo_recursivo( $directorio_base, $filename );
                if ( $ruta_completa ) {
                    update_post_meta( $post_id, 'rutaOriginal', $ruta_completa );
                } else {
                    //guardarLog("Post ID $post_id: No se encontró el archivo original '$filename'.");
                }
            } else {
                //guardarLog("Post ID $post_id: No se encontró el adjunto con ID $post_audio_id.");
            }
        }

        // Actualizar 'rutaLiteOriginal' si falta
        if ( !$rutaLiteOriginal && $post_audio_lite_id ) {
            $adjunto_lite = get_post( $post_audio_lite_id );
            if ( $adjunto_lite ) {
                $ruta_lite = get_attached_file( $post_audio_lite_id );
                if ( $ruta_lite ) {
                    update_post_meta( $post_id, 'rutaLiteOriginal', $ruta_lite );
                } else {
                    //guardarLog("Post ID $post_id: No se pudo obtener la ruta de 'post_audio_lite'.");
                }
            } else {
                //guardarLog("Post ID $post_id: No se encontró el adjunto lite con ID $post_audio_lite_id.");
            }
        }

        // Actualizar 'idHash_audioId' si falta
        if ( !$idHash_audioId && $post_audio_id ) {
            $adjunto_url = wp_get_attachment_url( $post_audio_id );
            if ( $adjunto_url ) {
                $file_id = obtenerFileIDPorURL( $adjunto_url ); 
                if ( $file_id ) {
                    update_post_meta( $post_id, 'idHash_audioId', $file_id );
                } else {
                    //guardarLog("Post ID $post_id: No se pudo obtener 'idHash_audioId' para la URL '$adjunto_url'.");
                }
            } else {
                //guardarLog("Post ID $post_id: No se pudo obtener la URL del adjunto con ID $post_audio_id.");
            }
        }
    }

    //guardarLog('Actualización de metadatos de posts social_post completada.');
}

ejecutar_actualizar_metas_posts_social_una_vez();

function ejecutar_actualizar_metas_posts_social_una_vez() {
    if ( get_option( 'actualizar_metas_posts_social_realizado' ) ) {
        return;
    }
    actualizar_metas_posts_social();
    update_option( 'actualizar_metas_posts_social_realizado', 1 );
}

*/