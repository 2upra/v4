<?php

namespace Kamples\Services;

/**
 * Servicio de gestión de intereses del usuario.
 * 
 * Genera y actualiza los intereses del usuario basándose
 * en sus likes e interacciones con el contenido.
 *
 * @since 1.0.0
 */
class InteresService
{
    private static ?InteresService $instancia = null;
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
     * Genera los metadatos de intereses basándose en los likes del usuario.
     *
     * @param int $userId ID del usuario
     * @return bool True si se generaron exitosamente
     */
    public function generarMetaDeIntereses(int $userId): bool
    {
        if (empty($userId) || $userId <= 0) {
            $this->log('error', "ID de usuario inválido: $userId");
            return false;
        }

        global $wpdb;

        $likePost = $this->obtenerLikesDelUsuario($userId, 500);
        if (empty($likePost) || !is_array($likePost)) {
            $this->log('info', "No se encontraron likes para el usuario: $userId");
            return false;
        }

        /* Obtener intereses actuales */
        $interesesActuales = $wpdb->get_results($wpdb->prepare(
            "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
            $userId
        ), OBJECT_K);

        try {
            /* Extraer post_ids de los likes */
            $postIds = array_map(function ($like) {
                return $like->post_id;
            }, $likePost);

            /* Crear placeholders para la consulta */
            $placeholders = implode(',', array_fill(0, count($postIds), '%d'));

            $query = "
                SELECT p.ID, p.post_content, pm.meta_value
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
                WHERE p.ID IN ($placeholders)
            ";

            $sql = $wpdb->prepare($query, $postIds);
            $postData = $wpdb->get_results($sql);

            if (empty($postData)) {
                $this->log('info', "No se encontraron datos de posts para los likes del usuario: $userId");
                return false;
            }

            $tagIntensidad = $this->procesarPostsParaIntereses($postData, $likePost);

            if (empty($tagIntensidad)) {
                $this->log('info', "No se generaron tags de intensidad para el usuario: $userId");
                return false;
            }

            /* Ordenar y limitar a 200 intereses */
            arsort($tagIntensidad);
            $tagIntensidad = array_slice($tagIntensidad, 0, 200, true);

            return $this->actualizarIntereses($userId, $tagIntensidad, $interesesActuales);
        } catch (\Exception $e) {
            $this->log('error', "Error en generarMetaDeIntereses: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los likes del usuario.
     *
     * @param int $userId ID del usuario
     * @param int $limit Límite de resultados
     * @return array Likes del usuario
     */
    public function obtenerLikesDelUsuario(int $userId, int $limit = 500): array
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'post_likes';

        $query = $wpdb->prepare(
            "SELECT post_id, like_type FROM $tableName WHERE user_id = %d ORDER BY like_date DESC LIMIT %d",
            $userId,
            $limit
        );

        $likedPosts = $wpdb->get_results($query);

        if (empty($likedPosts)) {
            return [];
        }

        return $likedPosts;
    }

    /**
     * Procesa los posts para extraer intereses.
     *
     * @param array $postData Datos de posts
     * @param array $likePost Likes del usuario
     * @return array Intensidades por tag
     */
    private function procesarPostsParaIntereses(array $postData, array $likePost): array
    {
        $tagIntensidad = [];

        foreach ($postData as $post) {
            /* Obtener el tipo de like para este post */
            $likeData = array_values(array_filter($likePost, function ($like) use ($post) {
                return $like->post_id == $post->ID;
            }));

            $likeType = $likeData[0]->like_type ?? 'like';

            /* Multiplicador basado en tipo de like */
            $multiplicador = 1;
            if ($likeType === 'favorito') {
                $multiplicador = 2;
            } elseif ($likeType === 'no_me_gusta') {
                $multiplicador = -1;
            }

            /* Procesar datosAlgoritmo */
            if (!empty($post->meta_value)) {
                $datosAlgoritmo = json_decode($post->meta_value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                if (is_array($datosAlgoritmo)) {
                    foreach ($datosAlgoritmo as $key => $value) {
                        if (is_array($value)) {
                            foreach (['es', 'en'] as $lang) {
                                if (isset($value[$lang]) && is_array($value[$lang])) {
                                    foreach ($value[$lang] as $item) {
                                        if (is_string($item)) {
                                            $item = $this->normalizarTexto($item);
                                            $tagIntensidad[$item] = ($tagIntensidad[$item] ?? 0) + $multiplicador;
                                        }
                                    }
                                }
                            }
                        } elseif (is_string($value) && !empty($value)) {
                            $value = $this->normalizarTexto($value);
                            $tagIntensidad[$value] = ($tagIntensidad[$value] ?? 0) + $multiplicador;
                        }
                    }
                }
            }

            /* Procesar contenido del post */
            if (!empty($post->post_content)) {
                $content = wp_strip_all_tags($post->post_content);
                $content = $this->normalizarTexto($content);
                $palabras = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($palabras as $palabra) {
                    $palabra = trim($palabra);
                    if (!empty($palabra)) {
                        $tagIntensidad[$palabra] = ($tagIntensidad[$palabra] ?? 0) + $multiplicador;
                    }
                }
            }
        }

        return $tagIntensidad;
    }

    /**
     * Actualiza los intereses del usuario en la base de datos.
     *
     * @param int $userId ID del usuario
     * @param array $tagIntensidad Intensidades por tag
     * @param array $interesesActuales Intereses actuales
     * @return bool True si se actualizó correctamente
     */
    private function actualizarIntereses(int $userId, array $tagIntensidad, $interesesActuales): bool
    {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            $batchValues = [];

            foreach ($tagIntensidad as $interest => $intensity) {
                $batchValues[] = $wpdb->prepare('(%d, %s, %d)', $userId, $interest, $intensity);
            }

            if (!empty($batchValues)) {
                $values = implode(', ', $batchValues);
                $sql = "
                    INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity)
                    VALUES $values
                    ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)
                ";
                $wpdb->query($sql);
            }

            /* Eliminar intereses que ya no aplican */
            if (!empty($interesesActuales)) {
                $interesesAEliminar = array_diff_key((array)$interesesActuales, $tagIntensidad);

                if (!empty($interesesAEliminar)) {
                    $placeholders = implode(', ', array_fill(0, count($interesesAEliminar), '%s'));
                    $sql = $wpdb->prepare(
                        "DELETE FROM " . INTERES_TABLE . " WHERE user_id = %d AND interest IN ($placeholders)",
                        array_merge([$userId], array_keys($interesesAEliminar))
                    );
                    $wpdb->query($sql);
                }
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->log('error', 'Error al actualizar intereses: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Normaliza un texto para comparación.
     *
     * @param string $texto Texto a normalizar
     * @return string Texto normalizado
     */
    private function normalizarTexto(string $texto): string
    {
        if (function_exists('normalizarTexto')) {
            return normalizarTexto($texto);
        }

        /* Fallback: normalización básica */
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = preg_replace('/[^\p{L}\p{N}\s]/u', '', $texto);
        return trim($texto);
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
