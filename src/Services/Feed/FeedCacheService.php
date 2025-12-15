<?php

namespace Kamples\Services\Feed;

use Kamples\Services\CacheService;
use Kamples\Services\AlgoritmoService;

/**
 * Servicio de cache y reinicio del feed.
 * 
 * Responsabilidad unica: Gestion de cache del feed y reinicio.
 *
 * @since 3.0.0
 */
class FeedCacheService
{
    private static ?FeedCacheService $instancia = null;
    private CacheService $cache;
    private ?\Logger $logger = null;

    private function __construct()
    {
        $this->cache = CacheService::obtenerInstancia('feed');
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
     * Reinicia el feed de un usuario.
     *
     * @param int $userId ID del usuario
     * @return int Numero de caches eliminadas
     */
    public function reiniciarFeed(int $userId): int
    {
        $tipoUsuario = get_user_meta($userId, 'tipoUsuario', true);
        $esAdmin = current_user_can('administrator');
        $eliminadas = 0;

        $this->logger->info('algoritmo', "Iniciando reinicio de feed para usuario ID: {$userId}");

        $cacheKey = ($userId == 44)
            ? "feed_personalizado_user_44_"
            : "feed_personalizado_user_{$userId}_";

        $cacheTiempo = $esAdmin ? 7200 : 43200;

        $patron = ($userId == 44)
            ? "feed_personalizado_anonymous_*"
            : "feed_personalizado_user_{$userId}_*";

        $eliminadas = $this->cache->borrarPorPatron($patron);

        if ($eliminadas > 0) {
            $this->logger->info('algoritmo', "Caches eliminadas: {$eliminadas} para usuario ID: {$userId}");

            $algoritmoService = AlgoritmoService::obtenerInstancia();
            $postsPersonalizados = $algoritmoService->calcularFeedPersonalizado($userId, '', null, $tipoUsuario);

            if ($postsPersonalizados) {
                $cacheContenido = ['posts' => $postsPersonalizados, 'timestamp' => time()];
                $this->cache->guardar($cacheKey, $cacheContenido, $cacheTiempo);
            }
        }

        $this->cache->borrar('feed_datos_' . $userId);
        $this->logger->info('algoritmo', "Reinicio de feed completado para usuario ID: {$userId}");

        return $eliminadas;
    }

    /**
     * Guarda el feed en cache.
     * 
     * @param string $cacheKey Clave del cache
     * @param array $posts Posts a guardar
     * @param int $duracion Duracion en segundos
     */
    public function guardarFeedEnCache(string $cacheKey, array $posts, int $duracion): void
    {
        $cacheContenido = ['posts' => $posts, 'timestamp' => time()];
        $this->cache->guardar($cacheKey, $cacheContenido, $duracion);
    }

    /**
     * Obtiene feed del cache.
     * 
     * @param string $cacheKey Clave del cache
     * @return array|null Posts o null si no existe
     */
    public function obtenerFeedDeCache(string $cacheKey): ?array
    {
        $cacheData = $this->cache->obtener($cacheKey);

        if ($cacheData && isset($cacheData['posts'])) {
            return $cacheData['posts'];
        }

        return null;
    }

    /**
     * Genera la clave de cache para el feed.
     * 
     * @param int $userId ID del usuario
     * @param string $identificador Identificador
     * @param array|null $filtrosUsuario Filtros del usuario
     * @param string|null $tipoUsuario Tipo de usuario
     * @return string Clave de cache
     */
    public function generarCacheKey(
        int $userId,
        string $identificador,
        ?array $filtrosUsuario,
        ?string $tipoUsuario
    ): string {
        $filtrosHash = $filtrosUsuario ? md5(serialize($filtrosUsuario)) : 'sin_filtros';
        $tipoUsuarioCache = $tipoUsuario ? md5($tipoUsuario) : 'sin_tipo';

        return ($userId == 44)
            ? "feed_personalizado_user_44_{$identificador}_{$filtrosHash}_{$tipoUsuarioCache}"
            : "feed_personalizado_user_{$userId}_{$identificador}_{$filtrosHash}_{$tipoUsuarioCache}";
    }
}
