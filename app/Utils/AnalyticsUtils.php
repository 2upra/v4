<?php

// Funcion guardarVista() movida a app/Services/AnalyticsService.php
// Funcion obtenerVistasPosts() movida a app/Services/AnalyticsService.php

/**
 * Filtra un array de vistas, eliminando aquellas cuya última vista ('last_view')
 * es más antigua que un número específico de días.
 *
 * @param array $vistas Array asociativo de vistas (postId => ['count' => int, 'last_view' => timestamp]).
 * @param int $dias Número de días para el límite de antigüedad.
 * @return array El array de vistas filtrado.
 */
function limpiarVistasAntiguas($vistas, $dias)
{
    if (empty($vistas) || !is_array($vistas)) {
        return [];
    }

    $fechaLimite = time() - (absint($dias) * 86400); // 86400 segundos en un día

    foreach ($vistas as $postId => $infoVista) {
        // Asegurarse de que 'last_view' existe y es numérico
        if (!isset($infoVista['last_view']) || !is_numeric($infoVista['last_view'])) {
             // Opcional: manejar o registrar posts con datos de vista inválidos
             unset($vistas[$postId]);
             continue;
        }

        if ($infoVista['last_view'] < $fechaLimite) {
            unset($vistas[$postId]);
        }
    }

    return $vistas;
}

// Hooks para manejar la petición AJAX de guardar vistas
// ADVERTENCIA: La función 'guardarVista' fue movida a AnalyticsService.php.
// Estos hooks ahora apuntan a una función que no existe en este archivo.
// Deberán ser actualizados o movidos para que funcionen correctamente.
add_action('wp_ajax_guardar_vistas', 'guardarVista');        // Para usuarios logueados
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista'); // Para usuarios no logueados

