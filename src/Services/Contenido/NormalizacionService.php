<?php

/**
 * Servicio de normalización de tags
 * 
 * Normaliza los tags de los posts para mantener consistencia
 *
 * @package Kamples\Services\Contenido
 * @since 1.0.0
 */

namespace Kamples\Services\Contenido;

class NormalizacionService
{
    private static ?NormalizacionService $instancia = null;
    private \Logger $logger;

    /** Mapa de normalizaciones de tags */
    private const NORMALIZACIONES = [
        'one-shot' => 'one shot',
        'oneshot' => 'one shot',
        'percusión' => 'percusión',
        'hiphop' => 'hip hop',
        'hip-hop' => 'hip hop',
        'rnb' => 'r&b',
        'vocal' => 'vocals',
        'r&b' => 'r&b',
        'randb' => 'r&b',
        'rock&roll' => 'rock and roll',
        'rockandroll' => 'rock and roll',
        'rock-and-roll' => 'rock and roll',
        'campana de vaca' => 'cowbell',
        'cowbells' => 'cowbell',
        'drums' => 'drum',
    ];

    /** Campos a normalizar */
    private const CAMPOS_NORMALIZAR = [
        'instrumentos_principal',
        'tags_posibles',
        'estado_animo',
        'genero_posible',
        'tipo_audio'
    ];

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->registrarHooks();
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
     * Registra los hooks de WordPress
     */
    private function registrarHooks(): void
    {
        add_action('wp_insert_post', [$this, 'normalizarNuevoPost'], 10, 3);
        add_action('post_updated', [$this, 'normalizarPostActualizado'], 10, 3);

        /* Restauración al inicio (una sola vez) */
        add_action('init', [$this, 'verificarRestauracion']);
    }

    /**
     * Normaliza los tags de un post nuevo
     *
     * @param int $postId ID del post
     * @param \WP_Post $post Objeto del post
     * @param bool $update Si es actualización
     */
    public function normalizarNuevoPost(int $postId, \WP_Post $post, bool $update): void
    {
        if ('social_post' !== get_post_type($postId)) {
            return;
        }

        $metaDatos = get_post_meta($postId, 'datosAlgoritmo', true);

        if (!is_array($metaDatos)) {
            return;
        }

        /* Crear respaldo si no existe */
        $respaldoExistente = get_post_meta($postId, 'datosAlgoritmo_respaldo', true);
        if (empty($respaldoExistente)) {
            add_post_meta($postId, 'datosAlgoritmo_respaldo', $metaDatos, true);
        }

        /* Normalizar tags */
        $fueModificado = $this->normalizarDatos($metaDatos);

        if ($fueModificado) {
            update_post_meta($postId, 'datosAlgoritmo', $metaDatos);
        }

        $this->verificarYRestaurarDatos($postId);
    }

    /**
     * Normaliza los tags cuando un post es actualizado
     */
    public function normalizarPostActualizado(int $postId, \WP_Post $postAfter, \WP_Post $postBefore): void
    {
        if ('social_post' !== get_post_type($postId)) {
            return;
        }

        if ($postBefore->post_content === $postAfter->post_content) {
            return;
        }

        $this->normalizarNuevoPost($postId, $postAfter, true);
    }

    /**
     * Normaliza los datos de un array de metadatos
     *
     * @param array &$metaDatos Array de datos a normalizar (por referencia)
     * @return bool True si se hicieron cambios
     */
    private function normalizarDatos(array &$metaDatos): bool
    {
        $fueModificado = false;

        foreach (self::CAMPOS_NORMALIZAR as $campo) {
            foreach (['es', 'en'] as $idioma) {
                if (!empty($metaDatos[$campo][$idioma]) && is_array($metaDatos[$campo][$idioma])) {
                    foreach ($metaDatos[$campo][$idioma] as &$tag) {
                        $tagLower = strtolower(trim($tag));
                        if (isset(self::NORMALIZACIONES[$tagLower])) {
                            $tag = self::NORMALIZACIONES[$tagLower];
                            $fueModificado = true;
                        }
                    }
                }
            }
        }

        return $fueModificado;
    }

    /**
     * Verifica y restaura datos del algoritmo desde respaldo
     */
    public function verificarYRestaurarDatos(int $postId): void
    {
        $datosAlgoritmo = get_post_meta($postId, 'datosAlgoritmo', true);

        if (empty($datosAlgoritmo)) {
            $respaldo = get_post_meta($postId, 'datosAlgoritmo_respaldo', true);

            if (!empty($respaldo)) {
                update_post_meta($postId, 'datosAlgoritmo', $respaldo);
                $this->logger->info('normalizacion', "Datos restaurados para el post ID: $postId");
            }
        }
    }

    /**
     * Verifica si es necesario restaurar datos del algoritmo (una sola vez)
     */
    public function verificarRestauracion(): void
    {
        if (!get_option('datos_algoritmo_restaurado')) {
            $this->restaurarDatosAlgoritmo();
            update_option('datos_algoritmo_restaurado', 1);
        }
    }

    /**
     * Restaura datos del algoritmo para todos los posts
     */
    public function restaurarDatosAlgoritmo(): void
    {
        $args = [
            'post_type' => 'social_post',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ];

        $posts = get_posts($args);

        foreach ($posts as $postId) {
            $datosAlgoritmo = get_post_meta($postId, 'datosAlgoritmo', true);

            if (empty($datosAlgoritmo)) {
                $respaldo = get_post_meta($postId, 'datosAlgoritmo_respaldo', true);

                if (!empty($respaldo)) {
                    update_post_meta($postId, 'datosAlgoritmo', $respaldo);
                    $this->logger->info('normalizacion', "Restaurado 'datosAlgoritmo' para el post ID: $postId");
                }
            }
        }

        $this->logger->info('normalizacion', "Restauración de 'datosAlgoritmo' completada.");
    }

    /**
     * Crea respaldos y normaliza tags en lote
     *
     * @param int $batchSize Tamaño del lote
     * @return int Total de posts procesados
     */
    public function crearRespaldoYNormalizar(int $batchSize = 100): int
    {
        global $wpdb;

        $offset = 0;
        $totalProcesados = 0;

        do {
            $query = $wpdb->prepare("
                SELECT p.ID, pm.meta_value 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'social_post'
                AND pm.meta_key = 'datosAlgoritmo'
                LIMIT %d, %d
            ", $offset, $batchSize);

            $resultados = $wpdb->get_results($query);

            if (empty($resultados)) {
                break;
            }

            foreach ($resultados as $row) {
                $metaDatos = json_decode($row->meta_value, true);

                if (!is_array($metaDatos)) {
                    continue;
                }

                /* Crear respaldo */
                $respaldoExistente = get_post_meta($row->ID, 'datosAlgoritmo_respaldo', true);
                if (empty($respaldoExistente)) {
                    add_post_meta($row->ID, 'datosAlgoritmo_respaldo', $row->meta_value);
                }

                /* Normalizar */
                if ($this->normalizarDatos($metaDatos)) {
                    update_post_meta($row->ID, 'datosAlgoritmo', $metaDatos);
                }

                $totalProcesados++;
            }

            $offset += $batchSize;

            if ($totalProcesados % 1000 === 0) {
                sleep(1);
            }
        } while (count($resultados) === $batchSize);

        return $totalProcesados;
    }

    /**
     * Revierte la normalización desde respaldos
     *
     * @param int $batchSize Tamaño del lote
     * @return int Total de posts revertidos
     */
    public function revertirNormalizacion(int $batchSize = 100): int
    {
        global $wpdb;

        $offset = 0;
        $totalRevertidos = 0;

        do {
            $query = $wpdb->prepare("
                SELECT p.ID, pm.meta_value 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'social_post'
                AND pm.meta_key = 'datosAlgoritmo_respaldo'
                LIMIT %d, %d
            ", $offset, $batchSize);

            $resultados = $wpdb->get_results($query);

            if (empty($resultados)) {
                break;
            }

            foreach ($resultados as $row) {
                update_post_meta($row->ID, 'datosAlgoritmo', $row->meta_value);
                delete_post_meta($row->ID, 'datosAlgoritmo_respaldo');
                $totalRevertidos++;
            }

            $offset += $batchSize;

            if ($totalRevertidos % 1000 === 0) {
                sleep(1);
            }
        } while (count($resultados) === $batchSize);

        return $totalRevertidos;
    }
}
