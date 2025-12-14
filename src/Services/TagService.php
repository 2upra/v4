<?php

namespace Kamples\Services;

/**
 * Servicio de gestión de tags.
 * 
 * Maneja la obtención y análisis de tags frecuentes
 * del contenido.
 *
 * @since 1.0.0
 */
class TagService
{
    private static ?TagService $instancia = null;
    private CacheService $cache;
    private \wpdb $wpdb;
    private ?\Logger $logger = null;

    /**
     * Constructor privado (Singleton).
     */
    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->cache = CacheService::obtenerInstancia('tags');
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
     * Obtiene los tags más frecuentes del contenido.
     *
     * @param int $cantidad Cantidad de tags a obtener
     * @return array Tags frecuentes
     */
    public function obtenerTagsFrecuentes(int $cantidad = 32): array
    {
        $claveCache = 'tagsFrecuentes13';
        $tagsFrecuentes = $this->cache->obtener($claveCache);
        $tiempoCache = 43200; // 12 horas

        if ($tagsFrecuentes !== false) {
            $tagsArray = array_keys($tagsFrecuentes);
            shuffle($tagsArray);
            return array_slice($tagsArray, 0, $cantidad);
        }

        $fechaLimite = date('Y-m-d', strtotime('-24 month'));

        $consulta = $this->wpdb->prepare(
            "SELECT pm.meta_value 
            FROM {$this->wpdb->postmeta} pm
            INNER JOIN {$this->wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = 'datosAlgoritmo'
            AND p.post_type = 'social_post'
            AND p.post_date >= %s",
            $fechaLimite
        );

        $resultados = $this->wpdb->get_results($consulta, ARRAY_A);
        $conteoTags = [];
        $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio', 'artista_posible'];

        if (empty($resultados)) {
            $this->log('info', 'obtenerTagsFrecuentes: No se encontraron resultados en la base de datos.');
            return [];
        }

        foreach ($resultados as $resultado) {
            $valorMeta = $resultado['meta_value'];
            $datosMeta = json_decode($valorMeta, true);

            if (!is_array($datosMeta)) {
                continue;
            }

            foreach ($campos as $campo) {
                if (isset($datosMeta[$campo]) && is_array($datosMeta[$campo]) && isset($datosMeta[$campo]['en']) && is_array($datosMeta[$campo]['en'])) {
                    foreach ($datosMeta[$campo]['en'] as $tag) {
                        if (is_string($tag)) {
                            $tagNormalizado = strtolower(trim($tag));
                            if (!empty($tagNormalizado)) {
                                $conteoTags[$tagNormalizado] = ($conteoTags[$tagNormalizado] ?? 0) + 1;
                            }
                        }
                    }
                }
            }
        }

        arsort($conteoTags);
        $top70Tags = array_slice($conteoTags, 0, 70, true);
        $claves = array_keys($top70Tags);
        shuffle($claves);
        $clavesSeleccionadas = array_slice($claves, 0, $cantidad);

        $this->cache->guardar($claveCache, $top70Tags, $tiempoCache);

        if (empty($clavesSeleccionadas)) {
            $this->log('info', 'obtenerTagsFrecuentes: No se encontraron tags frecuentes.');
        }

        return $clavesSeleccionadas;
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
}
