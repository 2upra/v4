<?php

namespace Kamples\Services\Feed;

/**
 * Servicio de calculo de similitud entre posts.
 * 
 * Extrae palabras de contenido y metadatos para calcular
 * la similitud mediante coeficiente de Jaccard.
 *
 * @since 1.0.0
 */
class AlgoritmoSimilitudService
{
    private static ?AlgoritmoSimilitudService $instancia = null;

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Calcula puntos de similitud entre dos posts.
     *
     * @param int $postId ID del post a evaluar
     * @param int $similarTo ID del post de referencia
     * @param array $datos Datos del feed
     * @return float Puntos de similitud
     */
    public function calcularPuntosSimilarTo(int $postId, int $similarTo, array $datos): float
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
     * Procesa un valor de meta (JSON o array).
     */
    public function procesarMetaValue($metaValue): array
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
     */
    public function extractWordsFromDatosAlgoritmo(array $datosAlgoritmo): array
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
     */
    public function extractWordsFromContent(string $content): array
    {
        $words = preg_split('/\s+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);
        return array_map([$this, 'stemWord'], $words);
    }

    /**
     * Aplica stemming simple a una palabra.
     */
    public function stemWord(string $word): string
    {
        return preg_replace('/(s|ed|ing)$/', '', $word);
    }
}
