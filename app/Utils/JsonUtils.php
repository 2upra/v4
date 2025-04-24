<?php

// Refactor(Org): Funcion limpiarJSON movida desde app/Utils/StringUtils.php
/**
 * Limpia una cadena JSON, asegurándose de que sea un JSON válido.
 * Puede decodificar una cadena JSON entre comillas o codificar un array/objeto.
 *
 * @param mixed $json_data El dato a limpiar (puede ser string, array u objeto).
 * @return string Una cadena JSON válida o el string original si no es JSON ni array/objeto.
 */
function limpiarJSON($json_data)
{
    // Si es una cadena y empieza y termina con comillas
    if (is_string($json_data) && substr($json_data, 0, 1) === '"' && substr($json_data, -1) === '"') {
        // Eliminar las comillas extras y decodificar los caracteres escapados
        $json_data = json_decode($json_data);
    }

    // Si es un array u objeto, convertirlo a JSON
    if (is_array($json_data) || is_object($json_data)) {
        $json_data = json_encode($json_data);
    }

    return $json_data;
}
