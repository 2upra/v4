<?php

// Helper para funciones relacionadas con fechas en la vista

// Refactor(Org): Funcion tiempoRelativo() movida desde app/Utils/DateUtils.php
function tiempoRelativo($fecha)
{
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;

    if ($diferencia < 60) {
        return 'unos segundos';
    } elseif ($diferencia < 3600) {
        $minutos = floor($diferencia / 60);
        return "$minutos minuto" . ($minutos > 1 ? 's' : '');
    } elseif ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        return "$horas hora" . ($horas > 1 ? 's' : '');
    } elseif ($diferencia < 604800) {
        $dias = floor($diferencia / 86400);
        return "$dias día" . ($dias > 1 ? 's' : '');
    } else {
        $semanas = floor($diferencia / 604800);
        return "$semanas semana" . ($semanas > 1 ? 's' : '');
    }
}
