<?php

namespace Kamples\Services\Contenido;

use Kamples\Services\Core\CacheService;
use Kamples\Services\Feed\FeedService;

/**
 * Servicio de gestión de ideas basadas en colecciones.
 * 
 * Procesa posts similares a los samples de una colección
 * para generar recomendaciones personalizadas ("ideas").
 *
 * @since 1.0.0
 */
class IdeaService
{
    private CacheService $cacheService;
    private FeedService $feedService;
    private static ?IdeaService $instancia = null;

    /**
     * Constructor del servicio.
     */
    public function __construct()
    {
        $this->cacheService = CacheService::obtenerInstancia('ideas');
        $this->feedService = FeedService::obtenerInstancia();
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
     * Maneja la obtención de ideas para una colección con cache.
     *
     * @param array $args Argumentos (debe incluir 'colec' y 'post_type')
     * @param int $paged Número de página
     * @return array|false Query args configurados o false si falla
     */
    public function manejar(array $args, int $paged): array|false
    {
        $userId = get_current_user_id();
        $cacheKey = 'idea_' . $userId . '_' . md5(json_encode($args) . '_paged_' . $paged);

        $cachedData = $this->cacheService->obtener($cacheKey);
        if ($cachedData !== false) {
            $this->log('debug', "Cargando ideas desde caché para usuario {$userId}");
            return $cachedData;
        }

        $this->log('debug', "Procesando ideas desde BD para usuario {$userId}");
        $queryArgs = $this->procesar($args, $paged);

        if (!$queryArgs) {
            return false;
        }

        /* Guardamos la clave de cache en lista del usuario */
        $cacheMasterKey = 'cache_idea_user_' . $userId;
        $cacheKeys = $this->cacheService->obtener($cacheMasterKey) ?: [];
        $cacheKeys[] = $cacheKey;
        $this->cacheService->guardar($cacheMasterKey, $cacheKeys, 300);

        /* Guardar resultados con expiración de 5 minutos */
        $this->cacheService->guardar($cacheKey, $queryArgs, 300);

        return $queryArgs;
    }

    /**
     * Procesa las ideas para una colección.
     *
     * Obtiene los samples de la colección, busca posts similares
     * para cada uno y los ordena por puntuación de vistas.
     *
     * @param array $args Argumentos de la query
     * @param int $paged Número de página
     * @return array|false Query args o false si falla
     */
    public function procesar(array $args, int $paged): array|false
    {
        try {
            if (empty($args['colec']) || !is_numeric($args['colec'])) {
                return false;
            }

            $colecId = intval($args['colec']);
            $samplesMeta = get_post_meta($colecId, 'samples', true);

            if (!is_array($samplesMeta)) {
                $samplesMeta = maybe_unserialize($samplesMeta);
            }

            if (!is_array($samplesMeta)) {
                return false;
            }

            $allSimilarPosts = $this->obtenerPostsSimilares($samplesMeta);

            if (empty($allSimilarPosts)) {
                return false;
            }

            /* Eliminar duplicados y limitar a 620 posts */
            $allSimilarPosts = array_unique($allSimilarPosts);
            if (count($allSimilarPosts) > 620) {
                $allSimilarPosts = array_slice($allSimilarPosts, 0, 620);
            }

            /* Aplicar puntuación y ordenamiento */
            $postsScored = $this->asignarPuntuacionPorVistas($allSimilarPosts);
            $postsSorted = $this->aplicarAleatoriedad($postsScored);

            $queryArgs = [
                'post_type'      => $args['post_type'] ?? 'social_post',
                'post__in'       => $postsSorted,
                'orderby'        => 'post__in',
                'posts_per_page' => 12,
                'paged'          => $paged,
            ];

            return $queryArgs;
        } catch (\Exception $e) {
            $this->log('error', "Error procesando ideas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene posts similares para todos los samples.
     *
     * @param array $samplesMeta IDs de los samples
     * @return array IDs de posts similares
     */
    private function obtenerPostsSimilares(array $samplesMeta): array
    {
        $allSimilarPosts = [];
        $feedUserId = 44; // Usuario fallback para calcular feed

        foreach ($samplesMeta as $postId) {
            $similarCacheKey = "similar_to_{$postId}";
            $cachedSimilars = $this->cacheService->obtener($similarCacheKey);

            if ($cachedSimilars) {
                arsort($cachedSimilars);
                $postsSimilares = array_keys($cachedSimilars);
            } else {
                /* Calcular posts similares usando el FeedService */
                $postsSimilares = $this->feedService->calcularFeed(
                    $feedUserId,
                    '',
                    (string)$postId
                );

                if ($postsSimilares && is_array($postsSimilares)) {
                    $postsSimilaresIds = array_keys($postsSimilares);
                    $this->cacheService->guardar(
                        $similarCacheKey,
                        $postsSimilares,
                        15 * DAY_IN_SECONDS
                    );
                    $postsSimilares = $postsSimilaresIds;
                } else {
                    continue;
                }
            }

            /* Excluir repetidos y los mismos samples */
            $postsSimilares = array_diff(
                $postsSimilares,
                [$postId],
                $samplesMeta,
                $allSimilarPosts
            );

            /* Limitar a 10 posts similares por sample */
            $postsSimilares = array_slice($postsSimilares, 0, 10);

            $allSimilarPosts = array_merge($allSimilarPosts, $postsSimilares);
        }

        return $allSimilarPosts;
    }

    /**
     * Asigna puntuación a los posts basándose en las vistas del usuario.
     *
     * Posts no vistos tienen mayor puntuación.
     *
     * @param array $postIds IDs de los posts
     * @return array Posts con puntuación [post_id => score]
     */
    private function asignarPuntuacionPorVistas(array $postIds): array
    {
        $userId = get_current_user_id();
        $vistasUsuario = get_user_meta($userId, 'vistas_posts', true);
        $postScores = [];

        if (!$vistasUsuario || !is_array($vistasUsuario)) {
            $vistasUsuario = [];
        }

        foreach ($postIds as $postId) {
            if (isset($vistasUsuario[$postId])) {
                /* Posts vistos: menor puntuación según número de vistas */
                $score = 1 / (1 + $vistasUsuario[$postId]['count']);
            } else {
                /* Posts no vistos: máxima puntuación */
                $score = 2;
            }

            $postScores[$postId] = $score;
        }

        arsort($postScores);
        return $postScores;
    }

    /**
     * Aplica aleatoriedad parcial al ordenamiento.
     *
     * Randomiza el 20% de los posts para agregar variedad.
     *
     * @param array $postsScored Posts con puntuación
     * @return array IDs ordenados
     */
    private function aplicarAleatoriedad(array $postsScored): array
    {
        if (count($postsScored) <= 1) {
            return array_keys($postsScored);
        }

        $totalPosts = count($postsScored);
        $randomizeCount = (int)ceil($totalPosts * 0.2);

        if ($randomizeCount > 1) {
            $keys = array_keys($postsScored);
            $randomIndices = array_rand($keys, min($randomizeCount, count($keys)));

            if (!is_array($randomIndices)) {
                $randomIndices = [$randomIndices];
            }

            $randomPosts = [];
            foreach ($randomIndices as $index) {
                $randomPosts[] = $keys[$index];
            }
            shuffle($randomPosts);

            $i = 0;
            foreach ($randomIndices as $index) {
                $postsScored[$keys[$index]] = $postsScored[$randomPosts[$i]] ?? $postsScored[$keys[$index]];
                $i++;
            }
        }

        return array_keys($postsScored);
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
        $logger = \Logger::obtenerInstancia();
        $logger->{$nivel}('algoritmo', $mensaje);
    }
}
