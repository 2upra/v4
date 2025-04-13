<?

function generarJsonAudioIA($rutaArchivo, $nombre_archivo = null, $carpeta = null, $carpeta_abuela = null)
{
    error_log("generarJsonAudioIA start");
    $resultados = procesarArchivoAudioPython($rutaArchivo);

    if ($resultados) {
        echo "BPM: " . ($resultados['bpm'] ?? '') . "\n";
        echo "Emotion: " . ($resultados['emotion'] ?? '') . "\n";
        echo "Key: " . ($resultados['key'] ?? '') . "\n";
        echo "Scale: " . ($resultados['scale'] ?? '') . "\n";
        echo "Pitch: " . ($resultados['pitch'] ?? '') . "\n";
    } else {
        echo "Error procesando el archivo de audio.";
    }

    $informacion_archivo = '';
    if ($nombre_archivo) {
        $informacion_archivo .= "Archivo (IMPORTANCIA ALTA): '{$nombre_archivo}'\n";
    }
    if ($carpeta) {
        $informacion_archivo .= "Carpeta (IMPORTANCIA MEDIA): '{$carpeta}'\n";
    }
    if ($carpeta_abuela) {
        $informacion_archivo .= "Carpeta abuela (IMPORTANCIA BAJA): '{$carpeta_abuela}'\n";
    }
    if ($rutaArchivo) {
        $informacion_archivo .= "Ruta completa (PUEDE AYUDAR SI EL RESTO DE INFORMACIONES NO ES CLARA): '{$rutaArchivo}'\n";
    }

    $prompt = "Este audio fue subido automáticamente. Información:"
        . "{$informacion_archivo}"
        . "Por favor, determina una descripción precisa del audio utilizando el siguiente formato JSON. La información como el nombre y las carpetas son información super relevante para completar el JSON. Por favor, ignora cualquier nombre comercial, dominio, redes sociales o información no relevante que pueda contener el nombre o las carpetas. También ignora la palabra 'lite' o '2upra'. El 'nombre_corto' es un nuevo nombre para el archivo, y la 'descripción corta' es para entender rápidamente qué es el audio, por favor, que sea corta pero sin perder detalles importantes. Importante por no digas nada sobre las carpetas o donde esta ubicado el archivo, solo es una guia para entender de que trata el audio no hay que comentarlo, si archivo tiene un nombre claro, hay que tenerlo en cuenta, y luego el resto. Con los artistas posible siempre piensa en uno o varios que tengan la vibra de la descripción que la gente pueda relacionar con el audio. No uses palabras como 'Repetitive', 'Energetic', 'Powerful' en la descripcion corta. Te incluyo la estructura JSON con datos de ejemplo, que son irrelevantes en este caso: "
        . '{"descripcion_ia":{"es":"(aquí iría una descripción tuya del audio muy detallada)", "en":"(aquí en inglés)"},'
        . '"instrumentos_principal":{"es":["Piano"], "en":["Piano"]},'
        . '"nombre_corto":{"es":["(maximo 3 palabras)"], "en":["Kick Vitagen"]},'
        . '"descripcion_corta":{"es":["(entre 4 a 6 palabras)"], "en":["(en ingles)"]},'
        . '"estado_animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"genero_posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"artista_posible":{"es":["Freddie Dredd", "Flume"], "en":["Freddie Dredd", "Flume"]},'
        . '"tipo_audio":{"es":["determina si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"tags_posibles":{"es":["Naturaleza", "phonk", "memphis", "oscuro"], "en":["Nature"]},'
        . '"sugerencia_busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . "Te dejo una guía interesante de tags que puedes usar, por favor, usa solo los que realmente describan el audio: "
        . "Tipo y Formato: Acoustic, Chord, Down Sweep/Fall, Dry, Harmony, Loop, Melody, Mixed, Monophonic, One Shot, Polyphonic, Processed, Progression, Riser/Sweep, Short, Wet. "
        . "Timbre y Tono: Bassy, Boomy, Breathy, Bright, Buzzy, Clean, Coarse/Harsh, Cold, Dark, Delicate, Detuned, Dissonant, Distorted, Exotic, Fat, Full, Glitchy, Granular, Gloomy, Hard, High, Hollow, Low, Metallic, Muffled, Muted, Narrow, Noisy, Round, Sharp, Shimmering, Sizzling, Smooth, Soft, Piercing, Thin, Tinny, Warm, Wide, Wooden. "
        . "Género: Ambient, Breaks, Chillout, Chiptune, Cinematic, Classical, Acid House, Deep House, Disco, Drum & Bass, Dubstep, Ethnic/World, Electro House, Electro, Electro Swing, Folk/Country, Funk/Soul, Jazz, Jungle, House, Hip Hop, Latin/Afro Cuban, Minimal House, Nu Disco, R&B, Reggae/Dub, Reggaeton, Rock, Pop, Progressive House, Synthwave, Tech House, Techno, Trance, Trap, Vocals, Phonk, Memphis. "
        . "Estilo y Técnica: Arpeggiated, Decaying, Echoing, Long Release, Legato, Glissando/Glide, Pad, Percussive, Pitch Bend, Plucked, Pulsating, Punchy, Randomized, Slow Attack, Sweep/Filter Mod, Staccato/Stabs, Stuttered/Gated, Straight, Sustained, Syncopated, Uptempo, Wobble, Vibrato. "
        . "Calidad y Tecnología: Analog, Compressed, Digital, Dynamic, Loud, Range, Female, Funky, Jazzy, Lo Fi, Male, Quiet, Vintage, Vinyl. "
        . "Estado de Ánimo: Aggressive, Angry, Bouncy, Calming, Carefree, Cheerful, Climactic, Cool, Dramatic, Elegant, Epic, Excited, Energetic, Fun, Futuristic, Gentle, Groovy, Happy, Haunting, Hypnotic, Industrial, Manic, Melancholic, Mellow, Mystical, Nervous, Passionate, Peaceful, Playful, Powerful, Rebellious, Reflective, Relaxing, Romantic, Rowdy, Sad, Sentimental, Sexy, Soothing, Sophisticated, Spacey, Suspenseful, Uplifting, Urgent, Weird."
        . " Es crucial determinar si es un loop, un one shot o un sample. Usa tags de una palabra y optimiza el SEO con sugerencias de búsqueda relevantes. Sé muy detallado sin perder precisión. Aunque te pido en español y en ingles, hay algunas palabras que son mejor mantenerlas en ingles cuando en español son muy frecuentes, por ejemplo, kick, snare, cowbell, etc. Ignora '/home/asley01/MEGA/Waw/Kits' no es relevante, el resto de la ruta si.";

    $descripcion = generarDescripcionIA($rutaArchivo, $prompt);
    error_log("Descripcion generada");
    if ($descripcion) {
        // Convertir a UTF-8
        $descripcion_utf8 = mb_convert_encoding($descripcion, 'UTF-8', 'auto');
        $descripcion_procesada = json_decode(trim($descripcion_utf8, "```json \n"), true, 512, JSON_UNESCAPED_UNICODE);

        // Comprobar que la decodificación JSON fue exitosa y que el campo 'descripcion_ia' existe
        if (!$descripcion_procesada || !isset($descripcion_procesada['descripcion_ia']) || !is_array($descripcion_procesada['descripcion_ia'])) {
            iaLog("Error: La descripción procesada no tiene el formato esperado.");
            return false; // Retornar false en caso de error de formato
        }

        // Crear los nuevos datos con la estructura correcta
        $nuevos_datos = [
            'descripcion_ia' => [
                'es' => $descripcion_procesada['descripcion_ia']['es'] ?? '',
                'en' => $descripcion_procesada['descripcion_ia']['en'] ?? ''
            ],
            'instrumentos_principal' => [
                'es' => $descripcion_procesada['instrumentos_principal']['es'] ?? [],
                'en' => $descripcion_procesada['instrumentos_principal']['en'] ?? []
            ],
            'nombre_corto' => [
                'es' => $descripcion_procesada['nombre_corto']['es'] ?? '',
                'en' => $descripcion_procesada['nombre_corto']['en'] ?? ''
            ],
            'descripcion_corta' => [
                'es' => $descripcion_procesada['descripcion_corta']['es'] ?? '',
                'en' => $descripcion_procesada['descripcion_corta']['en'] ?? ''
            ],
            'estado_animo' => [
                'es' => $descripcion_procesada['estado_animo']['es'] ?? [],
                'en' => $descripcion_procesada['estado_animo']['en'] ?? []
            ],
            'artista_posible' => [
                'es' => $descripcion_procesada['artista_posible']['es'] ?? [],
                'en' => $descripcion_procesada['artista_posible']['en'] ?? []
            ],
            'genero_posible' => [
                'es' => $descripcion_procesada['genero_posible']['es'] ?? [],
                'en' => $descripcion_procesada['genero_posible']['en'] ?? []
            ],
            'tipo_audio' => [
                'es' => $descripcion_procesada['tipo_audio']['es'] ?? '',
                'en' => $descripcion_procesada['tipo_audio']['en'] ?? ''
            ],
            'tags_posibles' => [
                'es' => $descripcion_procesada['tags_posibles']['es'] ?? [],
                'en' => $descripcion_procesada['tags_posibles']['en'] ?? []
            ],
            'sugerencia_busqueda' => [
                'es' => $descripcion_procesada['sugerencia_busqueda']['es'] ?? [],
                'en' => $descripcion_procesada['sugerencia_busqueda']['en'] ?? []
            ]
        ];

        //autLog("Descripción del audio guardada para el post ID: {$nombre_archivo}");
    } else {
        // Si no se generó ninguna descripción, retornar false
        error_log("Error: No se pudo generar la descripción.");
        return false;
    }

    $nuevos_datos_algoritmo = isset($nuevos_datos) ? [
        'bpm' => $resultados['bpm'] ?? '',
        'emotion' => $resultados['emotion'] ?? '',
        'key' => $resultados['key'] ?? '',
        'scale' => $resultados['scale'] ?? '',

        'descripcion_ia' => $nuevos_datos['descripcion_ia'],
        'instrumentos_principal' => $nuevos_datos['instrumentos_principal'],
        'nombre_corto' => $nuevos_datos['nombre_corto'],
        'descripcion_corta' => $nuevos_datos['descripcion_corta'],
        'estado_animo' => $nuevos_datos['estado_animo'],
        'artista_posible' => $nuevos_datos['artista_posible'],
        'genero_posible' => $nuevos_datos['genero_posible'],
        'tipo_audio' => $nuevos_datos['tipo_audio'],
        'tags_posibles' => $nuevos_datos['tags_posibles'],
        'sugerencia_busqueda' => $nuevos_datos['sugerencia_busqueda']
    ] : [];
    error_log("generarJsonAudioIA end");
    return $nuevos_datos_algoritmo;
}
