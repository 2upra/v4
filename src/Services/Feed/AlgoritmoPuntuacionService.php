<?php

namespace Kamples\Services\Feed;

/**
 * Servicio de calculo de puntuacion para el algoritmo.
 * 
 * Calcula puntos por intereses, busqueda (identifier) y likes.
 *
 * @since 1.0.0
 */
class AlgoritmoPuntuacionService
{
    private static ?AlgoritmoPuntuacionService $instancia = null;

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Calcula puntos basados en los intereses del usuario.
     *
     * @param int $postId ID del post
     * @param array $datos Datos del feed
     * @return int Puntos por intereses
     */
    public function calcularPuntosIntereses(int $postId, array $datos): int
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
     * Calcula puntos basados en un identificador de busqueda.
     *
     * @param int $postId ID del post
     * @param string|array $identifier Identificador(es)
     * @param array $datos Datos del feed
     * @return int Puntos totales
     */
    public function calcularPuntosIdentifier(int $postId, $identifier, array $datos): int
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
                /* Comparacion difusa */
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
     * Calcula puntos por likes de un post.
     *
     * @param int $postId ID del post
     * @param array $datos Datos del feed
     * @return int Puntos por likes
     */
    public function calcularPuntosLikes(int $postId, array $datos): int
    {
        $likesPorPost = $datos['likes_by_post'] ?? [];
        if (!is_array($likesPorPost)) {
            $likesPorPost = [];
        }

        $likesData = $likesPorPost[$postId] ?? ['like' => 0, 'favorito' => 0, 'no_me_gusta' => 0];

        return 5 + $likesData['like'] + 10 * $likesData['favorito'] - ($likesData['no_me_gusta'] * 10);
    }

    /**
     * Calcula los puntos finales combinados.
     *
     * @param int $pUsuario Puntos por usuario seguido
     * @param int $pIntereses Puntos por intereses
     * @param int $puntosLikes Puntos por likes
     * @param bool $metaVerificado Si el post esta verificado
     * @param bool $metaPostAut Si es post automatico
     * @param bool $esAdmin Si el usuario es admin
     * @return float Puntos finales
     */
    public function calcularPuntosFinales(
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
     * Calcula puntos por target de audiencia (Artista/Fan).
     *
     * @param int $postId ID del post
     * @param array $datos Datos del feed
     * @param string|null $tipoUsuario Tipo de usuario
     * @param int|null $similarTo ID de post similar (si existe, no aplica puntos)
     * @return int Puntos por target
     */
    public function calcularPuntosTarget(int $postId, array $datos, ?string $tipoUsuario, ?int $similarTo): int
    {
        if (!empty($similarTo)) {
            return 0;
        }

        $metaRoles = $datos['meta_roles'] ?? [];
        if (!isset($metaRoles[$postId]) || !is_array($metaRoles[$postId])) {
            $metaRoles[$postId] = ['artista' => false, 'fan' => false];
        }

        $postParaFans = !empty($metaRoles[$postId]['fan']);

        if ($tipoUsuario === 'Fan') {
            return $postParaFans ? 999 : 0;
        } elseif ($tipoUsuario === 'Artista') {
            return $postParaFans ? -50 : 0;
        }

        return 0;
    }
}
