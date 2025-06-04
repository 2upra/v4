<?php

namespace App\Services\Post;

use App\Utils\Logger;
use App\Services\IAService;

class PostAttachmentService
{
    private Logger $logger;
    private IAService $iaService;

    public function __construct(Logger $logger, IAService $iaService)
    {
        $this->logger = $logger;
        $this->iaService = $iaService;
    }

    public function renameAttachmentFile(int $attachment_id, string $nuevo_nombre, bool $es_lite = false): bool
    {
        $ruta_archivo = get_attached_file($attachment_id);
        if (!$ruta_archivo || !file_exists($ruta_archivo)) {
            $this->logger->error("Error en renameAttachmentFile: El archivo adjunto no existe o la ruta es inválida para attachment ID: {$attachment_id}. Ruta: {$ruta_archivo}");
            return false;
        }

        $carpeta = pathinfo($ruta_archivo, PATHINFO_DIRNAME);
        $extension = pathinfo($ruta_archivo, PATHINFO_EXTENSION);
        $final_nombre = $nuevo_nombre;
        if ($es_lite) {
            $final_nombre .= '_lite';
        }
        $nueva_ruta = $carpeta . '/' . $final_nombre . '.' . $extension;

        if (!rename($ruta_archivo, $nueva_ruta)) {
            $this->logger->error("Error en renameAttachmentFile: Fallo al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
            return false;
        }

        $this->logger->log("Archivo renombrado en el servidor de {$ruta_archivo} a {$nueva_ruta}");

        $wp_filetype = wp_check_filetype(basename($nueva_ruta), null);
        $attachment_data = array(
            'ID' => $attachment_id,
            'post_name' => sanitize_title($final_nombre),
            'guid' => home_url('/') . str_replace(ABSPATH, '', $nueva_ruta),
        );

        if (function_exists('wp_update_post')) {
            wp_update_post($attachment_data);
        }

        update_attached_file($attachment_id, $nueva_ruta);

        return true;
    }

    #Paso 5
    public function procesarURLs($postId)
    {
        $tiposURLs = [
            'imagenUrl'  => ['procesarArchivo', false],
            'audioUrl'   => ['procesarArchivo', true],
            'archivoUrl' => ['procesarArchivo', false],
        ];
        $maxCampos = 30;

        foreach ($tiposURLs as $tipoBase => $callbackData) {
            $funcionCallback = $callbackData[0];
            $renombrar = $callbackData[1];

            for ($i = 1; $i <= $maxCampos; $i++) {
                $campo = $tipoBase . $i;
                if (!empty($_POST[$campo])) {
                    $url = esc_url_raw(trim($_POST[$campo]));

                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                         call_user_func([$this, $funcionCallback], $postId, $campo, $renombrar, $i);
                    } else {
                        $this->logger->error("Error en procesarURLs: URL invalida en el campo: {$campo} para postId: {$postId}. URL: {$url}");
                    }
                }
            }
        }
    }

    #Paso 5.1 #Prepara para buscar la ID y actualizar la meta
    public function procesarArchivo($postId, $campo, $renombrar = false, $indice = null)
    {
        if (empty($_POST[$campo])) return false;

        $url = esc_url_raw(trim($_POST[$campo]));
        $archivoId = $this->obtenerArchivoId($url, $postId);

        if ($archivoId && !is_wp_error($archivoId)) {
            if ($this->actualizarMetaConArchivo($postId, $campo, $archivoId, $indice) === false) {
                $this->logger->error("Error en procesarArchivo: Fallo al actualizar meta con archivo para Post ID: $postId, Campo: $campo, Archivo ID: $archivoId");
                return false;
            }

            if ($renombrar && $indice !== null) {
                if ($this->renombrarArchivoAdjunto($postId, $archivoId, $indice) === false) {
                    $this->logger->error("Error en procesarArchivo: Fallo al renombrar el archivo adjunto para Post ID: $postId, Campo: $campo, Archivo ID: $archivoId, Indice: $indice");
                }
            }
            return true;
        } else {
            $mensajeError = 'ID de archivo inválido o no encontrado.';
            if (is_wp_error($archivoId)) {
                $mensajeError = $archivoId->get_error_message();
            }
            $log_message = str_replace("\n", " | ", $mensajeError);
            $this->logger->error("Error en procesarArchivo: No se pudo procesar el archivo para Post ID: $postId, Campo: $campo. URL: {$url}. Error: " . $log_message);
            return false;
        }
    }

    #Paso 5.3 #Busca la Id de Adjunto segun la URL
    public function obtenerArchivoId($url, $postId)
    {
        $archivoId = attachment_url_to_postid($url);

        if ($archivoId && get_post_type($archivoId) === 'attachment') {
            return $archivoId;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $tmp_file = download_url($url, 15);

        if (is_wp_error($tmp_file)) {
            $error_message = str_replace("\n", " | ", $tmp_file->get_error_message());
            $this->logger->error("Error en obtenerArchivoId (download_url): No se pudo descargar desde {$url}. Error: " . $error_message);
            return false;
        }

        $file_info = [
            'name'     => basename($url),
            'tmp_name' => $tmp_file
        ];

        $archivoId = media_handle_sideload($file_info, $postId);

        if (file_exists($tmp_file)) {
            @unlink($tmp_file);
        }

        if (is_wp_error($archivoId)) {
            $error_message = str_replace("\n", " | ", $archivoId->get_error_message());
            $this->logger->error("Error en obtenerArchivoId (media_handle_sideload): No se pudo crear el adjunto desde la URL {$url}. Error: " . $error_message);
            return false;
        }

        return $archivoId;
    }

    #PASO 5.2
    public function renombrarArchivoAdjunto($postId, $archivoId, $indice)
    {
        if (!$archivoId || get_post_type($archivoId) !== 'attachment') {
            $this->logger->error("Error en renombrarArchivoAdjunto: ID de archivo inválido {$archivoId} para postId {$postId}.");
            return false;
        }

        $post = get_post($postId);
        if (!$post) {
            $this->logger->error("Error en renombrarArchivoAdjunto: No se pudo obtener el post para postId: {$postId}.");
            return false;
        }
        $author = get_userdata($post->post_author);
        if (!$author) {
            $this->logger->error("Error en renombrarArchivoAdjunto: No se pudo obtener el autor para postId: {$postId}.");
            return false;
        }

        $file_path = get_attached_file($archivoId);
        if (!$file_path || !file_exists($file_path)) {
            $this->logger->error("Error en renombrarArchivoAdjunto: El archivo adjunto no existe para archivoId: {$archivoId}. Ruta esperada: {$file_path}");
            return false;
        }

        $info = pathinfo($file_path);
        $random_id = wp_rand(10000, 99999);
        $autor_login_sanitized = sanitize_file_name(mb_substr($author->user_login, 0, 20));
        $post_title_sanitized = sanitize_file_name(wp_trim_words($post->post_title, 5, ''));
        $post_title_sanitized = mb_substr($post_title_sanitized, 0, 40);

        $new_filename = sprintf(
            '2upra_%s_%s_%d_%d.%s',
            $autor_login_sanitized,
            $post_title_sanitized ?: "post{$postId}",
            $indice,
            $random_id,
            strtolower($info['extension'])
        );
        $new_file_path = $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename;

        if (rename($file_path, $new_file_path)) {
            $update_path_result = update_attached_file($archivoId, $new_file_path);
            if (!$update_path_result) {
                 $this->logger->error("Error en renombrarArchivoAdjunto: El archivo se renombró en el sistema ({$new_file_path}) pero falló al actualizar la ruta en WP para archivoId: {$archivoId}.");
                 return false;
            }

            $public_url = wp_get_attachment_url($archivoId);
            if (!$public_url) {
                $this->logger->error("Error en renombrarArchivoAdjunto: No se pudo obtener la nueva URL pública para archivoId: {$archivoId} después de renombrar.");
            }

            update_post_meta($postId, 'sample', true);
            procesarAudioLigero($postId, $archivoId, $indice);

            return true;

        } else {
            $this->logger->error("Error en renombrarArchivoAdjunto: No se pudo renombrar el archivo adjunto de {$file_path} a {$new_file_path}. Verificar permisos.");
            return false;
        }
    }

    #Paso 5.4
    public function actualizarMetaConArchivo($postId, $campo, $archivoId, $indice = null)
    {
        $meta_mapping = [
            'imagenUrl'   => 'imagenID',
            'audioUrl'    => 'post_audio',
            'archivoUrl'  => 'archivoID'
        ];

        preg_match('/^(?<base>imagenUrl|audioUrl|archivoUrl)/', $campo, $matches_base);
        $baseField = $matches_base['base'] ?? null;

        if ($baseField && isset($meta_mapping[$baseField]) && $indice !== null) {
            $baseMetaKey = $meta_mapping[$baseField];
            $meta_key = $baseMetaKey . $indice;

            if (update_post_meta($postId, $meta_key, $archivoId) === false) {
                $this->logger->error("Error en actualizarMetaConArchivo: Fallo al actualizar meta {$meta_key} con archivo ID {$archivoId} para el post ID {$postId}.");
                return false;
            }

            if ($baseField === 'imagenUrl' && $indice == 1) {
                set_post_thumbnail($postId, $archivoId);
            }

            return true;

        } else {
            $this->logger->error("Advertencia/Error en actualizarMetaConArchivo: No se pudo mapear el campo '{$campo}' o falta el índice para actualizar la meta en el post ID {$postId}.");
            return false;
        }
    }

    #Paso 5.6
    public function analizarYGuardarMetasAudio($post_id, $audio_path_lite, $index, $nombre_archivo = null, $carpeta = null, $carpeta_abuela = null)
    {
        $this->logger->log("INICIO analizarYGuardarMetasAudio para Post ID: {$post_id}, Index: {$index}, Path: {$audio_path_lite}");

        if (!file_exists($audio_path_lite)) {
            $this->logger->error("Error: El archivo de audio '{$audio_path_lite}' no existe.");
            return;
        }

        $python_script_path = '/var/www/wordpress/wp-content/themes/2upra3v/app/python/audio.py';
        if (!file_exists($python_script_path)) {
            $this->logger->error("Error: El script de Python '{$python_script_path}' no existe.");
            return;
        }

        $python_command = sprintf(
            "python3 %s %s",
            escapeshellcmd($python_script_path),
            escapeshellarg($audio_path_lite)
        );
        $this->logger->log("Ejecutando comando de Python: {$python_command}");
        exec($python_command . " 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            $log_output = implode(" | ", $output);
            $this->logger->error("Error al ejecutar el script de Python. Codigo de retorno: {$return_var}. Salida: " . $log_output);
            return;
        }
        $this->logger->log("Script de Python ejecutado exitosamente. Salida: " . implode(" | ", $output));


        $resultados_path = $audio_path_lite . '_resultados.json';
        $this->logger->log("Buscando archivo de resultados en: {$resultados_path}");

        if (file_exists($resultados_path)) {
            $resultados_json = file_get_contents($resultados_path);
            $resultados = json_decode($resultados_json, true);

            if ($resultados && is_array($resultados)) {
                $this->logger->log("Resultados JSON decodificados: " . print_r($resultados, true));
                $suffix = ($index == 1) ? '' : "_{$index}";

                update_post_meta($post_id, "audio_bpm{$suffix}", sanitize_text_field($resultados['bpm'] ?? ''));
                update_post_meta($post_id, "audio_pitch{$suffix}", sanitize_text_field($resultados['pitch'] ?? ''));
                update_post_meta($post_id, "audio_emotion{$suffix}", sanitize_text_field($resultados['emotion'] ?? ''));
                update_post_meta($post_id, "audio_key{$suffix}", sanitize_text_field($resultados['key'] ?? ''));
                update_post_meta($post_id, "audio_scale{$suffix}", sanitize_text_field($resultados['scale'] ?? ''));
                update_post_meta($post_id, "audio_strength{$suffix}", sanitize_text_field($resultados['strength'] ?? ''));

                $this->logger->log("Metas de análisis de audio (BPM, pitch, etc.) guardadas con sufijo '{$suffix}'.");

            } else {
                $this->logger->error("Error: El archivo de resultados JSON '{$resultados_path}' está vacío o no contiene JSON válido.");
                $resultados = [];
            }

        } else {
            $this->logger->log("Advertencia: No se encontró el archivo de resultados '{$resultados_path}'. No se guardarán metas de análisis de audio.");
            $resultados = [];
        }

        $post_content = get_post_field('post_content', $post_id);
        if (!$post_content) {
            $this->logger->log("Advertencia: No se pudo obtener el contenido del post ID: {$post_id} para generar el prompt.");
        }

        $tags_usuario = get_post_meta($post_id, 'tagsUsuario', true);
        $tags_usuario_texto = $tags_usuario ? implode(', ', (array)$tags_usuario) : '';

        $informacion_archivo = '';
        if ($nombre_archivo) {
            $informacion_archivo .= "Nombre Archivo Original: '" . sanitize_text_field($nombre_archivo) . "'\n";
        }
        if ($carpeta) {
            $informacion_archivo .= "Carpeta Original: '" . sanitize_text_field($carpeta) . "'\n";
        }
        if ($carpeta_abuela) {
            $informacion_archivo .= "Carpeta Padre Original: '" . sanitize_text_field($carpeta_abuela) . "'\n";
        }
        $informacion_archivo = str_replace('/home/asley01/MEGA/Waw/Kits', '', $informacion_archivo);


        $prompt = "Analiza la siguiente información sobre un archivo de audio y genera una descripción detallada en formato JSON.\n";
        $prompt .= "Descripción del usuario: \"{$post_content}\"\n";
        $prompt .= "Tags del usuario: {$tags_usuario_texto}\n";
        if ($informacion_archivo) {
            $prompt .= "Información adicional del archivo:\n{$informacion_archivo}\n";
        }
        if (!empty($resultados)) {
            $prompt .= "Datos de análisis técnico:\n";
            $prompt .= "BPM: " . ($resultados['bpm'] ?? 'N/A') . "\n";
            $prompt .= "Tono (Pitch): " . ($resultados['pitch'] ?? 'N/A') . "\n";
            $prompt .= "Emoción: " . ($resultados['emotion'] ?? 'N/A') . "\n";
            $prompt .= "Tonalidad (Key): " . ($resultados['key'] ?? 'N/A') . "\n";
            $prompt .= "Escala: " . ($resultados['scale'] ?? 'N/A') . "\n";
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

        $this->logger->log("Prompt generado para IA (longitud: " . strlen($prompt) . " caracteres). Llamando a generarDescripcionIA...");

        $descripcion_json_string = $this->iaService->generarDescripcionIA($audio_path_lite, $prompt);

        $nuevos_datos_ia = [];

        if ($descripcion_json_string) {
            $this->logger->log("Respuesta recibida de IA (primeros 500 chars): " . substr($descripcion_json_string, 0, 500));
            $descripcion_json_string = preg_replace('/^```json\s*|\s*```$/', '', trim($descripcion_json_string));

            $descripcion_procesada = json_decode($descripcion_json_string, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($descripcion_procesada)) {
                $this->logger->log("JSON de IA decodificado correctamente.");

                $nuevos_datos_ia = [
                    'descripcion_ia' => [
                        'es' => sanitize_textarea_field($descripcion_procesada['descripcion_ia']['es'] ?? ''),
                        'en' => sanitize_textarea_field($descripcion_procesada['descripcion_ia']['en'] ?? '')
                    ],
                     'instrumentos_posibles' => [
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

                $suffix = ($index == 1) ? '' : "_{$index}";
                if (update_post_meta($post_id, "audio_descripcion{$suffix}", wp_json_encode($nuevos_datos_ia, JSON_UNESCAPED_UNICODE))) {
                     $this->logger->log("Meta 'audio_descripcion{$suffix}' guardada para el post ID: {$post_id}");
                } else {
                     $this->logger->error("Error al guardar la meta 'audio_descripcion{$suffix}' para el post ID: {$post_id}");
                }

            } else {
                $this->logger->error("Error al decodificar el JSON de la IA. Error: " . json_last_error_msg() . ". Respuesta recibida: " . $descripcion_json_string);
            }
        } else {
            $this->logger->log("No se recibió respuesta o la respuesta estuvo vacía de la función generarDescripcionIA.");
        }


        $datos_algoritmo_json = get_post_meta($post_id, 'datosAlgoritmo', true);
        $datos_algoritmo = json_decode($datos_algoritmo_json, true);
        if (!is_array($datos_algoritmo)) {
            $datos_algoritmo = [];
        }
        $this->logger->log("Datos Algoritmo existentes: " . print_r($datos_algoritmo, true));


        $nuevos_datos_tecnicos = [
            'bpm' => sanitize_text_field($resultados['bpm'] ?? ''),
            'pitch' => sanitize_text_field($resultados['pitch'] ?? ''),
            'emotion' => sanitize_text_field($resultados['emotion'] ?? ''),
            'key' => sanitize_text_field($resultados['key'] ?? ''),
            'scale' => sanitize_text_field($resultados['scale'] ?? ''),
        ];
        $nuevos_datos_tecnicos = array_filter($nuevos_datos_tecnicos, function($value) { return $value !== ''; });

        $this->logger->log("Nuevos datos técnicos a mergear: " . print_r($nuevos_datos_tecnicos, true));
        $this->logger->log("Nuevos datos IA a mergear: " . print_r($nuevos_datos_ia, true));


        $datos_algoritmo = array_merge($datos_algoritmo, $nuevos_datos_tecnicos, $nuevos_datos_ia);

        $this->logger->log("Datos Algoritmo combinados antes de guardar: " . print_r($datos_algoritmo, true));

        if (update_post_meta($post_id, 'datosAlgoritmo', wp_json_encode($datos_algoritmo, JSON_UNESCAPED_UNICODE))) {
            $this->logger->log("Meta 'datosAlgoritmo' actualizada exitosamente para el post ID: {$post_id}");
        } else {
             $this->logger->error("Error al actualizar la meta 'datosAlgoritmo' para el post ID: {$post_id}");
        }

        update_post_meta($post_id, 'flashIA', true);
        $this->logger->log("Meta 'flashIA' establecida a true para el post ID: {$post_id}");

        $this->logger->log("FIN analizarYGuardarMetasAudio para Post ID: {$post_id}, Index: {$index}");
    }

    public function cambiar_imagen_post_handler() {
        if (empty($_POST['post_id']) || empty($_FILES['imagen'])) {
            $this->logger->error('AJAX Error: Faltan datos necesarios para cambiar_imagen_post.');
            wp_send_json_error(['message' => 'Faltan datos necesarios.']);
        }

        $post_id = intval($_POST['post_id']);

        $post = get_post($post_id);
        if (!$post) {
            $this->logger->error("AJAX Error: El post ID: {$post_id} no existe.");
            wp_send_json_error(['message' => 'El post no existe.']);
        }

        if ((int) $post->post_author !== get_current_user_id()) {
            $this->logger->error("AJAX Error: Usuario ID: " . get_current_user_id() . " no tiene permisos para cambiar la imagen del post ID: {$post_id}.");
            wp_send_json_error(['message' => 'No tienes permisos para cambiar la imagen de este post.']);
        }

        $file = $_FILES['imagen'];

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (isset($upload['error']) || !isset($upload['file'])) {
            $this->logger->error("AJAX Error: Error al subir la imagen para post ID: {$post_id}. Error: " . ($upload['error'] ?? 'Desconocido'));
            wp_send_json_error(['message' => 'Error al subir la imagen: ' . $upload['error']]);
        }

        $file_path = $upload['file'];
        $file_url = $upload['url'];

        $attachment_id = wp_insert_attachment([
            'guid'           => $file_url,
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name($file['name']),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $file_path, $post_id);

        if (is_wp_error($attachment_id) || !$attachment_id) {
            $this->logger->error("AJAX Error: Error al guardar la imagen en la biblioteca de medios para post ID: {$post_id}.");
            wp_send_json_error(['message' => 'Error al guardar la imagen en la biblioteca de medios.']);
        }

        $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attach_data);

        set_post_thumbnail($post_id, $attachment_id);

        wp_send_json_success(['new_image_url' => $file_url]);
    }

    public function adjuntarArchivo($newPostId, $fileUrl) {
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
                $this->logger->log("Archivo físico no encontrado para adjuntar.");
                return false;
            }
        }

        if ($attachment_id) {
            $fileAdjIds = get_post_meta($newPostId, 'fileAdjIds', true) ?: [];
            $fileAdjUrls = get_post_meta($newPostId, 'fileAdjUrls', true) ?: [];

            $fileAdjIds[] = $attachment_id;
            $fileAdjUrls[] = $fileUrl;

            update_post_meta($newPostId, 'fileAdjIds', array_unique($fileAdjIds));
            update_post_meta($newPostId, 'fileAdjUrls', array_unique($fileAdjUrls));

            $mime_type = get_post_mime_type($attachment_id);

            if (strpos($mime_type, 'audio') !== false) {
                $audioAdjIds = get_post_meta($newPostId, 'audioAdjIds', true) ?: [];
                $audioAdjIds[] = $attachment_id;
                update_post_meta($newPostId, 'audioAdjIds', array_unique($audioAdjIds));

                $index = 1;
                procesarAudioLigero($newPostId, $attachment_id, $index);
            } elseif (strpos($mime_type, 'image') !== false) {
                $imgAdjIds = get_post_meta($newPostId, 'imgAdjIds', true) ?: [];
                if (empty($imgAdjIds)) {
                    set_post_thumbnail($newPostId, $attachment_id);
                }
                $imgAdjIds[] = $attachment_id;
                update_post_meta($newPostId, 'imgAdjIds', array_unique($imgAdjIds));
            } else {
                $this->logger->log("Archivo adjuntado (tipo desconocido) con ID: {$attachment_id}");
            }

            $this->logger->log("Archivo adjuntado con ID: {$attachment_id}");
            return true;
        }

        return false;
    }

    public function updateUrlForHash(string $idHash, string $newUrl): bool
    {
        $this->logger->log("Attempting to update URL for hash ID: {$idHash} with new URL: {$newUrl}");
        return true;
    }
}