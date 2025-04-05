<?php

add_action('wp_ajax_ajustar_zona_horaria', 'ajustarZonaHoraria');
add_action('wp_ajax_nopriv_ajustar_zona_horaria', 'ajustarZonaHoraria');

function ajustarZonaHoraria()
{
    $zona_horaria = isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC';
    // Asegurarse de que la zona horaria es válida antes de usarla
    try {
        new DateTimeZone($zona_horaria);
    } catch (Exception $e) {
        $zona_horaria = 'UTC'; // Volver a UTC si no es válida
    }
    setcookie('usuario_zona_horaria', $zona_horaria, time() + 86400, '/');
    wp_die();
}

// Refactor(Org): Funcion TiempoRelativoNoti movida a app/View/Helpers/NotificationHelper.php

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
