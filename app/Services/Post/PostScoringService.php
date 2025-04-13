<?php
// Contiene funciones relacionadas con el cálculo de puntuaciones para posts (ej: basado en intereses).

// Refactor(Org): Funcion movida desde app/AlgoritmoPost/algoritmoPosts.php
function calcularPuntosIntereses($postId, $datos)
{
    $pIntereses = 0;

    // Verificar si existen los indices necesarios, si es un objeto y tiene la propiedad meta_value
    if (
        !isset($datos['datosAlgoritmo'][$postId]) ||
        !is_object($datos['datosAlgoritmo'][$postId]) || // Anadido is_object()
        !isset($datos['datosAlgoritmo'][$postId]->meta_value)
    ) {
        // Si alguna comprobacion falla, retornar los puntos actuales (0)
        return $pIntereses;
    }

    // Ahora es seguro acceder a meta_value
    $metaValue = $datos['datosAlgoritmo'][$postId]->meta_value;
    $datosAlgoritmo = json_decode($metaValue, true);

    // Verificar si el json_decode fue exitoso
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosAlgoritmo)) {
        // Si la decodificacion falla o no es un array, retornar puntos actuales
        return $pIntereses;
    }

    $oneshot = ['one shot', 'one-shot', 'oneshot'];
    $esOneShot = false;
    // $metaValue ya fue asignada y usada para json_decode, la reasignacion original era redundante
    // $metaValue = $datos['datosAlgoritmo'][$postId]->meta_value; // Linea original eliminada implicitamente al usar $metaValue de arriba

    // Usar la variable $metaValue ya existente
    if (!empty($metaValue) && is_string($metaValue)) { // Asegurar que es string para stripos
        foreach ($oneshot as $palabra) {
            if (stripos($metaValue, $palabra) !== false) {
                $esOneShot = true;
                break;
            }
        }
    }

    // Iterar sobre los datos decodificados
    foreach ($datosAlgoritmo as $key => $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        if (isset($datos['interesesUsuario'][$item])) {
                            // Asumiendo que $datos['interesesUsuario'][$item] es un objeto con propiedad intensity
                            // Idealmente, anadir aqui tambien is_object() y isset()->intensity
                            $pIntereses += 10 + $datos['interesesUsuario'][$item]->intensity;
                        }
                    }
                }
            }
        } elseif (!empty($value) && isset($datos['interesesUsuario'][$value])) {
            // Asumiendo que $datos['interesesUsuario'][$value] es un objeto con propiedad intensity
            // Idealmente, anadir aqui tambien is_object() y isset()->intensity
            $pIntereses += 10 + $datos['interesesUsuario'][$value]->intensity;
        }
    }

    if ($esOneShot) {
        $pIntereses *= 1;
    }

    return $pIntereses;
}
