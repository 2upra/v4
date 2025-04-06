<?php

// Funcion procesarMetaValue movida desde app/Utils/ArrayUtils.php
function procesarMetaValue($meta_value)
{
    if (is_array($meta_value)) {
        return $meta_value;
    }
    if (is_string($meta_value)) {
        $decoded_value = json_decode($meta_value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded_value;
        } else {
            return [];
        }
    }
    //error_log("meta_value no es un array ni una cadena, es de tipo: " . gettype($meta_value));
    return [];
}

?>