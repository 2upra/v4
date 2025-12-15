<?php

namespace Kamples\Services\Feed;

use Kamples\Services\CacheService;
use Kamples\Services\AlgoritmoService;

/**
 * Fachada para el servicio de feed.
 * 
 * Orquesta los servicios especializados de feed:
 * - FeedDatosService: Consultas a base de datos
 * - FeedRecopilacionService: Recopilacion de datos con cache
 * - FeedCacheService: Gestion de cache y reinicio
 *
 * @since 3.0.0
 */
class FeedService
{
    private static ?FeedService $instancia = null;
    private CacheService $cache;

    private ?FeedDatosService $datosService = null;
    private ?FeedRecopilacionService $recopilacionService = null;
    private ?FeedCacheService $cacheService = null;
    private ?\Logger $logger = null;

    private function __construct()
    {
        $this->cache = CacheService::obtenerInstancia('feed');
        $this->datosService = FeedDatosService::obtenerInstancia();
        $this->recopilacionService = FeedRecopilacionService::obtenerInstancia();
        $this->cacheService = FeedCacheService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene el feed personalizado para un usuario.
     *
     * @param int $idUsuario ID del usuario
     * @param string $identificador Identificador del feed
     * @param int|null $similar ID del post para posts similares
     * @param int $pagina Numero de pagina
     * @param bool $esAdmin Si el usuario es administrador
     * @param int $postsPagina Posts por pagina
     * @param string|null $tipoUsuario Tipo de usuario
     * @param array|null $filtrosUsuario Filtros del usuario
     * @return array Array con post_ids y post_not_in
     */
    public function obtenerFeedPersonalizado(
        int $idUsuario,
        string $identificador = '',
        ?int $similar = null,
        int $pagina = 1,
        bool $esAdmin = false,
        int $postsPagina = 12,
        ?string $tipoUsuario = null,
        ?array $filtrosUsuario = null
    ): array {
        try {
            if (!$idUsuario) {
                return ['post_ids' => [], 'post_not_in' => []];
            }

            $filtrosUsuario = get_user_meta($idUsuario, 'filtroPost', true);
            if (!is_array($filtrosUsuario)) {
                $filtrosUsuario = [];
            }

            if ($similar) {
                return $this->obtenerPostsSimilares($idUsuario, $similar);
            }

            $cacheKey = $this->cacheService->generarCacheKey($idUsuario, $identificador, $filtrosUsuario, $tipoUsuario);
            $cacheTiempo = $esAdmin ? 7200 : 43200;

            $posts = $this->cacheService->obtenerFeedDeCache($cacheKey);

            if (!$posts) {
                $posts = $this->calcularFeed($idUsuario, $identificador, '', $tipoUsuario, $filtrosUsuario);

                if (!$posts) {
                    return ['post_ids' => [], 'post_not_in' => []];
                }

                $this->cacheService->guardarFeedEnCache($cacheKey, $posts, $cacheTiempo);
            }

            $postIds = is_array($posts) ? array_keys($posts) : [];

            if (defined('POSTINLIMIT') && count($postIds) > POSTINLIMIT) {
                $postIds = array_slice($postIds, 0, POSTINLIMIT);
            }

            return [
                'post_ids' => $postIds,
                'post_not_in' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('algoritmo', "Error en obtenerFeedPersonalizado: " . $e->getMessage());
            return ['post_ids' => [], 'post_not_in' => []];
        }
    }

    /**
     * Obtiene posts similares a un post especifico.
     *
     * @param int $userId ID del usuario
     * @param int $similarTo ID del post de referencia
     * @return array
     */
    public function obtenerPostsSimilares(int $userId, int $similarTo): array
    {
        $postNotIn = [$similarTo];
        $cacheKey = "similar_to_{$similarTo}";

        $cachedData = $this->cache->obtener($cacheKey);

        if ($cachedData) {
            return [
                'post_ids' => is_array($cachedData) ? array_keys($cachedData) : [],
                'post_not_in' => $postNotIn,
            ];
        }

        $postsPersonalizados = $this->calcularFeed(44, '', (string)$similarTo);

        if (!$postsPersonalizados) {
            return ['post_ids' => [], 'post_not_in' => $postNotIn];
        }

        $this->cache->guardar($cacheKey, $postsPersonalizados, 15 * DAY_IN_SECONDS);

        return [
            'post_ids' => array_keys($postsPersonalizados),
            'post_not_in' => $postNotIn,
        ];
    }

    /**
     * Reinicia el feed de un usuario.
     */
    public function reiniciarFeed(int $userId): int
    {
        return $this->cacheService->reiniciarFeed($userId);
    }

    /**
     * Obtiene datos del feed con cache.
     */
    public function obtenerDatosFeedConCache(int $userId): array
    {
        return $this->recopilacionService->obtenerDatosFeedConCache($userId);
    }

    /**
     * Obtiene todos los datos necesarios para calcular el feed.
     */
    public function obtenerDatosFeed(int $userId): array
    {
        return $this->recopilacionService->obtenerDatosFeed($userId);
    }

    /* 
     * Metodos delegados a FeedDatosService
     */

    public function obtenerUsuariosSeguidos(int $userId): array
    {
        return $this->datosService->obtenerUsuariosSeguidos($userId);
    }

    public function obtenerInteresesUsuario(int $userId)
    {
        return $this->datosService->obtenerInteresesUsuario($userId);
    }

    public function vistasDatos(int $userId): mixed
    {
        return $this->datosService->vistasDatos($userId);
    }

    public function obtenerIdsPostsRecientes(): array
    {
        return $this->datosService->obtenerIdsPostsRecientes();
    }

    public function obtenerMetadatosPosts(array $postsIds): array
    {
        return $this->datosService->obtenerMetadatosPosts($postsIds);
    }

    public function procesarMetadatosRoles(array $metaData): array
    {
        return $this->datosService->procesarMetadatosRoles($metaData);
    }

    public function obtenerLikesPorPost(array $postsIds): array
    {
        return $this->datosService->obtenerLikesPorPost($postsIds);
    }

    public function obtenerDatosBasicosPosts(array $postsIds)
    {
        return $this->datosService->obtenerDatosBasicosPosts($postsIds);
    }

    public function procesarContenidoPosts($postsResultados): array
    {
        return $this->datosService->procesarContenidoPosts($postsResultados);
    }

    /**
     * Calcula el feed personalizado usando el AlgoritmoService.
     */
    public function calcularFeed(
        int $userId,
        string $identificador = '',
        string $similar = '',
        ?string $tipoUsuario = null,
        ?array $filtrosUsuario = null
    ): array {
        $algoritmoService = AlgoritmoService::obtenerInstancia();
        $similarTo = !empty($similar) ? (int)$similar : null;

        return $algoritmoService->calcularFeedPersonalizado(
            $userId,
            $identificador,
            $similarTo,
            $tipoUsuario
        );
    }
}
