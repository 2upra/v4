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

// Refactor(Org): Funcion limpiarJSON movida a app/Utils/JsonUtils.php

// Refactor(Org): Funcion stemWord movida desde app/AlgoritmoPost/algoritmoPosts.php
function stemWord($word)
{
    return preg_replace('/(s|ed|ing)$/', '', $word);
}

