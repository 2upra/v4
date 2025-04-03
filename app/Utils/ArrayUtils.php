<?php
// Funcion aplanarArray movida desde app/Content/Colecciones/View/renderPostColec.php

/**
 * Aplana un array multidimensional en un array de una sola dimensión.
 *
 * @param mixed $input El array a aplanar (o cualquier otro valor).
 * @return array El array aplanado.
 */
function aplanarArray($input)
{
    $result = [];
    if (is_array($input)) {
        foreach ($input as $element) {
            if (is_array($element)) {
                $result = array_merge($result, aplanarArray($element));
            } else {
                $result[] = $element;
            }
        }
    } else {
        $result[] = $input;
    }
    return $result;
}

// Funcion movida desde app/Content/Colecciones/View/renderPostColec.php
function maybe_unserialize_dos($data)
{
    if (empty($data)) {
        return $data;
    }

    // Si el dato ya es un array, devolverlo tal cual
    if (is_array($data)) {
        return $data;
    }

    // Intentar decodificar JSON si es un string
    if (is_string($data)) {
        $json = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
    }

    // Intentar deserializar si es un string
    $unserialized = @unserialize($data);
    if ($unserialized !== false || $data === 'b:0;') {
        return $unserialized;
    }

    // Devolver el original si no se pudo deserializar ni decodificar
    return $data;
}
