<?

/*
function mejorarDescripcionAudioPro($post_id, $archivo_audio)
{
    // Comprobar si tiene la meta 'postAut' en 1
    $postAut = get_post_meta($post_id, 'postAut', true);
    // Comprobar si tiene la meta 'Verificado' en 1
    $verificado = get_post_meta($post_id, 'Verificado', true);

    // Si la meta 'postAut' es 1 y la meta 'Verificado' no es 1, no enviar el contenido a la IA
    if ($postAut == 1 && $verificado != 1) {
        iaLog("El post ID: {$post_id} tiene postAut en 1 y no está verificado. No se enviará el contenido a la IA.");
        $post_content = ''; // No enviamos el contenido
    } else {
        // Obtener el contenido actual del post
        $post_content = get_post_field('post_content', $post_id);
        if (!$post_content) {
            iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
        } else {
            iaLog("Contenido del post obtenido para el post ID: {$post_id}");
        }
    }

    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción o lo acaba de publicar ahora mismo. "
        . "Ten muy en cuenta la descripcion. descripción:\"{$post_content}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, estos son datos de ejemplo!!: : "
        . '{"Descripcion":{"es":"(aqui iría una descripcion tuya del audio muy detallada)", "en":"(aqui en ingles)"},'
        . '"Instrumentos posibles":{"es":["Piano", "Guitarra"], "en":["Piano", "Guitar"]},'
        . '"Estado de animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"Genero posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"Artista posible":{"es":["Freddie Dredd", "Flume"], "en":["Freddie Dredd, Flume"]},'
        . '"Tipo de audio":{"es":["aqui necesito que puedas determinar si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"Tags posibles":{"es":["Naturaleza", "phonk", "memphis", "oscuro"], "en":["Nature"]},'
        . '"Sugerencia de busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot, o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes. Se muy detallado sin perder precisión";

    // Usar el modelo Pro para generar la nueva descripción
    $descripcion_mejorada = generarDescripcionIAPro($archivo_audio, $prompt);

    if ($descripcion_mejorada) {
        iaLog("Descripción mejorada generada correctamente para el post ID: {$post_id}");
        // Limpiar y procesar la nueva descripción generada
        $descripcion_procesada = json_decode(trim($descripcion_mejorada, "```json \n"), true);

        if ($descripcion_procesada) {
            iaLog("Descripción JSON procesada correctamente para el post ID: {$post_id}");

            // Obtener el metadato 'datosAlgoritmo' existente
            $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
            if ($datos_algoritmo) {
                $datos_algoritmo = json_decode($datos_algoritmo, true);
                iaLog("DatosAlgoritmo existentes obtenidos para el post ID: {$post_id}");
            } else {
                $datos_algoritmo = [];
                iaLog("No se encontraron datosAlgoritmo existentes para el post ID: {$post_id}");
            }

            // Preservar los datos esenciales
            $datos_preservados = [
                'tags' => $datos_algoritmo['tags'] ?? [],
                'bpm' => $datos_algoritmo['bpm'] ?? null,
                'key' => $datos_algoritmo['key'] ?? null,
                'scale' => $datos_algoritmo['scale'] ?? null,
                'autor' => $datos_algoritmo['autor'] ?? []
            ];
            iaLog("Datos preservados para el post ID: {$post_id}");

            // Agregar la nueva descripción IA mejorada con traducciones
            $nuevos_datos = [
                'descripcion_ia_pro' => [
                    'es' => $descripcion_procesada['Descripcion']['es'] ?? '',
                    'en' => $descripcion_procesada['Descripcion']['en'] ?? ''
                ],
                'instrumentos_posibles' => [
                    'es' => $descripcion_procesada['Instrumentos posibles']['es'] ?? [],
                    'en' => $descripcion_procesada['Instrumentos posibles']['en'] ?? []
                ],
                'estado_animo' => [
                    'es' => $descripcion_procesada['Estado de animo']['es'] ?? [],
                    'en' => $descripcion_procesada['Estado de animo']['en'] ?? []
                ],
                'artista_posible' => [
                    'es' => $descripcion_procesada['Artista posible']['es'] ?? [],
                    'en' => $descripcion_procesada['Artista posible']['en'] ?? []
                ],
                'genero_posible' => [
                    'es' => $descripcion_procesada['Genero posible']['es'] ?? [],
                    'en' => $descripcion_procesada['Genero posible']['en'] ?? []
                ],
                'tipo_audio' => [
                    'es' => $descripcion_procesada['Tipo de audio']['es'] ?? [],
                    'en' => $descripcion_procesada['Tipo de audio']['en'] ?? []
                ],
                'tags_posibles' => [
                    'es' => $descripcion_procesada['Tags posibles']['es'] ?? [],
                    'en' => $descripcion_procesada['Tags posibles']['en'] ?? []
                ],
                'sugerencia_busqueda' => [
                    'es' => $descripcion_procesada['Sugerencia de busqueda']['es'] ?? [],
                    'en' => $descripcion_procesada['Sugerencia de busqueda']['en'] ?? []
                ]
            ];
            iaLog("Nuevos datos generados para el post ID: {$post_id}");

            // Combinar los datos preservados con los nuevos
            $datos_actualizados = array_merge($datos_preservados, $nuevos_datos);

            // Guardar los metadatos actualizados
            update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_actualizados, JSON_UNESCAPED_UNICODE));
            iaLog("Metadatos actualizados para el post ID: {$post_id}");

            // Marcar que la descripción fue mejorada
            update_post_meta($post_id, 'proIA', true);
            iaLog("Post con descripción mejorada marcado como IA Pro para el post ID: {$post_id}");
        } else {
            iaLog("Error al procesar el JSON de la descripción mejorada generada por IA Pro.");
        }
    } else {
        iaLog("No se pudo generar la descripción mejorada para el post ID: {$post_id}");
    }
}

*/
function procesarUnAudio()
{
    global $wpdb;

    iaLog("Iniciando el procesamiento de un audio...");

    $query = "
        SELECT p.ID, pm.meta_value AS archivo_audio_id
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        LEFT JOIN {$wpdb->postmeta} proIA_meta 
            ON p.ID = proIA_meta.post_id 
            AND proIA_meta.meta_key = 'proIA'
        LEFT JOIN {$wpdb->postmeta} verificado_meta 
            ON p.ID = verificado_meta.post_id 
            AND verificado_meta.meta_key = 'Verificado'
        WHERE pm.meta_key = 'post_audio_lite'
            AND proIA_meta.meta_value IS NULL
            AND (
                verificado_meta.meta_value IS NULL 
                OR verificado_meta.meta_value NOT IN ('true', '1')
            )
            AND p.post_status = 'publish'
        ORDER BY p.post_date DESC
        LIMIT 1
    ";

    iaLog("Ejecutando consulta SQL para buscar post con audio, excluyendo verificados...");

    $post_con_audio = $wpdb->get_row($query);

    if ($post_con_audio) {
        $post_id = $post_con_audio->ID;
        $archivo_audio_id = $post_con_audio->archivo_audio_id;

        iaLog("Post encontrado. ID: {$post_id}, archivo_audio_id: {$archivo_audio_id}");

        // Obtener la URL del archivo de audio a partir de la ID
        $archivo_audio_url = wp_get_attachment_url($archivo_audio_id);

        // Si no se encuentra la URL, saltar al siguiente post
        if (!$archivo_audio_url) {
            iaLog("No se encontró la URL del archivo de audio para el post ID: {$post_id}. Saliendo.");
            return;
        }

        iaLog("URL del archivo de audio obtenida: {$archivo_audio_url}");

        // Convertir la URL a la ruta del servidor
        $upload_dir = wp_upload_dir();
        $archivo_audio_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $archivo_audio_url);

        iaLog("Ruta del servidor del archivo de audio: {$archivo_audio_path}");

        // Verificar si el archivo de audio existe en el servidor
        if (file_exists($archivo_audio_path)) {
            iaLog("El archivo de audio existe en el servidor. Iniciando mejora de descripción...");

            // Ejecutar la función para mejorar la descripción del audio
            mejorarDescripcionAudioPro($post_id, $archivo_audio_path);

            iaLog("Mejora de descripción completada para el post ID: {$post_id}");
        } else {
            iaLog("El archivo de audio no existe en el servidor para el post ID: {$post_id}");
        }
    } else {
        iaLog("No se encontraron posts pendientes de mejora de descripción.");
    }

    iaLog("Procesamiento de audio completado.");
}

/*
// Función para ejecutarla cada 30 minutos
function programarMejorarDescripcionAudioPro()
{
    if (!wp_next_scheduled('mejorar_descripcion_audio_event')) {
        wp_schedule_event(time(), 'thirty_minutes', 'mejorar_descripcion_audio_event');
    }
}

add_action('mejorar_descripcion_audio_event', 'procesarUnAudio');

// Registrar intervalo de 30 minutos
function agregarIntervaloCronPersonalizado($schedules)
{
    $schedules['thirty_minutes'] = [
        'interval' => 1800, // 1800 segundos = 30 minutos
        'display'  => __('Cada 30 minutos')
    ];
    return $schedules;
}

add_filter('cron_schedules', 'agregarIntervaloCronPersonalizado');

// Programar evento al activar el plugin o tema
add_action('wp', 'programarMejorarDescripcionAudioPro');

*/