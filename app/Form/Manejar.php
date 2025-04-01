<?php // Asegúrate de que el tag de apertura de PHP esté presente y sea correcto

function crearPost($tipoPost = 'social_post', $estadoPost = 'publish')
{
    $contenido = isset($_POST['textoNormal']) ? sanitize_textarea_field($_POST['textoNormal']) : '';
    $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';

    if (empty($contenido)) {
        error_log('Error en crearPost: El contenido no puede estar vacio.');
        return new WP_Error('empty_content', 'El contenido no puede estar vacio.');
    }

    $titulo = wp_trim_words($contenido, 15, '...');
    $autor = get_current_user_id();

    // Insertar el post
    $postId = wp_insert_post([
        'post_title'   => $titulo,
        'post_content' => $contenido,
        'post_status'  => $estadoPost,
        'post_author'  => $autor,
        'post_type'    => $tipoPost,
    ]);

    if (is_wp_error($postId)) {
        // Reemplazar saltos de línea en el mensaje de error para loguear en una sola línea
        $error_message = str_replace("\n", " | ", $postId->get_error_message());
        error_log('Error en crearPost: Error al insertar el post. Detalles: ' . $error_message);
        return $postId;
    }

    // El bloque de actualización de 'tagsUsuario' ha sido eliminado de aquí.
    // La asignación de tags ahora es manejada por la función asignarTags en PostService.php

    /*
    // Código de notificaciones comentado - los logs aquí no parecen tener variables con saltos de línea
    $seguidores = get_user_meta($autor, 'seguidores', true);
    if (!empty($seguidores) && is_array($seguidores)) {
        $autor_nombre = esc_html(get_the_author_meta('display_name', $autor));
        $contenido_corto = mb_strimwidth($contenido, 0, 100, "...");
        $post_url = get_permalink($postId);

        $notificaciones = get_option('notificaciones_pendientes', []);
        $notificaciones_unicas = [];

        foreach ($seguidores as $seguidor_id) {
            if (get_user_by('id', $seguidor_id) === false) {
                error_log("Error en crearPost: Seguidor ID {$seguidor_id} no es un usuario valido.");
                continue;
            }

            $clave_notificacion = "{$seguidor_id}_{$postId}";

            if (!isset($notificaciones_unicas[$clave_notificacion])) {
                $notificaciones[] = [
                    'seguidor_id' => $seguidor_id,
                    'mensaje' => "{$autor_nombre} ha publicado: \"{$contenido_corto}\"", // Corregido el escape de comillas
                    'post_id' => $postId,
                    'titulo' => 'Nueva publicacion',
                    'url'  => $post_url,
                    'autor_id' => $autor
                ];
                $notificaciones_unicas[$clave_notificacion] = true;
            }
        }

        update_option('notificaciones_pendientes', $notificaciones);

        if (!wp_next_scheduled('wp_enqueue_notifications')) {
            wp_schedule_event(time(), 'minute', 'wp_enqueue_notifications');
        }
    } else {
        error_log("El usuario $autor no tiene seguidores o la lista de seguidores no es valida.");
    }
    */
    return $postId;
}

// Asumiendo que esta acción llama a una función que procesa las notificaciones
// add_action('wp_enqueue_notifications', 'procesar_notificaciones');


add_filter('cron_schedules', function ($schedules) {
    $schedules['minute'] = [
        'interval' => 15,
        'display'  => __('Cada minuto')
    ];
    return $schedules;
});


#Paso 2
function actualizarMetaDatos($postId)
{
    $meta_fields = [
        'paraColab'         => 'colab',
        'esExclusivo'       => 'exclusivo',
        'paraDescarga'      => 'descarga',
        'rola'              => 'music',
        'fan'               => 'fan',
        'artista'           => 'artista',
        'individual'        => 'individual',
        'multiple'          => 'multiple',
        'tienda'            => 'tienda',
        'momento'           => 'momento'
    ];

    foreach ($meta_fields as $meta_key => $post_key) {
        // Asegúrate que el índice existe antes de accederlo
        $value = (isset($_POST[$post_key]) && $_POST[$post_key] == '1') ? 1 : 0;
        if (update_post_meta($postId, $meta_key, $value) === false) {
            // Los logs aquí están comentados, no se requiere acción inmediata
            //error_log("Error en actualizarMetaDatos: Fallo al actualizar el meta $meta_key para el post ID $postId.");
        }
    }

    // Manejo de nombreLanzamiento
    if (isset($_POST['nombreLanzamiento'])) {
        $nombreLanzamiento = sanitize_text_field($_POST['nombreLanzamiento']);
        if (update_post_meta($postId, 'nombreLanzamiento', $nombreLanzamiento) === false) {
            // Log comentado
            //error_log("Error en actualizarMetaDatos: Fallo al actualizar el meta nombreLanzamiento para el post ID $postId.");
        }
    }

    if (isset($_POST['music']) && $_POST['music'] == '1') {
        registrarNombreRolas($postId);
    }
    if (isset($_POST['tienda']) && $_POST['tienda'] == '1') {
        registrarPrecios($postId);
    }
}

#Paso 2.1
function registrarNombreRolas($postId)
{
    for ($i = 1; $i <= 30; $i++) {
        $rola_key = 'nombreRola' . $i;
        if (isset($_POST[$rola_key])) {
            $nombre_rola = sanitize_text_field($_POST[$rola_key]);
            if (update_post_meta($postId, $rola_key, $nombre_rola) === false) {
                // Mensaje de log simple, sin variables complejas
                error_log("Error en registrarNombreRolas: Fallo al actualizar el meta $rola_key para el post ID $postId.");
            }
        }
    }
}

#Paso 2.2 (Renumerado para seguir la secuencia lógica)
function registrarPrecios($postId)
{
    for ($i = 1; $i <= 30; $i++) {
        $precio_key = 'precioRola' . $i;
        if (isset($_POST[$precio_key])) {
            $precio = sanitize_text_field($_POST[$precio_key]);

            if (is_numeric($precio)) {
                if (update_post_meta($postId, $precio_key, $precio) === false) {
                    // Mensaje de log simple
                    error_log("Error en registrarPrecios: Fallo al actualizar el meta $precio_key para el post ID $postId.");
                }
            } else {
                // Mensaje de log simple
                error_log("Error en registrarPrecios: El valor para $precio_key no es numerico. Post ID: $postId, valor ingresado: " . $precio);
            }
        }
    }
}

#Paso 3
function datosParaAlgoritmo($postId)
{
    $textoNormal = isset($_POST['textoNormal']) ? trim($_POST['textoNormal']) : '';
    // Decodificar entidades HTML podría ser necesario dependiendo de cómo se guardó el texto
    // $textoNormal = htmlspecialchars_decode($textoNormal, ENT_QUOTES); // Descomentar si es necesario
    $tags_string = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
    $tags = !empty($tags_string) ? array_map('trim', explode(',', $tags_string)) : [];

    $autorId = get_post_field('post_author', $postId);
    $autorData = get_userdata($autorId); // Obtener datos del autor de forma segura

    $nombreUsuario = $autorData ? $autorData->user_login : 'desconocido';
    $nombreMostrar = $autorData ? $autorData->display_name : 'Desconocido';

    $datosAlgoritmo = [
        'tags' => $tags,
        'texto' => $textoNormal, // Usar el texto sanitizado
        'autor' => [
            'id' => $autorId,
            'usuario' => $nombreUsuario,
            'nombre' => $nombreMostrar,
        ],
    ];

    // Usar wp_json_encode para manejo de errores de WordPress si aplica, o json_encode estándar
    $datosAlgoritmoJson = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE);

    if ($datosAlgoritmoJson === false) {
        // Reemplazar saltos de línea en el mensaje de error JSON si los hubiera (poco probable pero seguro)
        $json_error_message = str_replace("\n", " | ", json_last_error_msg());
        error_log("Error en datosParaAlgoritmo: Fallo al codificar JSON para el post ID: " . $postId . ". Error: " . $json_error_message);
    } else {
        if (update_post_meta($postId, 'datosAlgoritmo', $datosAlgoritmoJson) === false) {
            // Mensaje de log simple
            error_log("Error en datosParaAlgoritmo: Fallo al actualizar meta datosAlgoritmo para el post ID " . $postId);
        }
    }
}

#Paso 4
function confirmarArchivos($postId)
{
    $tiposCampos = ['archivoId', 'audioId', 'imagenId'];
    $maxCampos = 30;
    foreach ($tiposCampos as $tipo) {
        for ($i = 1; $i <= $maxCampos; $i++) {
            $campo = $tipo . $i;
            if (!empty($_POST[$campo])) {
                $file_id = intval($_POST[$campo]); // Asegura que es un entero
                if ($file_id > 0 && get_post_type($file_id) === 'attachment') { // Validar que es un adjunto válido
                    $meta_key = 'idHash_' . $campo;
                    if (update_post_meta($postId, $meta_key, $file_id) === false) {
                        // Mensaje de log simple
                        error_log("Error en confirmarArchivos: Fallo al actualizar meta {$meta_key} para el post ID: {$postId}");
                    }
                    // Asumiendo que confirmarHashId existe y hace algo con el ID del archivo
                    confirmarHashId($file_id);
                } elseif ($file_id <= 0) {
                     error_log("Error en confirmarArchivos: ID de archivo inválido recibido para el campo {$campo}. Valor: {$_POST[$campo]}");
                } else {
                     error_log("Error en confirmarArchivos: ID {$file_id} recibido para el campo {$campo} no es un adjunto válido.");
                }
            }
        }
    }
}

#Paso 5
function procesarURLs($postId)
{
    $tiposURLs = [
        // [callback, ¿renombrar?]
        'imagenUrl'  => ['procesarArchivo', false],
        'audioUrl'   => ['procesarArchivo', true],  // Solo los audios se renombran
        'archivoUrl' => ['procesarArchivo', false],
    ];
    $maxCampos = 30;

    foreach ($tiposURLs as $tipoBase => $callbackData) {
        $funcionCallback = $callbackData[0];
        $renombrar = $callbackData[1];

        for ($i = 1; $i <= $maxCampos; $i++) {
            $campo = $tipoBase . $i;
            if (!empty($_POST[$campo])) {
                $url = esc_url_raw(trim($_POST[$campo])); // Limpiar espacios y escapar URL

                // Validar URL de forma más robusta
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    // Llamar a la función callback con los parámetros correctos
                     call_user_func($funcionCallback, $postId, $campo, $renombrar, $i); // Pasar índice
                } else {
                    // Mensaje de log simple
                    error_log("Error en procesarURLs: URL invalida en el campo: {$campo} para postId: {$postId}. URL: {$url}");
                }
            }
        }
    }
}


#Paso 5.1 #Prepara para buscar la ID y actualizar la meta
function procesarArchivo($postId, $campo, $renombrar = false, $indice = null)
{
    if (empty($_POST[$campo])) return false; // Salir si no hay URL

    $url = esc_url_raw(trim($_POST[$campo]));
    $archivoId = obtenerArchivoId($url, $postId); // Intenta obtener el ID del adjunto

    if ($archivoId && !is_wp_error($archivoId)) {
        // Actualizar la meta correspondiente con el ID del archivo
        if (actualizarMetaConArchivo($postId, $campo, $archivoId, $indice) === false) {
            error_log("Error en procesarArchivo: Fallo al actualizar meta con archivo para Post ID: $postId, Campo: $campo, Archivo ID: $archivoId");
            return false; // Falló la actualización de la meta
        }

        // Renombrar si es necesario (generalmente para audioUrl)
        if ($renombrar && $indice !== null) {
            if (renombrarArchivoAdjunto($postId, $archivoId, $indice) === false) { // Pasar índice
                error_log("Error en procesarArchivo: Fallo al renombrar el archivo adjunto para Post ID: $postId, Campo: $campo, Archivo ID: $archivoId, Indice: $indice");
                // No necesariamente retornar false aquí, la meta ya se guardó, pero el renombrado falló.
            }
        }
        return true; // Éxito al procesar el archivo
    } else {
        // Manejar el caso de WP_Error o ID inválido
        $mensajeError = 'ID de archivo inválido o no encontrado.';
        if (is_wp_error($archivoId)) {
            $mensajeError = $archivoId->get_error_message();
        }
        // Reemplazar saltos de línea en el mensaje de error
        $log_message = str_replace("\n", " | ", $mensajeError);
        error_log("Error en procesarArchivo: No se pudo procesar el archivo para Post ID: $postId, Campo: $campo. URL: {$url}. Error: " . $log_message);
        return false; // Falló la obtención del ID del archivo
    }
}


#PASO 5.2
function renombrarArchivoAdjunto($postId, $archivoId, $indice) // Recibe índice directamente
{
    // Validar que $archivoId es un ID de adjunto válido
    if (!$archivoId || get_post_type($archivoId) !== 'attachment') {
        error_log("Error en renombrarArchivoAdjunto: ID de archivo inválido {$archivoId} para postId {$postId}.");
        return false;
    }

    // Obtener post y autor
    $post = get_post($postId);
    if (!$post) {
        error_log("Error en renombrarArchivoAdjunto: No se pudo obtener el post para postId: {$postId}.");
        return false;
    }
    $author = get_userdata($post->post_author);
    if (!$author) {
        error_log("Error en renombrarArchivoAdjunto: No se pudo obtener el autor para postId: {$postId}.");
        return false;
    }

    // Obtener ruta del archivo
    $file_path = get_attached_file($archivoId);
    if (!$file_path || !file_exists($file_path)) {
        error_log("Error en renombrarArchivoAdjunto: El archivo adjunto no existe para archivoId: {$archivoId}. Ruta esperada: {$file_path}");
        return false;
    }

    $info = pathinfo($file_path);
    $random_id = wp_rand(10000, 99999); // Usar wp_rand para mejor aleatoriedad en WP
    $autor_login_sanitized = sanitize_file_name(mb_substr($author->user_login, 0, 20));
    // Usar el título del post en lugar del contenido para el nombre, es más predecible y corto
    $post_title_sanitized = sanitize_file_name(wp_trim_words($post->post_title, 5, '')); // 5 palabras del título
    $post_title_sanitized = mb_substr($post_title_sanitized, 0, 40); // Limitar longitud

    // Construir nuevo nombre de archivo
    $new_filename = sprintf(
        '2upra_%s_%s_%d_%d.%s',
        $autor_login_sanitized,
        $post_title_sanitized ?: "post{$postId}", // Fallback si el título está vacío
        $indice, // Incluir índice en el nombre
        $random_id,
        strtolower($info['extension']) // Usar extensión en minúsculas
    );
    $new_file_path = $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename;

    // Intentar renombrar
    if (rename($file_path, $new_file_path)) {
        // Actualizar la ruta del archivo en la base de datos de WordPress
        $update_path_result = update_attached_file($archivoId, $new_file_path);
        if (!$update_path_result) {
             error_log("Error en renombrarArchivoAdjunto: El archivo se renombró en el sistema ({$new_file_path}) pero falló al actualizar la ruta en WP para archivoId: {$archivoId}.");
             // Podría intentar revertir el rename aquí, pero es complejo.
             return false;
        }

        // Obtener la nueva URL pública (esto debería funcionar después de update_attached_file)
        $public_url = wp_get_attachment_url($archivoId);
        if (!$public_url) {
            error_log("Error en renombrarArchivoAdjunto: No se pudo obtener la nueva URL pública para archivoId: {$archivoId} después de renombrar.");
            // El archivo se renombró, pero la URL podría estar mal.
        }

        // Actualizar URL en algún otro lugar si es necesario (Función actualizarUrlArchivo)
        // ¿De dónde viene idHash aquí? Se necesita obtenerlo correctamente.
        // Asumiendo que idHash se relaciona con el archivo original subido antes de confirmar.
        // Necesitamos una forma de vincular el $archivoId procesado con su 'idHash' original si es necesario.
        // Por ahora, comentaremos esta parte ya que 'idHash' no está definido aquí.
        /*
        $idHashCampo = "idHash_audioId{$indice}"; // Construir la meta key esperada
        $idHash = get_post_meta($postId, $idHashCampo, true);
        if (!empty($idHash) && $public_url) {
            actualizarUrlArchivo($idHash, $public_url);
        } else {
             error_log("Advertencia en renombrarArchivoAdjunto: No se encontró idHash ({$idHashCampo}) o URL pública para actualizar URL externa. PostID: {$postId}, ArchivoID: {$archivoId}");
        }
        */

        // Actualizar metadatos adicionales
        update_post_meta($postId, 'sample', true); // ¿Qué significa 'sample'? Asegúrate que esto es correcto.
        procesarAudioLigero($postId, $archivoId, $indice); // Procesar versión ligera

        return true; // Éxito

    } else {
        error_log("Error en renombrarArchivoAdjunto: No se pudo renombrar el archivo adjunto de {$file_path} a {$new_file_path}. Verificar permisos.");
        return false;
    }
}


#Paso 5.3 #Busca la Id de Adjunto segun la URL
function obtenerArchivoId($url, $postId)
{
    // 1. Intentar obtener ID directamente desde la URL (más eficiente)
    $archivoId = attachment_url_to_postid($url);

    if ($archivoId && get_post_type($archivoId) === 'attachment') {
        return $archivoId; // Encontrado y es un adjunto válido
    }

    // 2. Si no se encontró o no es válido, intentar descargar y agregar (sideload)
    // Esto es más pesado y propenso a errores (permisos, timeouts, etc.)
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Intentar descargar el archivo desde la URL
    $tmp_file = download_url($url, 15); // Timeout de 15 segundos

    if (is_wp_error($tmp_file)) {
        $error_message = str_replace("\n", " | ", $tmp_file->get_error_message());
        error_log("Error en obtenerArchivoId (download_url): No se pudo descargar desde {$url}. Error: " . $error_message);
        return false; // Retorna false en lugar de WP_Error para consistencia
    }

    // Preparar información del archivo para media_handle_sideload
    $file_info = [
        'name'     => basename($url), // Usar nombre de archivo de la URL
        'tmp_name' => $tmp_file
    ];

    // Agregar el archivo a la biblioteca de medios asociado al post
    $archivoId = media_handle_sideload($file_info, $postId);

    // Limpiar archivo temporal si existe
    if (file_exists($tmp_file)) {
        @unlink($tmp_file);
    }

    if (is_wp_error($archivoId)) {
        $error_message = str_replace("\n", " | ", $archivoId->get_error_message());
        error_log("Error en obtenerArchivoId (media_handle_sideload): No se pudo crear el adjunto desde la URL {$url}. Error: " . $error_message);
        return false; // Retorna false en lugar de WP_Error
    }

    // Éxito al agregar el archivo
    return $archivoId;
}


#Paso 5.4
function actualizarMetaConArchivo($postId, $campo, $archivoId, $indice = null) // Recibe índice
{
    // Mapeo base de campos a meta keys base
    $meta_mapping = [
        'imagenUrl'   => 'imagenID',    // Mapea imagenUrl1 a imagenID1, imagenUrl2 a imagenID2, etc.
        'audioUrl'    => 'post_audio',  // Mapea audioUrl1 a post_audio1, audioUrl2 a post_audio2, etc.
        'archivoUrl'  => 'archivoID'    // Mapea archivoUrl1 a archivoID1, etc.
    ];

    // Extraer la base del campo (e.g., 'imagenUrl')
    preg_match('/^(?<base>imagenUrl|audioUrl|archivoUrl)/', $campo, $matches_base);
    $baseField = $matches_base['base'] ?? null;

    if ($baseField && isset($meta_mapping[$baseField]) && $indice !== null) {
        // Construir la meta key final usando el índice
        $baseMetaKey = $meta_mapping[$baseField];
        // Para el índice 1, algunos campos podrían no tener sufijo (depende de tu lógica original)
        // Ajusta esto según necesites. Este código SIEMPRE añade el índice si es > 0.
        $meta_key = $baseMetaKey . $indice;

        // Actualizar la meta específica
        if (update_post_meta($postId, $meta_key, $archivoId) === false) {
            error_log("Error en actualizarMetaConArchivo: Fallo al actualizar meta {$meta_key} con archivo ID {$archivoId} para el post ID {$postId}.");
            return false;
        }

        // Caso especial: Establecer la imagen destacada si es la primera imagen (imagenUrl1)
        if ($baseField === 'imagenUrl' && $indice == 1) {
            set_post_thumbnail($postId, $archivoId);
        }

        return true; // Meta actualizada correctamente

    } else {
        // Si no coincide con el mapeo o no hay índice, loguear un aviso/error y no hacer nada
        // o manejar como un caso genérico si es necesario.
        error_log("Advertencia/Error en actualizarMetaConArchivo: No se pudo mapear el campo '{$campo}' o falta el índice para actualizar la meta en el post ID {$postId}.");
        // Opcionalmente, actualizar la meta con el nombre del campo original si ese es el comportamiento deseado
        // if (update_post_meta($postId, $campo, $archivoId) === false) { ... }
        return false; // Indicar que no se pudo mapear/actualizar correctamente
    }
}


#Paso 5.5 (Renumerado)
function procesarAudioLigero($post_id, $audio_id, $index)
{
    guardarLog("INICIO procesarAudioLigero para Post ID: $post_id, Audio ID: $audio_id, Index: $index");

    // Validar IDs
    if (!$post_id || !$audio_id || get_post_type($audio_id) !== 'attachment') {
        guardarLog("Error: IDs inválidos en procesarAudioLigero. PostID: $post_id, AudioID: $audio_id");
        return;
    }

    $audio_path = get_attached_file($audio_id);
    if (!$audio_path || !file_exists($audio_path)) {
        guardarLog("Error: No se encontró el archivo de audio original en {$audio_path} para Audio ID: {$audio_id}");
        return;
    }
    guardarLog("Ruta del archivo de audio original: {$audio_path}");

    $path_parts = pathinfo($audio_path);
    $output_dir = $path_parts['dirname'];
    // Usar el nombre base del archivo original para las versiones ligeras
    $original_filename_base = $path_parts['filename'];

    // --- Eliminar metadatos del archivo original ---
    $tmp_output_path = $output_dir . '/' . $original_filename_base . '_temp_stripped.mp3';
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c copy " . escapeshellarg($tmp_output_path);
    guardarLog("Ejecutando comando para eliminar metadatos: {$comando_strip_metadata}");
    exec($comando_strip_metadata . " 2>&1", $output_strip, $return_strip); // Capturar stderr también

    if ($return_strip !== 0) {
        // Unir la salida con " | " para loguear en una línea
        $log_output = implode(" | ", $output_strip);
        guardarLog("Error al eliminar metadatos del archivo original ({$return_strip}): " . $log_output);
        // Continuar de todos modos, pero loguear el error
    } else {
        // Reemplazar el original con la versión sin metadatos
        if (rename($tmp_output_path, $audio_path)) {
            guardarLog("Metadatos del archivo original eliminados y archivo reemplazado.");
        } else {
            guardarLog("Error al reemplazar el archivo original con la versión sin metadatos.");
            @unlink($tmp_output_path); // Limpiar archivo temporal si falla el rename
        }
    }

    // --- Obtener información del autor ---
    $post_author_id = get_post_field('post_author', $post_id);
    $author_info = get_userdata($post_author_id);
    $author_username = $author_info ? $author_info->user_login : "Desconocido";
    $page_name = "2upra.com"; // O obtener de una opción de WP
    guardarLog("Autor: {$author_username}, Sitio: {$page_name}");

    // --- Procesar archivo de audio ligero (128 kbps) ---
    $nuevo_archivo_path_lite = $output_dir . '/' . $original_filename_base . '_128k.mp3';
    // Crear metadatos correctamente escapados
    $metadata_args = sprintf(
        '-metadata artist=%s -metadata comment=%s',
        escapeshellarg($author_username),
        escapeshellarg($page_name)
    );
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k {$metadata_args} " . escapeshellarg($nuevo_archivo_path_lite);
    guardarLog("Ejecutando comando para crear audio ligero: {$comando_lite}");
    exec($comando_lite . " 2>&1", $output_lite, $return_var_lite); // Capturar stderr

    if ($return_var_lite !== 0) {
        // Unir la salida con " | "
        $log_output = implode(" | ", $output_lite);
        guardarLog("Error al procesar audio ligero ({$return_var_lite}): " . $log_output);
        return; // No continuar si falla la creación del archivo ligero
    } else {
        guardarLog("Audio ligero creado exitosamente en: {$nuevo_archivo_path_lite}");
    }

    // --- Insertar archivo ligero en la biblioteca de medios ---
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $filetype_lite = wp_check_filetype(basename($nuevo_archivo_path_lite), null);
    $attachment_lite = array(
        'guid'           => $nuevo_archivo_path_lite, // Usar la ruta como GUID inicial (WP lo ajustará)
        'post_mime_type' => $filetype_lite['type'],
        'post_title'     => $original_filename_base . '_128k', // Título descriptivo
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Insertar el adjunto asociado al post original
    $attach_id_lite = wp_insert_attachment($attachment_lite, $nuevo_archivo_path_lite, $post_id);

    if (is_wp_error($attach_id_lite)) {
        $error_message = str_replace("\n", " | ", $attach_id_lite->get_error_message());
        guardarLog("Error al insertar el adjunto ligero: " . $error_message);
        @unlink($nuevo_archivo_path_lite); // Limpiar archivo si falla la inserción
        return;
    }
    guardarLog("ID de adjunto ligero insertado: {$attach_id_lite}");

    // Generar metadatos del adjunto (importante para que WP lo reconozca correctamente)
    $attach_data_lite = wp_generate_attachment_metadata($attach_id_lite, $nuevo_archivo_path_lite);
    if (is_wp_error($attach_data_lite)) {
         $error_message = str_replace("\n", " | ", $attach_data_lite->get_error_message());
         guardarLog("Error al generar metadata para adjunto ligero {$attach_id_lite}: " . $error_message);
         // Continuar, pero el archivo puede no funcionar correctamente en WP
    } else {
        wp_update_attachment_metadata($attach_id_lite, $attach_data_lite);
        guardarLog("Metadatos generados para adjunto ligero {$attach_id_lite}.");
    }


    // --- Actualizar la meta del post con el ID del archivo ligero ---
    // Ajustar la clave de la meta según el índice
    $meta_key = ($index == 1) ? "post_audio_lite" : "post_audio_lite_{$index}";
    if (update_post_meta($post_id, $meta_key, $attach_id_lite)) {
        guardarLog("Meta '{$meta_key}' actualizada en Post ID {$post_id} con Attach ID {$attach_id_lite}");
    } else {
        guardarLog("Error al actualizar la meta '{$meta_key}' en Post ID {$post_id}");
    }


    // --- Extraer y guardar la duración del audio ---
    $duration_command = "/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($nuevo_archivo_path_lite);
    guardarLog("Ejecutando comando para obtener duración: {$duration_command}");
    $duration_in_seconds = shell_exec($duration_command);
    guardarLog("Salida de ffprobe (duración): '{$duration_in_seconds}'");

    $duration_in_seconds = trim($duration_in_seconds);
    if (is_numeric($duration_in_seconds) && $duration_in_seconds > 0) {
        $duration_in_seconds_float = (float)$duration_in_seconds;
        $minutes = floor($duration_in_seconds_float / 60);
        $seconds = floor($duration_in_seconds_float % 60);
        $duration_formatted = $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);

        $duration_meta_key = "audio_duration_{$index}";
        if (update_post_meta($post_id, $duration_meta_key, $duration_formatted)) {
            guardarLog("Duración del audio ({$duration_formatted}) guardada en meta '{$duration_meta_key}' para Post ID {$post_id}");
        } else {
            guardarLog("Error al guardar duración del audio en meta '{$duration_meta_key}' para Post ID {$post_id}");
        }
    } else {
        guardarLog("Duración del audio no válida o cero para el archivo {$nuevo_archivo_path_lite}. Salida: {$duration_in_seconds}");
    }

    guardarLog("Llamando a analizarYGuardarMetasAudio para Post ID: {$post_id}, Path Lite: {$nuevo_archivo_path_lite}, Index: {$index}");
    // Llamar a la función de análisis de IA (anteriormente solo si index === 1, ahora siempre según tu código)
    // Pasar la ruta del archivo ligero que acabamos de crear y verificar
    if (file_exists($nuevo_archivo_path_lite)) {
        analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index);
    } else {
         guardarLog("Error: El archivo ligero {$nuevo_archivo_path_lite} no existe antes de llamar a analizarYGuardarMetasAudio.");
    }

    guardarLog("FIN procesarAudioLigero para Post ID: $post_id, Audio ID: $audio_id, Index: $index");
}


#Paso 5.6
function analizarYGuardarMetasAudio($post_id, $audio_path_lite, $index, $nombre_archivo = null, $carpeta = null, $carpeta_abuela = null)
{
    iaLog("INICIO analizarYGuardarMetasAudio para Post ID: {$post_id}, Index: {$index}, Path: {$audio_path_lite}");

    // Validar existencia del archivo antes de ejecutar Python
    if (!file_exists($audio_path_lite)) {
        iaLog("Error: El archivo de audio '{$audio_path_lite}' no existe.");
        return;
    }

    // --- Ejecutar Script Python ---
    $python_script_path = '/var/www/wordpress/wp-content/themes/2upra3v/app/python/audio.py'; // Definir ruta claramente
    if (!file_exists($python_script_path)) {
        iaLog("Error: El script de Python '{$python_script_path}' no existe.");
        return;
    }

    $python_command = sprintf(
        "python3 %s %s",
        escapeshellcmd($python_script_path), // Escapar ruta del script
        escapeshellarg($audio_path_lite)     // Escapar argumento de ruta de audio
    );
    iaLog("Ejecutando comando de Python: {$python_command}");
    exec($python_command . " 2>&1", $output, $return_var); // Capturar stderr también

    if ($return_var !== 0) {
        // Unir salida de Python con " | " para log
        $log_output = implode(" | ", $output);
        iaLog("Error al ejecutar el script de Python. Codigo de retorno: {$return_var}. Salida: " . $log_output);
        return; // Salir si el script falla
    }
    iaLog("Script de Python ejecutado exitosamente. Salida: " . implode(" | ", $output));


    // --- Procesar Resultados del Script ---
    $resultados_path = $audio_path_lite . '_resultados.json'; // Asumiendo que el script crea este archivo
    iaLog("Buscando archivo de resultados en: {$resultados_path}");

    if (file_exists($resultados_path)) {
        $resultados_json = file_get_contents($resultados_path);
        $resultados = json_decode($resultados_json, true);

        if ($resultados && is_array($resultados)) {
            iaLog("Resultados JSON decodificados: " . print_r($resultados, true)); // Loguear para depuración
            $suffix = ($index == 1) ? '' : "_{$index}"; // Sufijo para metas si index > 1

            // Guardar metas individuales del análisis de audio
            update_post_meta($post_id, "audio_bpm{$suffix}", sanitize_text_field($resultados['bpm'] ?? ''));
            update_post_meta($post_id, "audio_pitch{$suffix}", sanitize_text_field($resultados['pitch'] ?? ''));
            update_post_meta($post_id, "audio_emotion{$suffix}", sanitize_text_field($resultados['emotion'] ?? ''));
            update_post_meta($post_id, "audio_key{$suffix}", sanitize_text_field($resultados['key'] ?? ''));
            update_post_meta($post_id, "audio_scale{$suffix}", sanitize_text_field($resultados['scale'] ?? ''));
            update_post_meta($post_id, "audio_strength{$suffix}", sanitize_text_field($resultados['strength'] ?? '')); // ¿Qué es strength?

            iaLog("Metas de análisis de audio (BPM, pitch, etc.) guardadas con sufijo '{$suffix}'.");

        } else {
            iaLog("Error: El archivo de resultados JSON '{$resultados_path}' está vacío o no contiene JSON válido.");
            $resultados = []; // Asegurar que $resultados sea un array vacío para evitar errores posteriores
        }
         // Opcional: eliminar el archivo JSON después de procesarlo
         // @unlink($resultados_path);

    } else {
        iaLog("Advertencia: No se encontró el archivo de resultados '{$resultados_path}'. No se guardarán metas de análisis de audio.");
        $resultados = []; // Asegurar que $resultados sea un array vacío
    }

    // --- Generar Descripción con IA ---
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        iaLog("Advertencia: No se pudo obtener el contenido del post ID: {$post_id} para generar el prompt.");
        // Continuar de todos modos, pero el prompt será menos informativo
    }

    // Obtener tags de usuario
    $tags_usuario = get_post_meta($post_id, 'tagsUsuario', true);
    $tags_usuario_texto = $tags_usuario ? implode(', ', (array)$tags_usuario) : ''; // Asegurar que sea array y luego string

    // Construir información adicional del archivo si está disponible
    $informacion_archivo = '';
    if ($nombre_archivo) {
        $informacion_archivo .= "Nombre Archivo Original: '" . sanitize_text_field($nombre_archivo) . "'\n"; // Sanitizar
    }
    if ($carpeta) {
        $informacion_archivo .= "Carpeta Original: '" . sanitize_text_field($carpeta) . "'\n"; // Sanitizar
    }
    if ($carpeta_abuela) {
        $informacion_archivo .= "Carpeta Padre Original: '" . sanitize_text_field($carpeta_abuela) . "'\n"; // Sanitizar
    }
    // Limpiar la ruta irrelevante si existe en la información
    $informacion_archivo = str_replace('/home/asley01/MEGA/Waw/Kits', '', $informacion_archivo);


    // Construir el Prompt para la IA
    $prompt = "Analiza la siguiente información sobre un archivo de audio y genera una descripción detallada en formato JSON.\n";
    $prompt .= "Descripción del usuario: \"{$post_content}\"\n";
    $prompt .= "Tags del usuario: {$tags_usuario_texto}\n";
    if ($informacion_archivo) {
        $prompt .= "Información adicional del archivo:\n{$informacion_archivo}\n";
    }
    // Añadir datos de análisis si existen
    if (!empty($resultados)) {
        $prompt .= "Datos de análisis técnico:\n";
        $prompt .= "BPM: " . ($resultados['bpm'] ?? 'N/A') . "\n";
        $prompt .= "Tono (Pitch): " . ($resultados['pitch'] ?? 'N/A') . "\n";
        $prompt .= "Emoción: " . ($resultados['emotion'] ?? 'N/A') . "\n";
        $prompt .= "Tonalidad (Key): " . ($resultados['key'] ?? 'N/A') . "\n";
        $prompt .= "Escala: " . ($resultados['scale'] ?? 'N/A') . "\n";
        // $prompt .= "Fuerza (Strength): " . ($resultados['strength'] ?? 'N/A') . "\n"; // Incluir si es relevante
    }

    $prompt .= "\nFormato JSON requerido (rellena con tu análisis, estos son solo ejemplos de estructura):\n"
        . '{"descripcion_ia":{"es":"(descripción detallada en español)", "en":"(detailed description in English)"},'
        . '"instrumentos_posibles":{"es":["Instrumento1", "Instrumento2"], "en":["Instrument1", "Instrument2"]},'
        . '"nombre_corto":{"es":"(nombre conciso max 3 palabras)", "en":"(concise name max 3 words)"},'
        . '"descripcion_corta":{"es":"(resumen 4-6 palabras)", "en":"(summary 4-6 words)"},'
        . '"estado_animo":{"es":["Estado1", "Estado2"], "en":["Mood1", "Mood2"]},'
        . '"genero_posible":{"es":["Genero1", "Genero2"], "en":["Genre1", "Genre2"]},'
        . '"artista_posible":{"es":["ArtistaSimilar1"], "en":["SimilarArtist1"]},'
        . '"tipo_audio":{"es":"(sample/loop/one shot)", "en":"(sample/loop/one shot)"},'
        . '"tags_posibles":{"es":["tag1", "tag2", "tag_especifico"], "en":["tag1", "tag2", "specific_tag"]},'
        . '"sugerencia_busqueda":{"es":["busqueda seo español"], "en":["seo search english"]}}' . "\n";

    $prompt .= "\nGuía de Tags (usa solo los relevantes y sé específico):\n"
        . "Tipo/Formato: Acoustic, Chord, Down Sweep/Fall, Dry, Harmony, Loop, Melody, Mixed, Monophonic, One Shot, Polyphonic, Processed, Progression, Riser/Sweep, Short, Wet.\n"
        . "Timbre/Tono: Bassy, Boomy, Breathy, Bright, Buzzy, Clean, Coarse/Harsh, Cold, Dark, Delicate, Detuned, Dissonant, Distorted, Exotic, Fat, Full, Glitchy, Granular, Gloomy, Hard, High, Hollow, Low, Metallic, Muffled, Muted, Narrow, Noisy, Round, Sharp, Shimmering, Sizzling, Smooth, Soft, Piercing, Thin, Tinny, Warm, Wide, Wooden.\n"
        . "Género: Ambient, Breaks, Chillout, Chiptune, Cinematic, Classical, Acid House, Deep House, Disco, Drum & Bass, Dubstep, Ethnic/World, Electro House, Electro, Electro Swing, Folk/Country, Funk/Soul, Jazz, Jungle, House, Hip Hop, Latin/Afro Cuban, Minimal House, Nu Disco, R&B, Reggae/Dub, Reggaeton, Rock, Pop, Progressive House, Synthwave, Tech House, Techno, Trance, Trap, Vocals, Phonk, Memphis.\n"
        . "Estilo/Técnica: Arpeggiated, Decaying, Echoing, Long Release, Legato, Glissando/Glide, Pad, Percussive, Pitch Bend, Plucked, Pulsating, Punchy, Randomized, Slow Attack, Sweep/Filter Mod, Staccato/Stabs, Stuttered/Gated, Straight, Sustained, Syncopated, Uptempo, Wobble, Vibrato.\n"
        . "Calidad/Tecnología: Analog, Compressed, Digital, Dynamic, Loud, Range, Female, Funky, Jazzy, Lo Fi, Male, Quiet, Vintage, Vinyl.\n"
        . "Estado de Ánimo: Aggressive, Angry, Bouncy, Calming, Carefree, Cheerful, Climactic, Cool, Dramatic, Elegant, Epic, Excited, Energetic, Fun, Futuristic, Gentle, Groovy, Happy, Haunting, Hypnotic, Industrial, Manic, Melancholic, Mellow, Mystical, Nervous, Passionate, Peaceful, Playful, Powerful, Rebellious, Reflective, Relaxing, Romantic, Rowdy, Sad, Sentimental, Sexy, Soothing, Sophisticated, Spacey, Suspenseful, Uplifting, Urgent, Weird.\n";

    $prompt .= "\nInstrucciones adicionales: Determina claramente si es un loop, one shot o sample. Usa tags de una palabra. Optimiza SEO con sugerencias de búsqueda. Sé detallado y preciso. Mantén términos técnicos comunes (kick, snare) en inglés si aplica. Ignora rutas de sistema en la información del archivo al generar la descripción.";

    iaLog("Prompt generado para IA (longitud: " . strlen($prompt) . " caracteres). Llamando a generarDescripcionIA...");

    // Llamar a la función que interactúa con la IA
    $descripcion_json_string = generarDescripcionIA($audio_path_lite, $prompt);

    $nuevos_datos_ia = []; // Inicializar array para datos de IA

    if ($descripcion_json_string) {
        iaLog("Respuesta recibida de IA (primeros 500 chars): " . substr($descripcion_json_string, 0, 500));
        // Limpiar posible formato markdown de JSON
        $descripcion_json_string = preg_replace('/^```json\s*|\s*```$/', '', trim($descripcion_json_string));

        $descripcion_procesada = json_decode($descripcion_json_string, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($descripcion_procesada)) {
            iaLog("JSON de IA decodificado correctamente.");

            // --- Validar y Estructurar Datos de IA ---
            // Definir la estructura esperada y sanitizar/validar cada parte
            $nuevos_datos_ia = [
                'descripcion_ia' => [
                    'es' => sanitize_textarea_field($descripcion_procesada['descripcion_ia']['es'] ?? ''),
                    'en' => sanitize_textarea_field($descripcion_procesada['descripcion_ia']['en'] ?? '')
                ],
                 'instrumentos_posibles' => [ // Corregido nombre de clave
                    'es' => array_map('sanitize_text_field', (array)($descripcion_procesada['instrumentos_posibles']['es'] ?? [])),
                    'en' => array_map('sanitize_text_field', (array)($descripcion_procesada['instrumentos_posibles']['en'] ?? []))
                ],
                 'nombre_corto' => [
                     'es' => sanitize_text_field($descripcion_procesada['nombre_corto']['es'] ?? ''),
                     'en' => sanitize_text_field($descripcion_procesada['nombre_corto']['en'] ?? '')
                 ],
                 'descripcion_corta' => [
                     'es' => sanitize_text_field($descripcion_procesada['descripcion_corta']['es'] ?? ''),
                     'en' => sanitize_text_field($descripcion_procesada['descripcion_corta']['en'] ?? '')
                 ],
                'estado_animo' => [
                    'es' => array_map('sanitize_text_field', (array)($descripcion_procesada['estado_animo']['es'] ?? [])),
                    'en' => array_map('sanitize_text_field', (array)($descripcion_procesada['estado_animo']['en'] ?? []))
                ],
                'artista_posible' => [
                    'es' => array_map('sanitize_text_field', (array)($descripcion_procesada['artista_posible']['es'] ?? [])),
                    'en' => array_map('sanitize_text_field', (array)($descripcion_procesada['artista_posible']['en'] ?? []))
                ],
                'genero_posible' => [
                    'es' => array_map('sanitize_text_field', (array)($descripcion_procesada['genero_posible']['es'] ?? [])),
                    'en' => array_map('sanitize_text_field', (array)($descripcion_procesada['genero_posible']['en'] ?? []))
                ],
                'tipo_audio' => [
                    'es' => sanitize_text_field($descripcion_procesada['tipo_audio']['es'] ?? ''),
                    'en' => sanitize_text_field($descripcion_procesada['tipo_audio']['en'] ?? '')
                ],
                'tags_posibles' => [
                    'es' => array_map('sanitize_text_field', (array)($descripcion_procesada['tags_posibles']['es'] ?? [])),
                    'en' => array_map('sanitize_text_field', (array)($descripcion_procesada['tags_posibles']['en'] ?? []))
                ],
                'sugerencia_busqueda' => [
                    'es' => array_map('sanitize_text_field', (array)($descripcion_procesada['sugerencia_busqueda']['es'] ?? [])),
                    'en' => array_map('sanitize_text_field', (array)($descripcion_procesada['sugerencia_busqueda']['en'] ?? []))
                ]
            ];

            // Guardar la descripción IA completa como meta separada (con sufijo si aplica)
            $suffix = ($index == 1) ? '' : "_{$index}";
            if (update_post_meta($post_id, "audio_descripcion{$suffix}", wp_json_encode($nuevos_datos_ia, JSON_UNESCAPED_UNICODE))) {
                 iaLog("Meta 'audio_descripcion{$suffix}' guardada para el post ID: {$post_id}");
            } else {
                 iaLog("Error al guardar la meta 'audio_descripcion{$suffix}' para el post ID: {$post_id}");
            }

        } else {
            iaLog("Error al decodificar el JSON de la IA. Error: " . json_last_error_msg() . ". Respuesta recibida: " . $descripcion_json_string);
        }
    } else {
        iaLog("No se recibió respuesta o la respuesta estuvo vacía de la función generarDescripcionIA.");
    }


    // --- Actualizar 'datosAlgoritmo' ---
    // Obtener los datos existentes de forma segura
    $datos_algoritmo_json = get_post_meta($post_id, 'datosAlgoritmo', true);
    $datos_algoritmo = json_decode($datos_algoritmo_json, true);
    if (!is_array($datos_algoritmo)) {
        $datos_algoritmo = []; // Inicializar si no existe o no es un array válido
    }
    iaLog("Datos Algoritmo existentes: " . print_r($datos_algoritmo, true));


    // Preparar datos técnicos para mergear (asegurarse que vienen de $resultados)
    $nuevos_datos_tecnicos = [
        'bpm' => sanitize_text_field($resultados['bpm'] ?? ''),
        'pitch' => sanitize_text_field($resultados['pitch'] ?? ''), // Agregar si es necesario
        'emotion' => sanitize_text_field($resultados['emotion'] ?? ''),
        'key' => sanitize_text_field($resultados['key'] ?? ''),
        'scale' => sanitize_text_field($resultados['scale'] ?? ''),
        // 'strength' => sanitize_text_field($resultados['strength'] ?? ''), // Agregar si es necesario
    ];
    // Filtrar valores vacíos de datos técnicos si se prefiere
    $nuevos_datos_tecnicos = array_filter($nuevos_datos_tecnicos, function($value) { return $value !== ''; });

    iaLog("Nuevos datos técnicos a mergear: " . print_r($nuevos_datos_tecnicos, true));
    iaLog("Nuevos datos IA a mergear: " . print_r($nuevos_datos_ia, true));


    // Combinar datos existentes con los nuevos datos técnicos y los nuevos datos de IA
    // Los nuevos datos sobrescribirán los existentes si las claves coinciden
    $datos_algoritmo = array_merge($datos_algoritmo, $nuevos_datos_tecnicos, $nuevos_datos_ia);

    iaLog("Datos Algoritmo combinados antes de guardar: " . print_r($datos_algoritmo, true));

    // Guardar los datos combinados (como JSON)
    if (update_post_meta($post_id, 'datosAlgoritmo', wp_json_encode($datos_algoritmo, JSON_UNESCAPED_UNICODE))) {
        iaLog("Meta 'datosAlgoritmo' actualizada exitosamente para el post ID: {$post_id}");
    } else {
         iaLog("Error al actualizar la meta 'datosAlgoritmo' para el post ID: {$post_id}");
    }

    // Actualizar la bandera de IA (indica que el análisis se completó para este post)
    update_post_meta($post_id, 'flashIA', true);
    iaLog("Meta 'flashIA' establecida a true para el post ID: {$post_id}");

    iaLog("FIN analizarYGuardarMetasAudio para Post ID: {$post_id}, Index: {$index}");
}


?>
