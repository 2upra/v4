<?php

namespace Kamples\Services\Feed;

use Kamples\Services\Core\CacheService;
use Kamples\Services\Usuario\InteresService;

/**
 * Servicio de recopilacion de datos para el feed.
 * 
 * Responsabilidad unica: Obtener todos los datos necesarios para calcular el feed.
 *
 * @since 3.0.0
 */
class FeedRecopilacionService
{
    private static ?FeedRecopilacionService $instancia = null;
    private CacheService $cache;
    private ?FeedDatosService $datosService = null;
    private ?\Logger $logger = null;

    private function __construct()
    {
        $this->cache = CacheService::obtenerInstancia('feed');
        $this->datosService = FeedDatosService::obtenerInstancia();
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
     * Obtiene datos del feed con cache.
     *
     * @param int $userId ID del usuario
     * @return array Datos del feed
     */
    public function obtenerDatosFeedConCache(int $userId): array
    {
        $cacheKey = 'feed_datos_' . $userId;
        $datos = $this->cache->obtener($cacheKey);

        if ($datos === false) {
            $datos = $this->obtenerDatosFeed($userId);
            $this->cache->guardar($cacheKey, $datos, 43200);
        }

        if (!isset($datos['author_results']) || !is_array($datos['author_results'])) {
            return [];
        }

        return $datos;
    }

    /**
     * Obtiene todos los datos necesarios para calcular el feed.
     *
     * @param int $userId ID del usuario
     * @return array Datos del feed
     */
    public function obtenerDatosFeed(int $userId): array
    {
        $tiempoInicio = microtime(true);

        try {
            if (!$this->datosService->comprobarConexionBD() || !$userId) {
                return [];
            }

            $siguiendo = $this->datosService->obtenerUsuariosSeguidos($userId);
            $intereses = $this->datosService->obtenerInteresesUsuario($userId);
            $vistas = $this->datosService->vistasDatos($userId);

            /* Generar/actualizar intereses del usuario */
            $interesService = InteresService::obtenerInstancia();
            $interesService->generarMetaDeIntereses($userId);

            $postsIds = $this->datosService->obtenerIdsPostsRecientes();
            if (empty($postsIds)) {
                return [];
            }

            $cacheKey = 'metaData_' . md5(implode('_', $postsIds));
            $metaData = $this->cache->obtener($cacheKey);

            if ($metaData === false) {
                $metaData = $this->datosService->obtenerMetadatosPosts($postsIds);
                $this->cache->guardar($cacheKey, $metaData, 14400);
            }

            $metaRoles = $this->datosService->procesarMetadatosRoles($metaData);
            $likesPorPost = $this->datosService->obtenerLikesPorPost($postsIds);
            $postsResultados = $this->datosService->obtenerDatosBasicosPosts($postsIds);
            $postContenido = $this->datosService->procesarContenidoPosts($postsResultados);

            $tiempoTotal = microtime(true) - $tiempoInicio;
            $this->logger->info('algoritmo', "Feed obtenido para usuario {$userId} en {$tiempoTotal}s");

            return [
                'siguiendo' => $siguiendo,
                'interesesUsuario' => $intereses,
                'posts_ids' => $postsIds,
                'likes_by_post' => $likesPorPost,
                'meta_data' => $metaData,
                'meta_roles' => $metaRoles,
                'author_results' => $postsResultados,
                'post_content' => $postContenido,
            ];
        } catch (\Exception $e) {
            $this->logger->error('algoritmo', "Error en obtenerDatosFeed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpia el cache de datos del feed para un usuario.
     * 
     * @param int $userId ID del usuario
     */
    public function limpiarCacheDatos(int $userId): void
    {
        $this->cache->borrar('feed_datos_' . $userId);
    }
}
