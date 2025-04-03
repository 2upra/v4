<?php

/**
 * Normaliza un texto: convierte a minúsculas, elimina acentos y caracteres no alfanuméricos.
 *
 * @param string $texto El texto a normalizar.
 * @return string El texto normalizado.
 */
function normalizarTexto($texto)
{
    // Convertir a minúsculas y eliminar acentos
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = preg_replace('/[áàäâã]/u', 'a', $texto);
    $texto = preg_replace('/[éèëê]/u', 'e', $texto);
    $texto = preg_replace('/[íìïî]/u', 'i', $texto);
    $texto = preg_replace('/[óòöôõ]/u', 'o', $texto);
    $texto = preg_replace('/[úùüû]/u', 'u', $texto);
    $texto = preg_replace('/[ñ]/u', 'n', $texto);

    // Eliminar cualquier carácter no alfanumérico (excepto espacios)
    $texto = preg_replace('/[^a-z0-9\s]+/u', '', $texto);

    return $texto;
}

// Refactor(Org): Mover función limpiarJSON desde renderPost.php
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

