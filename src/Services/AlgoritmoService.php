<?php

namespace Kamples\Services;

/**
 * Servicio del algoritmo de recomendación de posts.
 * 
 * Calcula puntuaciones personalizadas para cada post basándose en:
 * - Usuarios seguidos
 * - Intereses del usuario
 * - Likes e interacciones
 * - Similitud de contenido
 * - Decaimiento temporal
 *
 * @since 1.0.0
 */
class AlgoritmoService
{
    private static ?AlgoritmoService $instancia = null;
    private ?\Logger $logger = null;

    /**
     * Constructor privado (Singleton).
     */
    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia singleton del servicio.
     *
     * @return self
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Calcula el feed personalizado para un usuario.
     *
     * @param int $userId ID del usuario
     * @param string $identifier Identificador de búsqueda
     * @param int|null $similarTo ID del post de referencia para similitud
     * @param string|null $tipoUsuario Tipo de usuario (Fan, Artista)
     * @return array Puntuaciones de posts ordenadas
     */
    public function calcularFeedPersonalizado(
        int $userId,
        string $identifier = '',
        ?int $similarTo = null,
        ?string $tipoUsuario = null
    ): array {
        $tiempoInicio = microtime(true);
        $this->log('debug', "Inicio calcularFeedPersonalizado - Usuario: $userId, Identifier: $identifier, SimilarTo: $similarTo, TipoUsuario: $tipoUsuario");

        $feedService = FeedService::obtenerInstancia();
        $datos = $feedService->obtenerDatosFeedConCache($userId);

        if (empty($datos)) {
            $this->log('debug', 'Datos vacíos, finalizando');
            return [];
        }

        $usuario = $this->obtenerUsuario($userId);
        if (empty($usuario)) {
            $this->log('debug', 'Usuario no encontrado, finalizando');
            return [];
        }

        $vistas = $this->obtenerVistas($userId);
        $esAdmin = $this->esUsuarioAdmin($usuario);
        $decaimiento = $this->calcularDecaimiento($datos);

        $puntos = $this->calcularPuntosBatch(
            $datos,
            $esAdmin,
            $vistas,
            $identifier,
            $similarTo,
            $userId,
            $decaimiento,
            $tipoUsuario
        );

        if (empty($puntos)) {
            $this->log('debug', 'No hay puntos calculados, finalizando');
            return [];
        }

        $puntos = $this->ordenarYLimitarPuntos($puntos);

        $duracion = microtime(true) - $tiempoInicio;
        $this->log('debug', "Fin calcularFeedPersonalizado - Duración: {$duracion}s");

        return $puntos;
    }

    /**
     * Obtiene los datos de un usuario.
     *
     * @param int $userId ID del usuario
     * @return \WP_User|array Usuario o array vacío
     */
    private function obtenerUsuario(int $userId)
    {
        $usuario = get_userdata($userId);
        if (!$usuario || !is_object($usuario)) {
            return [];
        }
        return $usuario;
    }

    /**
     * Obtiene las vistas del usuario.
     *
     * @param int $userId ID del usuario
     * @return array Vistas procesadas
     */
    private function obtenerVistas(int $userId): array
    {
        return $this->obtenerYProcesarVistasPosts($userId);
    }

    /**
     * Verifica si el usuario es administrador.
     *
     * @param \WP_User $usuario Usuario
     * @return bool
     */
    private function esUsuarioAdmin($usuario): bool
    {
        return in_array('administrator', (array)$usuario->roles);
    }

    /**
     * Pre-calcula los factores de decaimiento temporal para los posts.
     *
     * @param array $datos Datos del feed
     * @return array Factores de decaimiento por días
     */
    private function calcularDecaimiento(array $datos): array
    {
        $actual = current_time('timestamp');
        $decaimiento = [];

        if (!isset($datos['author_results'])) {
            return $decaimiento;
        }

        foreach ($datos['author_results'] as $post) {
            $fecha = is_string($post->post_date) ? strtotime($post->post_date) : $post->post_date;
            $dias = (int)floor(($actual - $fecha) / (3600 * 24));

            if (!isset($decaimiento[$dias])) {
                $decaimiento[$dias] = $this->getDecayFactor($dias);
            }
        }

        return $decaimiento;
    }

    /**
     * Calcula puntos para un lote de posts.
     *
     * @param array $datos Datos del feed
     * @param bool $esAdmin Si el usuario es admin
     * @param array $vistas Vistas del usuario
     * @param string $identifier Identificador de búsqueda
     * @param int|null $similarTo ID del post de referencia
     * @param int $userId ID del usuario
     * @param array $decaimiento Factores de decaimiento
     * @param string|null $tipoUsuario Tipo de usuario
     * @return array Puntuaciones por post
     */
    private function calcularPuntosBatch(
        array $datos,
        bool $esAdmin,
        array $vistas,
        string $identifier,
        ?int $similarTo,
        int $userId,
        array $decaimiento,
        ?string $tipoUsuario
    ): array {
        if (!isset($datos['author_results'])) {
            return [];
        }

        $actual = current_time('timestamp');
        $puntos = [];

        foreach ($datos['author_results'] as $id => $post) {
            try {
                $pFinal = $this->calcularPuntosParaPost(
                    (int)$id,
                    $post,
                    $datos,
                    $esAdmin,
                    $vistas,
                    $identifier,
                    $similarTo,
                    $actual,
                    $decaimiento,
                    $tipoUsuario
                );

                if (is_numeric($pFinal) && $pFinal > 0) {
                    $puntos[$id] = max($pFinal, 0);
                }
            } catch (\Exception $e) {
                $this->log('error', "Error calculando puntos para post $id: " . $e->getMessage());
                continue;
            }
        }

        return $puntos;
    }

    /**
     * Ordena y limita los puntos al máximo permitido.
     *
     * @param array $puntos Puntuaciones
     * @return array Puntuaciones ordenadas y limitadas
     */
    private function ordenarYLimitarPuntos(array $puntos): array
    {
        if (!empty($puntos)) {
            arsort($puntos);
            $limite = defined('POSTINLIMIT') ? POSTINLIMIT : 1000;
            $puntos = array_slice($puntos, 0, $limite, true);
        }
        return $puntos;
    }

    /**
     * Calcula puntos para un post individual.
     *
     * @param int $postId ID del post
     * @param object $postData Datos del post
     * @param array $datos Datos del feed
     * @param bool $esAdmin Si el usuario es admin
     * @param array $vistasPosts Vistas del usuario
     * @param string $identifier Identificador de búsqueda
     * @param int|null $similarTo ID del post de referencia
     * @param int $actualTimestamp Timestamp actual
     * @param array $decaimientoF Factores de decaimiento
     * @param string|null $tipoUsuario Tipo de usuario
     * @return float Puntuación final
     */
    private function calcularPuntosParaPost(
        int $postId,
        object $postData,
        array $datos,
        bool $esAdmin,
        array $vistasPosts,
        string $identifier,
        ?int $similarTo,
        int $actualTimestamp,
        array $decaimientoF,
        ?string $tipoUsuario
    ): float {
        $autorId = $postData->post_author;
        $postDate = $postData->post_date;

        $postTimestamp = is_string($postDate) ? strtotime($postDate) : $postDate;
        $diasPubli = (int)floor(($actualTimestamp - $postTimestamp) / (3600 * 24));
        $factorTiempo = $decaimientoF[$diasPubli] ?? $this->getDecayFactor($diasPubli);

        /* Puntos por usuarios seguidos */
        $siguiendo = $datos['siguiendo'] ?? [];
        $pUsuario = in_array($autorId, $siguiendo) ? 20 : 0;

        /* Puntos por intereses */
        $pIntereses = $this->calcularPuntosIntereses($postId, $datos);

        /* Puntos por identifier (búsqueda) */
        $pIdentifier = 0;
        if (!empty($identifier)) {
            $pIdentifier = $this->calcularPuntosIdentifier($postId, $identifier, $datos);
        }

        /* Puntos por similaridad */
        $pSimilarTo = 0;
        if (!empty($similarTo)) {
            $pSimilarTo = $this->calcularPuntosSimilarTo($postId, $similarTo, $datos);
        }

        /* Puntos por likes */
        $likesPorPost = $datos['likes_by_post'] ?? [];
        if (!is_array($likesPorPost)) {
            $likesPorPost = [];
        }
        $likesData = $likesPorPost[$postId] ?? ['like' => 0, 'favorito' => 0, 'no_me_gusta' => 0];
        $puntosLikes = 5 + $likesData['like'] + 10 * $likesData['favorito'] - ($likesData['no_me_gusta'] * 10);

        /* Verificar estados del post */
        $metaData = $datos['meta_data'] ?? [];
        $metaVerificado = isset($metaData[$postId]['Verificado']) && ($metaData[$postId]['Verificado'] === '1');
        $metaPostAut = isset($metaData[$postId]['postAut']) && ($metaData[$postId]['postAut'] === '1');

        /* Puntos por target de audiencia (Artista/Fan) */
        $metaRoles = $datos['meta_roles'] ?? [];
        if (!isset($metaRoles[$postId]) || !is_array($metaRoles[$postId])) {
            $metaRoles[$postId] = ['artista' => false, 'fan' => false];
        }

        $pArtistaFan = 0;
        if (empty($similarTo)) {
            $postParaFans = !empty($metaRoles[$postId]['fan']);

            if ($tipoUsuario === 'Fan') {
                $pArtistaFan = $postParaFans ? 999 : 0;
            } elseif ($tipoUsuario === 'Artista') {
                $pArtistaFan = $postParaFans ? -50 : 0;
            }
        }

        /* Calcular puntos finales base */
        $pFinal = $this->calcularPuntosFinales(
            $pUsuario,
            $pIntereses + $pSimilarTo + $pArtistaFan,
            $puntosLikes,
            $metaVerificado,
            $metaPostAut,
            $esAdmin
        );

        $pFinal += $pIdentifier;

        /* Reducción por vistas */
        if (isset($vistasPosts[$postId])) {
            $v = $vistasPosts[$postId]['count'];
            $rPuntos = $v * 10;
            $pFinal -= $rPuntos;
        }

        /* Aplicar aleatoriedad y factor de tiempo */
        $aleatoriedad = mt_rand(0, 20);
        $ajusteExtra = mt_rand(-50, 50);
        $pFinal = ($pFinal * (1 + ($aleatoriedad / 100))) * $factorTiempo;
        $pFinal += $ajusteExtra;

        return $pFinal;
    }

    /**
     * Calcula puntos basados en los intereses del usuario.
     *
     * @param int $postId ID del post
     * @param array $datos Datos del feed
     * @return int Puntos por intereses
     */
    private function calcularPuntosIntereses(int $postId, array $datos): int
    {
        $pIntereses = 0;

        if (
            !isset($datos['datosAlgoritmo'][$postId]) ||
            !isset($datos['datosAlgoritmo'][$postId]->meta_value)
        ) {
            return $pIntereses;
        }

        $datosAlgoritmo = json_decode($datos['datosAlgoritmo'][$postId]->meta_value, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosAlgoritmo)) {
            return $pIntereses;
        }

        $interesesUsuario = $datos['interesesUsuario'] ?? [];

        foreach ($datosAlgoritmo as $key => $value) {
            if (is_array($value)) {
                foreach (['es', 'en'] as $lang) {
                    if (isset($value[$lang]) && is_array($value[$lang])) {
                        foreach ($value[$lang] as $item) {
                            if (isset($interesesUsuario[$item])) {
                                $pIntereses += 10 + $interesesUsuario[$item]->intensity;
                            }
                        }
                    }
                }
            } elseif (!empty($value) && isset($interesesUsuario[$value])) {
                $pIntereses += 10 + $interesesUsuario[$value]->intensity;
            }
        }

        return $pIntereses;
    }

    /**
     * Calcula puntos basados en un identificador de búsqueda.
     *
     * @param int $postId ID del post
     * @param string|array $identifier Identificador(es)
     * @param array $datos Datos del feed
     * @return int Puntos totales
     */
    private function calcularPuntosIdentifier(int $postId, $identifier, array $datos): int
    {
        $resumen = [
            'matches' => ['content' => 0, 'data' => 0],
            'puntos' => ['contenido' => 0, 'datos' => 0, 'bonus' => 0, 'total' => 0]
        ];

        /* Normalizar identificadores */
        $identifiers = is_array($identifier)
            ? array_unique(array_map('strtolower', $identifier))
            : array_unique(preg_split('/\s+/', strtolower($identifier), -1, PREG_SPLIT_NO_EMPTY));

        $totalIds = count($identifiers);
        if ($totalIds === 0) {
            return 0;
        }

        /* Obtener contenido del post */
        $postContent = !empty($datos['post_content'][$postId])
            ? strtolower($datos['post_content'][$postId])
            : '';

        /* Obtener datos del algoritmo */
        $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$postId]->meta_value)
            ? json_decode($datos['datosAlgoritmo'][$postId]->meta_value, true)
            : [];

        /* Obtener nombre original */
        $nombreOriginal = !empty($datos['nombreOriginal'][$postId])
            ? strtolower($datos['nombreOriginal'][$postId])
            : '';

        /* Calcular coincidencias en contenido y nombre original */
        foreach ($identifiers as $id) {
            $found = false;

            if (strpos($postContent, $id) !== false || strpos($nombreOriginal, $id) !== false) {
                $resumen['matches']['content']++;
                $found = true;
            }

            if (!$found) {
                /* Comparación difusa */
                foreach (array_merge(explode(" ", $postContent), explode(" ", $nombreOriginal)) as $word) {
                    similar_text($id, $word, $percent);
                    if ($percent > 75) {
                        $resumen['matches']['content']++;
                        break;
                    }
                }
            }
        }

        /* Procesar datosAlgoritmo */
        $postWords = [];
        if (is_array($datosAlgoritmo)) {
            foreach ($datosAlgoritmo as $val) {
                if (is_array($val)) {
                    foreach (['es', 'en'] as $lang) {
                        if (isset($val[$lang]) && is_array($val[$lang])) {
                            foreach ($val[$lang] as $item) {
                                $postWords[strtolower($item)] = true;
                            }
                        }
                    }
                } elseif (!empty($val)) {
                    $postWords[strtolower($val)] = true;
                }
            }
        }

        /* Calcular coincidencias en datos */
        foreach ($identifiers as $id) {
            if (isset($postWords[$id])) {
                $resumen['matches']['data']++;
            } else {
                foreach (array_keys($postWords) as $word) {
                    similar_text($id, $word, $percent);
                    if ($percent > 75) {
                        $resumen['matches']['data']++;
                        break;
                    }
                }
            }
        }

        /* Calcular puntos */
        $puntosBaseContenido = 1000;
        $puntosBaseDatos = 250;
        $bonus = 2000;

        $resumen['puntos']['contenido'] = $resumen['matches']['content'] * $puntosBaseContenido;
        $resumen['puntos']['datos'] = $resumen['matches']['data'] * $puntosBaseDatos;

        /* Aplicar bonus */
        if ($resumen['matches']['content'] === $totalIds) {
            $resumen['puntos']['bonus'] = $bonus;
        } elseif ($resumen['matches']['data'] === $totalIds) {
            $resumen['puntos']['bonus'] = (int)($bonus * 0.5);
        }

        $resumen['puntos']['total'] = $resumen['puntos']['contenido'] +
            $resumen['puntos']['datos'] +
            $resumen['puntos']['bonus'];

        return $resumen['puntos']['total'];
    }

    /**
     * Calcula puntos de similitud entre dos posts.
     *
     * @param int $postId ID del post a evaluar
     * @param int $similarTo ID del post de referencia
     * @param array $datos Datos del feed
     * @return float Puntos de similitud
     */
    private function calcularPuntosSimilarTo(int $postId, int $similarTo, array $datos): float
    {
        $contenidoPost1 = isset($datos['post_content'][$postId])
            ? strtolower($datos['post_content'][$postId])
            : '';
        $contenidoPost2 = isset($datos['post_content'][$similarTo])
            ? strtolower($datos['post_content'][$similarTo])
            : '';

        $datosAlgoritmo1 = isset($datos['datosAlgoritmo'][$postId]->meta_value)
            ? $this->procesarMetaValue($datos['datosAlgoritmo'][$postId]->meta_value)
            : [];

        $datosAlgoritmo2 = isset($datos['datosAlgoritmo'][$similarTo]->meta_value)
            ? $this->procesarMetaValue($datos['datosAlgoritmo'][$similarTo]->meta_value)
            : $this->procesarMetaValue(get_post_meta($similarTo, 'datosAlgoritmo', true));

        $wordsPost1 = array_merge(
            $this->extractWordsFromDatosAlgoritmo($datosAlgoritmo1),
            $this->extractWordsFromContent($contenidoPost1)
        );

        $wordsPost2 = array_merge(
            $this->extractWordsFromDatosAlgoritmo($datosAlgoritmo2),
            $this->extractWordsFromContent($contenidoPost2)
        );

        if (empty($wordsPost1) || empty($wordsPost2)) {
            return 0;
        }

        $set1 = array_unique($wordsPost1);
        $set2 = array_unique($wordsPost2);
        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        $contentWeight = 1.5;
        $contenidoMatches = count(array_intersect(
            $this->extractWordsFromContent($contenidoPost1),
            $this->extractWordsFromContent($contenidoPost2)
        ));

        $similarity = (count($intersection) + $contenidoMatches * $contentWeight) / count($union);

        return $similarity * 150;
    }

    /**
     * Calcula los puntos finales combinados.
     *
     * @param int $pUsuario Puntos por usuario seguido
     * @param int $pIntereses Puntos por intereses
     * @param int $puntosLikes Puntos por likes
     * @param bool $metaVerificado Si el post está verificado
     * @param bool $metaPostAut Si es post automático
     * @param bool $esAdmin Si el usuario es admin
     * @return float Puntos finales
     */
    private function calcularPuntosFinales(
        int $pUsuario,
        int $pIntereses,
        int $puntosLikes,
        bool $metaVerificado,
        bool $metaPostAut,
        bool $esAdmin
    ): float {
        $base = $pUsuario + $pIntereses + $puntosLikes;

        if ($esAdmin) {
            if (!$metaVerificado && $metaPostAut) {
                return $base * 1;
            } elseif ($metaVerificado && !$metaPostAut) {
                return $base * 1;
            }
        } else {
            if ($metaVerificado && $metaPostAut) {
                return $base * 4;
            } elseif (!$metaVerificado && $metaPostAut) {
                return $base * 1;
            }
        }

        return $base;
    }

    /**
     * Obtiene el factor de decaimiento para un número de días.
     *
     * @param int $days Días desde la publicación
     * @param bool $useDecay Si usar decaimiento
     * @return float Factor de decaimiento
     */
    public function getDecayFactor(int $days, bool $useDecay = false): float
    {
        static $decaimiento = [];
        static $useDecayStatic = false;

        if ($useDecay) {
            $useDecayStatic = $useDecay;
        }

        if (!$useDecayStatic) {
            return 1.0;
        }

        if (empty($decaimiento)) {
            for ($d = 0; $d <= 365; $d++) {
                $decaimiento[$d] = pow(0.99, $d);
            }
        }

        $days = min(max(0, $days), 365);
        return $decaimiento[$days];
    }

    /**
     * Procesa un valor de meta (JSON o array).
     *
     * @param mixed $metaValue Valor de meta
     * @return array Valor procesado
     */
    private function procesarMetaValue($metaValue): array
    {
        if (is_array($metaValue)) {
            return $metaValue;
        }
        if (is_string($metaValue)) {
            $decoded = json_decode($metaValue, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Extrae palabras de los datos del algoritmo.
     *
     * @param array $datosAlgoritmo Datos del algoritmo
     * @return array Palabras extraídas
     */
    private function extractWordsFromDatosAlgoritmo(array $datosAlgoritmo): array
    {
        $words = [];

        foreach ($datosAlgoritmo as $value) {
            if (is_array($value)) {
                foreach (['es', 'en'] as $lang) {
                    if (isset($value[$lang]) && is_array($value[$lang])) {
                        foreach ($value[$lang] as $item) {
                            $words[] = strtolower($item);
                        }
                    }
                }
            } elseif (!empty($value)) {
                $words[] = strtolower($value);
            }
        }

        return $words;
    }

    /**
     * Extrae palabras de un contenido de texto.
     *
     * @param string $content Contenido
     * @return array Palabras stemizadas
     */
    private function extractWordsFromContent(string $content): array
    {
        $words = preg_split('/\s+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);
        return array_map([$this, 'stemWord'], $words);
    }

    /**
     * Aplica stemming simple a una palabra.
     *
     * @param string $word Palabra
     * @return string Palabra stemizada
     */
    private function stemWord(string $word): string
    {
        return preg_replace('/(s|ed|ing)$/', '', $word);
    }

    /**
     * Obtiene y procesa las vistas de posts del usuario.
     *
     * @param int $userId ID del usuario
     * @return array Vistas procesadas
     */
    private function obtenerYProcesarVistasPosts(int $userId): array
    {
        if (!function_exists('obtenerVistasPosts')) {
            return [];
        }

        $vistasPosts = obtenerVistasPosts($userId);
        $resultado = [];

        if (!empty($vistasPosts)) {
            foreach ($vistasPosts as $postId => $viewData) {
                $resultado[$postId] = [
                    'count' => $viewData['count'],
                    'last_view' => date('Y-m-d H:i:s', $viewData['last_view']),
                ];
            }
        }

        return $resultado;
    }

    /**
     * Registra un mensaje en el log.
     *
     * @param string $nivel Nivel del log
     * @param string $mensaje Mensaje
     * @return void
     */
    private function log(string $nivel, string $mensaje): void
    {
        if ($this->logger) {
            $this->logger->$nivel('algoritmo', $mensaje);
        }
    }

    /**
     * Recalcula feed similar para posts en background (Cron job).
     */
    public function recalcularSimilarToFeed(): void
    {
        // Constantes internas
        $LOCK_KEY = 'similar_to_process_lock';
        $MAX_LOCK_TIME = 300;
        $PROGRESS_OPTION = 'similar_to_progress';
        $CACHED_COUNT_OPTION = 'similar_to_cached_count';
        $STOP_UNTIL_OPTION = 'similar_to_stop_until';
        $CONSECUTIVE_LIMIT = 100;
        $STOP_DURATION = 6 * HOUR_IN_SECONDS;

        // Verificar detención
        $stopUntil = get_option($STOP_UNTIL_OPTION, 0);
        if ($stopUntil && time() < $stopUntil) {
            return;
        } elseif ($stopUntil && time() >= $stopUntil) {
            delete_option($STOP_UNTIL_OPTION);
            update_option($CACHED_COUNT_OPTION, 0);
        }

        // Lock
        $cacheService = \Kamples\Services\CacheService::obtenerInstancia(); // Asumiendo CacheService
        // Ojo, en legacy usaba 'obtenerCache/guardarCache' wrappers.
        // Si CacheService tiene metodos estaticos o instancia, usarlo.

        // Simulado con methods temporales si no tengo acceso fácil a CacheService aquí o usar transients
        $lockTime = get_transient($LOCK_KEY);
        if ($lockTime && (time() - $lockTime < $MAX_LOCK_TIME)) {
            return;
        }
        set_transient($LOCK_KEY, time(), $MAX_LOCK_TIME);

        try {
            $lastProcessedId = (int)get_option($PROGRESS_OPTION, 0);
            global $wpdb;

            while (true) {
                // Obtener siguiente post
                $query = $wpdb->prepare(
                    "SELECT p.ID FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type = 'social_post'
                    AND p.post_status = 'publish'
                    AND p.ID > %d
                    AND pm.meta_key = 'datosAlgoritmo'
                    ORDER BY p.ID ASC LIMIT 1",
                    $lastProcessedId
                );

                $postId = $wpdb->get_var($query);

                if (!$postId) {
                    update_option($PROGRESS_OPTION, 0);
                    update_option($CACHED_COUNT_OPTION, 0);
                    break;
                }

                $cacheKey = "similar_to_$postId";
                // Verificar si existe cache
                if (get_transient($cacheKey)) { // Uso transient como cache simple
                    update_option($PROGRESS_OPTION, $postId);
                    $cachedCount = (int)get_option($CACHED_COUNT_OPTION, 0) + 1;
                    update_option($CACHED_COUNT_OPTION, $cachedCount);

                    if ($cachedCount >= $CONSECUTIVE_LIMIT) {
                        update_option($STOP_UNTIL_OPTION, time() + $STOP_DURATION);
                        update_option($CACHED_COUNT_OPTION, 0);
                        break;
                    }
                    $lastProcessedId = $postId;
                } else {
                    // Calcular 
                    $postsSimilares = $this->calcularFeedPersonalizado(44, '', $postId); // 44 es usuario sistema/default? 
                    if ($postsSimilares) {
                        set_transient($cacheKey, $postsSimilares, 15 * DAY_IN_SECONDS);
                    }
                    update_option($PROGRESS_OPTION, $postId);
                    update_option($CACHED_COUNT_OPTION, 0);
                    break; // Solo uno por ejecución
                }
            }
        } catch (\Exception $e) {
            $this->log('error', "Error en recalcularSimilarToFeed: " . $e->getMessage());
        } finally {
            delete_transient($LOCK_KEY);
        }
    }
}
