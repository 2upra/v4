<?php

/**
 * Servicio de descripción de audio con IA
 * 
 * Maneja la generación de descripciones usando IA:
 * - Construcción de prompts
 * - Procesamiento de respuestas
 * - Estructuración de datos
 * - Actualización de datos del algoritmo
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class AudioIAService
{
    private static ?AudioIAService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Genera descripción del audio usando IA
     */
    public function generarDescripcion(
        int $postId,
        string $audioPath,
        int $index,
        ?string $nombreArchivo = null,
        ?string $carpeta = null,
        ?string $carpetaAbuela = null
    ): void {
        $postContent = get_post_field('post_content', $postId);

        if (!$postContent) {
            $this->logger->warning('audio', "No se pudo obtener el contenido del post ID: {$postId}");
            return;
        }

        $prompt = $this->construirPrompt($postId, $postContent, $nombreArchivo, $carpeta, $carpetaAbuela);

        if (!function_exists('generarDescripcionIA')) {
            $this->logger->warning('audio', 'Función generarDescripcionIA no disponible');
            return;
        }

        $descripcion = generarDescripcionIA($audioPath, $prompt);

        if ($descripcion) {
            $this->procesarYGuardarDescripcion($postId, $descripcion, $index);
        }
    }

    /**
     * Construye el prompt para la IA
     */
    private function construirPrompt(
        int $postId,
        string $postContent,
        ?string $nombreArchivo,
        ?string $carpeta,
        ?string $carpetaAbuela
    ): string {
        $tagsUsuario = get_post_meta($postId, 'tagsUsuario', true);
        $tagsUsuarioTexto = $tagsUsuario ? (is_array($tagsUsuario) ? implode(', ', $tagsUsuario) : $tagsUsuario) : '';

        $informacionArchivo = '';
        if ($nombreArchivo) {
            $informacionArchivo .= "Archivo: '{$nombreArchivo}'\n";
        }
        if ($carpeta) {
            $informacionArchivo .= "Carpeta: '{$carpeta}'\n";
        }
        if ($carpetaAbuela) {
            $informacionArchivo .= "Carpeta abuela: '{$carpetaAbuela}'\n";
        }

        return "El usuario ya subió este audio, pero acaba de editar la descripción o lo acaba de publicar ahora mismo. "
            . "Ten en cuenta la descripcion, puede ser relevante. descripción:\"{$postContent}\". {$tagsUsuarioTexto}"
            . "{$informacionArchivo}"
            . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, estos son datos de ejemplo!!: El 'nombre_corto' es un nuevo nombre para el archivo, y la 'descripción corta' es para entender rápidamente qué es el audio, por favor, que sea corta pero sin perder detalles importantes. Con los artistas posible siempre piensa en uno o varios que tengan la vibra de la descripción que la gente pueda relacionar con el audio. No uses palabras como 'Repetitive', 'Energetic', 'Powerful' en la descripcion corta. Te incluyo la estructura JSON con datos de ejemplo, que son irrelevantes en este caso: "
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
            . $this->obtenerGuiaTags()
            . " Es crucial determinar si es un loop, un one shot o un sample. Usa tags de una palabra y optimiza el SEO con sugerencias de búsqueda relevantes. Sé muy detallado sin perder precisión. Aunque te pido en español y en ingles, hay algunas palabras que son mejor mantenerlas en ingles cuando en español son muy frecuentes, por ejemplo, kick, snare, cowbell, etc. Ignora '/home/asley01/MEGA/Waw/Kits' no es relevante, el resto de la ruta si.";
    }

    /**
     * Obtiene la guía de tags para el prompt de IA
     */
    private function obtenerGuiaTags(): string
    {
        return " Te dejo una guía interesante de tags que puedes usar, por favor, usa solo los que realmente describan el audio: "
            . "Tipo y Formato: Acoustic, Chord, Down Sweep/Fall, Dry, Harmony, Loop, Melody, Mixed, Monophonic, One Shot, Polyphonic, Processed, Progression, Riser/Sweep, Short, Wet. "
            . "Timbre y Tono: Bassy, Boomy, Breathy, Bright, Buzzy, Clean, Coarse/Harsh, Cold, Dark, Delicate, Detuned, Dissonant, Distorted, Exotic, Fat, Full, Glitchy, Granular, Gloomy, Hard, High, Hollow, Low, Metallic, Muffled, Muted, Narrow, Noisy, Round, Sharp, Shimmering, Sizzling, Smooth, Soft, Piercing, Thin, Tinny, Warm, Wide, Wooden. "
            . "Género: Ambient, Breaks, Chillout, Chiptune, Cinematic, Classical, Acid House, Deep House, Disco, Drum & Bass, Dubstep, Ethnic/World, Electro House, Electro, Electro Swing, Folk/Country, Funk/Soul, Jazz, Jungle, House, Hip Hop, Latin/Afro Cuban, Minimal House, Nu Disco, R&B, Reggae/Dub, Reggaeton, Rock, Pop, Progressive House, Synthwave, Tech House, Techno, Trance, Trap, Vocals, Phonk, Memphis. "
            . "Estilo y Técnica: Arpeggiated, Decaying, Echoing, Long Release, Legato, Glissando/Glide, Pad, Percussive, Pitch Bend, Plucked, Pulsating, Punchy, Randomized, Slow Attack, Sweep/Filter Mod, Staccato/Stabs, Stuttered/Gated, Straight, Sustained, Syncopated, Uptempo, Wobble, Vibrato. "
            . "Calidad y Tecnología: Analog, Compressed, Digital, Dynamic, Loud, Range, Female, Funky, Jazzy, Lo Fi, Male, Quiet, Vintage, Vinyl. "
            . "Estado de Ánimo: Aggressive, Angry, Bouncy, Calming, Carefree, Cheerful, Climactic, Cool, Dramatic, Elegant, Epic, Excited, Energetic, Fun, Futuristic, Gentle, Groovy, Happy, Haunting, Hypnotic, Industrial, Manic, Melancholic, Mellow, Mystical, Nervous, Passionate, Peaceful, Playful, Powerful, Rebellious, Reflective, Relaxing, Romantic, Rowdy, Sad, Sentimental, Sexy, Soothing, Sophisticated, Spacey, Suspenseful, Uplifting, Urgent, Weird.";
    }

    /**
     * Procesa y guarda la descripción generada por IA
     */
    public function procesarYGuardarDescripcion(int $postId, string $descripcion, int $index): void
    {
        $descripcionProcesada = json_decode(trim($descripcion, "```json \n"), true);

        if (!$descripcionProcesada) {
            $this->logger->error('audio', 'Error al procesar el JSON de la descripción generada por IA');
            return;
        }

        /* Corregir estructura de descripcion_ia si está mal anidada */
        if (isset($descripcionProcesada['descripcion_ia']['descripcion_ia'])) {
            $descripcionProcesada['descripcion_ia'] = [
                'es' => $descripcionProcesada['descripcion_ia']['descripcion_ia']['es'] ?? '',
                'en' => $descripcionProcesada['descripcion_ia']['descripcion_ia']['en'] ?? ''
            ];
        }

        $suffix = ($index == 1) ? '' : "_{$index}";

        if (isset($descripcionProcesada['descripcion_ia']) && is_array($descripcionProcesada['descripcion_ia'])) {
            $nuevosDatos = $this->estructurarDatos($descripcionProcesada);
            update_post_meta($postId, "audio_descripcion{$suffix}", json_encode($nuevosDatos, JSON_UNESCAPED_UNICODE));
            $this->logger->info('audio', "Descripción del audio guardada para el post ID: {$postId}");
        } else {
            $this->logger->error('audio', "'descripcion_ia' no está presente o tiene una estructura incorrecta");
        }
    }

    /**
     * Estructura los datos de descripción en el formato esperado
     */
    private function estructurarDatos(array $descripcionProcesada): array
    {
        return [
            'descripcion_ia' => [
                'es' => $descripcionProcesada['descripcion_ia']['es'] ?? '',
                'en' => $descripcionProcesada['descripcion_ia']['en'] ?? ''
            ],
            'instrumentos_posibles' => [
                'es' => $descripcionProcesada['instrumentos_posibles']['es'] ?? [],
                'en' => $descripcionProcesada['instrumentos_posibles']['en'] ?? []
            ],
            'estado_animo' => [
                'es' => $descripcionProcesada['estado_animo']['es'] ?? [],
                'en' => $descripcionProcesada['estado_animo']['en'] ?? []
            ],
            'artista_posible' => [
                'es' => $descripcionProcesada['artista_posible']['es'] ?? [],
                'en' => $descripcionProcesada['artista_posible']['en'] ?? []
            ],
            'genero_posible' => [
                'es' => $descripcionProcesada['genero_posible']['es'] ?? [],
                'en' => $descripcionProcesada['genero_posible']['en'] ?? []
            ],
            'tipo_audio' => [
                'es' => $descripcionProcesada['tipo_audio']['es'] ?? '',
                'en' => $descripcionProcesada['tipo_audio']['en'] ?? ''
            ],
            'tags_posibles' => [
                'es' => $descripcionProcesada['tags_posibles']['es'] ?? [],
                'en' => $descripcionProcesada['tags_posibles']['en'] ?? []
            ],
            'sugerencia_busqueda' => [
                'es' => $descripcionProcesada['sugerencia_busqueda']['es'] ?? [],
                'en' => $descripcionProcesada['sugerencia_busqueda']['en'] ?? []
            ]
        ];
    }

    /**
     * Actualiza los datos del algoritmo con los resultados del análisis
     */
    public function actualizarDatosAlgoritmo(int $postId, array $resultados, int $index): void
    {
        $datosAlgoritmo = get_post_meta($postId, 'datosAlgoritmo', true);

        if (!$datosAlgoritmo) {
            $datosAlgoritmo = [];
        } else {
            $datosAlgoritmo = json_decode($datosAlgoritmo, true);
            if (!is_array($datosAlgoritmo)) {
                $datosAlgoritmo = [];
            }
        }

        /* Obtener descripción guardada */
        $suffix = ($index == 1) ? '' : "_{$index}";
        $descripcionGuardada = get_post_meta($postId, "audio_descripcion{$suffix}", true);
        $descripcionData = $descripcionGuardada ? json_decode($descripcionGuardada, true) : [];

        $nuevosDatosAlgoritmo = [
            'bpm' => $resultados['bpm'] ?? '',
            'emotion' => $resultados['emotion'] ?? '',
            'key' => $resultados['key'] ?? '',
            'scale' => $resultados['scale'] ?? '',
            'descripcion_ia' => $descripcionData['descripcion_ia'] ?? [],
            'instrumentos_posibles' => $descripcionData['instrumentos_posibles'] ?? [],
            'estado_animo' => $descripcionData['estado_animo'] ?? [],
            'artista_posible' => $descripcionData['artista_posible'] ?? [],
            'genero_posible' => $descripcionData['genero_posible'] ?? [],
            'tipo_audio' => $descripcionData['tipo_audio'] ?? [],
            'tags_posibles' => $descripcionData['tags_posibles'] ?? [],
            'sugerencia_busqueda' => $descripcionData['sugerencia_busqueda'] ?? []
        ];

        $datosAlgoritmo = array_merge($datosAlgoritmo, $nuevosDatosAlgoritmo);
        update_post_meta($postId, 'datosAlgoritmo', json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE));

        $this->logger->info('audio', "Datos del algoritmo actualizados para post ID: {$postId}");
    }
}
