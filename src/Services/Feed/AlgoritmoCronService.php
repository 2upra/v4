<?php

namespace Kamples\Services\Feed;

/**
 * Servicio de cron para recalculo de feeds similares.
 * 
 * Procesa posts en background para pre-calcular feeds similares
 * y guardarlos en cache.
 *
 * @since 1.0.0
 */
class AlgoritmoCronService
{
    private const LOCK_KEY = 'similar_to_process_lock';
    private const MAX_LOCK_TIME = 300;
    private const PROGRESS_OPTION = 'similar_to_progress';
    private const CACHED_COUNT_OPTION = 'similar_to_cached_count';
    private const STOP_UNTIL_OPTION = 'similar_to_stop_until';
    private const CONSECUTIVE_LIMIT = 100;
    private const STOP_DURATION = 6 * HOUR_IN_SECONDS;
    private const DEFAULT_USER_ID = 44;

    private static ?AlgoritmoCronService $instancia = null;
    private ?\Logger $logger = null;

    public function __construct()
    {
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
     * Recalcula feed similar para posts en background (Cron job).
     */
    public function recalcularSimilarToFeed(): void
    {
        /* Verificar detencion */
        $stopUntil = get_option(self::STOP_UNTIL_OPTION, 0);
        if ($stopUntil && time() < $stopUntil) {
            return;
        } elseif ($stopUntil && time() >= $stopUntil) {
            delete_option(self::STOP_UNTIL_OPTION);
            update_option(self::CACHED_COUNT_OPTION, 0);
        }

        /* Lock para evitar ejecuciones simultaneas */
        $lockTime = get_transient(self::LOCK_KEY);
        if ($lockTime && (time() - $lockTime < self::MAX_LOCK_TIME)) {
            return;
        }
        set_transient(self::LOCK_KEY, time(), self::MAX_LOCK_TIME);

        try {
            $this->procesarPosts();
        } catch (\Exception $e) {
            $this->log('error', "Error en recalcularSimilarToFeed: " . $e->getMessage());
        } finally {
            delete_transient(self::LOCK_KEY);
        }
    }

    /**
     * Procesa posts pendientes de calculo.
     */
    private function procesarPosts(): void
    {
        global $wpdb;
        $lastProcessedId = (int)get_option(self::PROGRESS_OPTION, 0);

        while (true) {
            $postId = $this->obtenerSiguientePost($wpdb, $lastProcessedId);

            if (!$postId) {
                update_option(self::PROGRESS_OPTION, 0);
                update_option(self::CACHED_COUNT_OPTION, 0);
                break;
            }

            $cacheKey = "similar_to_$postId";

            if (get_transient($cacheKey)) {
                $this->procesarPostConCache($postId);
                $lastProcessedId = $postId;
            } else {
                $this->calcularYGuardarSimilitud($postId, $cacheKey);
                break;
            }
        }
    }

    /**
     * Obtiene el siguiente post a procesar.
     */
    private function obtenerSiguientePost($wpdb, int $lastProcessedId): ?int
    {
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
        return $postId ? (int)$postId : null;
    }

    /**
     * Procesa un post que ya tiene cache.
     */
    private function procesarPostConCache(int $postId): void
    {
        update_option(self::PROGRESS_OPTION, $postId);
        $cachedCount = (int)get_option(self::CACHED_COUNT_OPTION, 0) + 1;
        update_option(self::CACHED_COUNT_OPTION, $cachedCount);

        if ($cachedCount >= self::CONSECUTIVE_LIMIT) {
            update_option(self::STOP_UNTIL_OPTION, time() + self::STOP_DURATION);
            update_option(self::CACHED_COUNT_OPTION, 0);
        }
    }

    /**
     * Calcula y guarda la similitud para un post.
     */
    private function calcularYGuardarSimilitud(int $postId, string $cacheKey): void
    {
        $algoritmoService = AlgoritmoService::obtenerInstancia();
        $postsSimilares = $algoritmoService->calcularFeedPersonalizado(
            self::DEFAULT_USER_ID,
            '',
            $postId
        );

        if ($postsSimilares) {
            set_transient($cacheKey, $postsSimilares, 15 * DAY_IN_SECONDS);
        }

        update_option(self::PROGRESS_OPTION, $postId);
        update_option(self::CACHED_COUNT_OPTION, 0);
    }

    private function log(string $nivel, string $mensaje): void
    {
        if ($this->logger) {
            $this->logger->$nivel('algoritmo', $mensaje);
        }
    }
}
