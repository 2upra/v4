<?php

/**
 * Servicio de procesamiento de audio
 * 
 * Maneja el procesamiento de archivos de audio:
 * - Creación de versiones ligeras (128k)
 * - Eliminación de metadatos
 * - Extracción de duración
 * - Análisis con IA (BPM, pitch, emotion, key, scale)
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class AudioProcessingService
{
    private static ?AudioProcessingService $instancia = null;
    private \Logger $logger;
    private HashService $hashService;

    /* Rutas de ejecutables */
    private const FFMPEG_PATH = '/usr/bin/ffmpeg';
    private const FFPROBE_PATH = '/usr/bin/ffprobe';
    private const PYTHON_SCRIPT_PATH = '/var/www/wordpress/wp-content/themes/2upra3v/app/python/audio.py';

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->hashService = HashService::obtenerInstancia();
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
     * Procesa un archivo de audio creando una versión ligera (128k)
     * 
     * @param int $postId ID del post
     * @param int $audioId ID del attachment de audio
     * @param int $index Índice del audio (1-30)
     * @return bool True si el procesamiento fue exitoso
     */
    public function procesarAudioLigero(int $postId, int $audioId, int $index): bool
    {
        $this->logger->info('audio', "Inicio procesarAudioLigero para Post ID: {$postId} y Audio ID: {$audioId}");

        $audioPath = get_attached_file($audioId);

        if (!$audioPath || !file_exists($audioPath)) {
            $this->logger->error('audio', "Archivo de audio no encontrado: {$audioPath}");
            return false;
        }

        $pathParts = pathinfo($audioPath);
        $basePath = $pathParts['dirname'] . '/' . $pathParts['filename'];

        /* Eliminar metadatos del archivo original */
        $this->eliminarMetadatos($audioPath);

        /* Obtener información del autor */
        $authorUsername = $this->obtenerNombreAutor($postId);
        $pageName = parse_url(home_url(), PHP_URL_HOST);

        /* Crear versión ligera (128k) con metadatos personalizados */
        $archivoLitePath = $basePath . '_128k.mp3';

        if (!$this->crearVersionLigera($audioPath, $archivoLitePath, $authorUsername, $pageName)) {
            return false;
        }

        /* Insertar en la biblioteca de medios */
        $attachIdLite = $this->insertarEnMediaLibrary($archivoLitePath, $postId);

        if (!$attachIdLite) {
            return false;
        }

        /* Guardar metadatos del archivo ligero */
        $metaKey = ($index == 1) ? 'post_audio_lite' : "post_audio_lite_{$index}";
        update_post_meta($postId, $metaKey, $attachIdLite);

        /* Extraer y guardar duración */
        $this->guardarDuracion($archivoLitePath, $postId, $index);

        /* Análisis con IA solo para el primer audio */
        if ($index === 1) {
            $this->analizarYGuardarMetasAudio($postId, $archivoLitePath, $index);
        }

        $this->logger->info('audio', "Procesamiento completado para Post ID: {$postId}");
        return true;
    }

    /**
     * Elimina los metadatos de un archivo de audio
     */
    private function eliminarMetadatos(string $audioPath): bool
    {
        $tempPath = $audioPath . '.tmp';
        $comando = sprintf(
            '%s -i %s -map_metadata -1 -c:v copy %s && mv %s %s',
            self::FFMPEG_PATH,
            escapeshellarg($audioPath),
            escapeshellarg($tempPath),
            escapeshellarg($tempPath),
            escapeshellarg($audioPath)
        );

        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->logger->warning('audio', 'Error al eliminar metadatos del archivo original', ['output' => implode("\n", $output)]);
            return false;
        }

        $this->logger->debug('audio', 'Metadatos del archivo original eliminados correctamente');
        return true;
    }

    /**
     * Obtiene el nombre de usuario del autor del post
     */
    private function obtenerNombreAutor(int $postId): string
    {
        $postAuthorId = get_post_field('post_author', $postId);
        $authorInfo = get_userdata($postAuthorId);

        if ($authorInfo) {
            return $authorInfo->user_login;
        }

        $this->logger->warning('audio', "No se pudo obtener el nombre de usuario del autor para Post ID: {$postId}");
        return 'Desconocido';
    }

    /**
     * Crea una versión ligera (128k) del audio con metadatos personalizados
     */
    private function crearVersionLigera(string $audioPath, string $outputPath, string $author, string $comment): bool
    {
        $comando = sprintf(
            '%s -i %s -b:a 128k -metadata author=%s -metadata comment=%s %s',
            self::FFMPEG_PATH,
            escapeshellarg($audioPath),
            escapeshellarg($author),
            escapeshellarg($comment),
            escapeshellarg($outputPath)
        );

        $this->logger->debug('audio', "Ejecutando comando para crear audio ligero: {$comando}");
        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->logger->error('audio', 'Error al procesar audio ligero', ['output' => implode("\n", $output)]);
            return false;
        }

        $this->logger->info('audio', 'Audio ligero creado exitosamente con metadatos');
        return true;
    }

    /**
     * Inserta un archivo en la biblioteca de medios de WordPress
     */
    private function insertarEnMediaLibrary(string $filePath, int $postId): ?int
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $filetype = wp_check_filetype(basename($filePath), null);

        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filePath)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachId = wp_insert_attachment($attachment, $filePath, $postId);

        if (is_wp_error($attachId)) {
            $this->logger->error('audio', 'Error al insertar el adjunto ligero', ['error' => $attachId->get_error_message()]);
            return null;
        }

        $attachData = wp_generate_attachment_metadata($attachId, $filePath);
        wp_update_attachment_metadata($attachId, $attachData);

        $this->logger->debug('audio', "ID de adjunto ligero: {$attachId}");
        return $attachId;
    }

    /**
     * Extrae y guarda la duración del audio
     */
    private function guardarDuracion(string $filePath, int $postId, int $index): void
    {
        $comando = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            self::FFPROBE_PATH,
            escapeshellarg($filePath)
        );

        $durationInSeconds = trim(shell_exec($comando));

        if (is_numeric($durationInSeconds)) {
            $durationInSeconds = (float)$durationInSeconds;
            $durationFormatted = floor($durationInSeconds / 60) . ':' . str_pad(floor($durationInSeconds % 60), 2, '0', STR_PAD_LEFT);
            update_post_meta($postId, "audio_duration_{$index}", $durationFormatted);
            $this->logger->debug('audio', "Duración del audio (formateada): {$durationFormatted}");
        } else {
            $this->logger->warning('audio', "Duración del audio no válida para el archivo {$filePath}");
        }
    }

    /**
     * Analiza el audio con scripts de Python e IA y guarda los metadatos
     * 
     * @param int $postId ID del post
     * @param string $audioPath Ruta del archivo de audio
     * @param int $index Índice del audio
     * @param string|null $nombreArchivo Nombre del archivo (opcional, para contexto IA)
     * @param string|null $carpeta Nombre de la carpeta (opcional, para contexto IA)
     * @param string|null $carpetaAbuela Nombre de la carpeta abuela (opcional, para contexto IA)
     */
    public function analizarYGuardarMetasAudio(
        int $postId,
        string $audioPath,
        int $index,
        ?string $nombreArchivo = null,
        ?string $carpeta = null,
        ?string $carpetaAbuela = null
    ): void {
        /* Ejecutar script Python para análisis de audio */
        $resultados = $this->ejecutarAnalisisPython($audioPath);

        if ($resultados) {
            $this->guardarResultadosAnalisis($postId, $resultados, $index);
        }

        /* Generar descripción con IA */
        $this->generarDescripcionIA($postId, $audioPath, $index, $nombreArchivo, $carpeta, $carpetaAbuela);

        /* Actualizar datos del algoritmo con los resultados */
        $this->actualizarDatosAlgoritmo($postId, $resultados ?? [], $index);

        /* Marcar como procesado por IA */
        update_post_meta($postId, 'flashIA', true);

        $this->logger->info('audio', "Metadatos de 'datosAlgoritmo' actualizados para el post ID: {$postId}");
    }

    /**
     * Ejecuta el script Python de análisis de audio
     */
    private function ejecutarAnalisisPython(string $audioPath): ?array
    {
        $pythonCommand = escapeshellcmd("python3 " . self::PYTHON_SCRIPT_PATH . " \"{$audioPath}\"");
        $this->logger->debug('audio', "Ejecutando comando de Python: {$pythonCommand}");

        exec($pythonCommand, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->logger->error('audio', "Error al ejecutar el script de Python. Código de retorno: {$returnVar}", ['output' => implode("\n", $output)]);
            return null;
        }

        $resultadosPath = $audioPath . '_resultados.json';

        if (file_exists($resultadosPath)) {
            $resultados = json_decode(file_get_contents($resultadosPath), true);
            if ($resultados && is_array($resultados)) {
                return $resultados;
            }
            $this->logger->warning('audio', 'El archivo de resultados JSON no contiene datos válidos');
        } else {
            $this->logger->warning('audio', "No se encontró el archivo de resultados en {$resultadosPath}");
        }

        return null;
    }

    /**
     * Guarda los resultados del análisis de Python como metadatos
     */
    private function guardarResultadosAnalisis(int $postId, array $resultados, int $index): void
    {
        $suffix = ($index == 1) ? '' : "_{$index}";

        update_post_meta($postId, "audio_bpm{$suffix}", $resultados['bpm'] ?? '');
        update_post_meta($postId, "audio_pitch{$suffix}", $resultados['pitch'] ?? '');
        update_post_meta($postId, "audio_emotion{$suffix}", $resultados['emotion'] ?? '');
        update_post_meta($postId, "audio_key{$suffix}", $resultados['key'] ?? '');
        update_post_meta($postId, "audio_scale{$suffix}", $resultados['scale'] ?? '');
        update_post_meta($postId, "audio_strength{$suffix}", $resultados['strength'] ?? '');
    }

    /**
     * Genera descripción del audio usando IA
     */
    private function generarDescripcionIA(
        int $postId,
        string $audioPath,
        int $index,
        ?string $nombreArchivo,
        ?string $carpeta,
        ?string $carpetaAbuela
    ): void {
        $postContent = get_post_field('post_content', $postId);

        if (!$postContent) {
            $this->logger->warning('audio', "No se pudo obtener el contenido del post ID: {$postId}");
            return;
        }

        $prompt = $this->construirPromptIA($postId, $postContent, $nombreArchivo, $carpeta, $carpetaAbuela);

        if (!function_exists('generarDescripcionIA')) {
            $this->logger->warning('audio', 'Función generarDescripcionIA no disponible');
            return;
        }

        $descripcion = generarDescripcionIA($audioPath, $prompt);

        if ($descripcion) {
            $this->procesarYGuardarDescripcionIA($postId, $descripcion, $index);
        }
    }

    /**
     * Construye el prompt para la IA
     */
    private function construirPromptIA(
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
    private function procesarYGuardarDescripcionIA(int $postId, string $descripcion, int $index): void
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
            $nuevosDatos = $this->estructurarDatosDescripcion($descripcionProcesada);
            update_post_meta($postId, "audio_descripcion{$suffix}", json_encode($nuevosDatos, JSON_UNESCAPED_UNICODE));
            $this->logger->info('audio', "Descripción del audio guardada para el post ID: {$postId}");
        } else {
            $this->logger->error('audio', "'descripcion_ia' no está presente o tiene una estructura incorrecta");
        }
    }

    /**
     * Estructura los datos de descripción en el formato esperado
     */
    private function estructurarDatosDescripcion(array $descripcionProcesada): array
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
    private function actualizarDatosAlgoritmo(int $postId, array $resultados, int $index): void
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
    }
}
