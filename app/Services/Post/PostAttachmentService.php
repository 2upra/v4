<?php

// Refactor(Org): Funcion confirmarArchivos movida desde app/Form/Manejar.php
function confirmarArchivos($postId)
{
    $tiposCampos = ['archivoId', 'audioId', 'imagenId'];
    $maxCampos = 30;
    foreach ($tiposCampos as $tipo) {
        for ($i = 1; $i <= $maxCampos; $i++) {
            $campo = $tipo . $i;
            if (!empty($_POST[$campo])) {
                $file_id = intval($_POST[$campo]);
                if ($file_id > 0 && get_post_type($file_id) === 'attachment') {
                    $meta_key = 'idHash_' . $campo;
                    if (update_post_meta($postId, $meta_key, $file_id) === false) {
                        error_log("Error en confirmarArchivos: Fallo al actualizar meta {$meta_key} para el post ID: {$postId}");
                    }
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

// Refactor(Org): Función procesarURLs() movida desde app/Services/PostService.php
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

// Refactor(Org): Funciones de manejo de adjuntos y audio movidas desde app/Form/Manejar.php


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
            // Refactor(Org): Llamada a función renombrarArchivoAdjunto movida a app/Utils/FileUtils.php
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


// Refactor(Org): Función renombrarArchivoAdjunto movida a app/Utils/FileUtils.php


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

// Refactor(Move): Función procesarAudioLigero movida a app/Services/AudioProcessingService.php

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


// Refactor(Org): Mover función cambiar_imagen_post_handler y hook AJAX desde estado.php
add_action('wp_ajax_cambiar_imagen_post', 'cambiar_imagen_post_handler'); // Acción AJAX autenticada

function cambiar_imagen_post_handler() {
    if (empty($_POST['post_id']) || empty($_FILES['imagen'])) {
        wp_send_json_error(['message' => 'Faltan datos necesarios.']);
    }

    $post_id = intval($_POST['post_id']);

    // Verificar que el post existe
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(['message' => 'El post no existe.']);
    }

    // Verificar que el usuario actual sea el autor del post
    if ((int) $post->post_author !== get_current_user_id()) {
        wp_send_json_error(['message' => 'No tienes permisos para cambiar la imagen de este post.']);
    }

    // Procesar la imagen subida
    $file = $_FILES['imagen'];

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $upload = wp_handle_upload($file, ['test_form' => false]);

    if (isset($upload['error']) || !isset($upload['file'])) {
        wp_send_json_error(['message' => 'Error al subir la imagen: ' . $upload['error']]);
    }

    $file_path = $upload['file'];
    $file_url = $upload['url']; // URL de la imagen subida

    // Crear un attachment en la biblioteca de medios
    $attachment_id = wp_insert_attachment([
        'guid'           => $file_url,
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name($file['name']),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $file_path, $post_id);

    if (is_wp_error($attachment_id) || !$attachment_id) {
        wp_send_json_error(['message' => 'Error al guardar la imagen en la biblioteca de medios.']);
    }

    // Generar los metadatos de la imagen
    $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    // Establecer la imagen destacada del post
    set_post_thumbnail($post_id, $attachment_id);

    // Devolver la URL de la nueva imagen
    wp_send_json_success(['new_image_url' => $file_url]); // Asegurarse de devolver la URL correcta
}
