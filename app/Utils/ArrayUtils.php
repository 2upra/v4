<?php
// Funcion movida desde app/Content/Colecciones/View/renderPostColec.php

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
