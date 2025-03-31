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

function TiempoRelativoNoti($fecha)
{
    $zona_horaria_usuario = isset($_COOKIE['usuario_zona_horaria']) ? $_COOKIE['usuario_zona_horaria'] : 'UTC';
    // Asegurarse de que la zona horaria del usuario es válida
    try {
        $tz_usuario = new DateTimeZone($zona_horaria_usuario);
    } catch (Exception $e) {
        $tz_usuario = new DateTimeZone('UTC'); // Volver a UTC si no es válida
    }

    try {
        $fechaNotificacionUTC = new DateTime($fecha, new DateTimeZone('UTC'));
        $fechaNotificacion = $fechaNotificacionUTC->setTimezone($tz_usuario);
        $ahora = new DateTime('now', $tz_usuario);
        $diferencia = $ahora->getTimestamp() - $fechaNotificacion->getTimestamp();

        if ($diferencia < 60) {
            return 'Justo ahora';
        } elseif ($diferencia < 3600) {
            $minutos = round($diferencia / 60);
            return 'hace ' . $minutos . ($minutos > 1 ? ' minutos' : ' minuto');
        } elseif ($diferencia < 86400) {
            $horas = round($diferencia / 3600);
            return 'hace ' . $horas . ($horas > 1 ? ' horas' : ' hora');
        } elseif ($diferencia < 604800) {
            $dias = round($diferencia / 86400);
            return 'hace ' . $dias . ($dias > 1 ? ' días' : ' día');
        } elseif ($diferencia < 2419200) { // Aprox 4 semanas
            $semanas = round($diferencia / 604800);
            return 'hace ' . $semanas . ($semanas > 1 ? ' semanas' : ' semana');
        } elseif ($diferencia < 31536000) { // Aprox 1 año
            $meses = round($diferencia / 2628000); // Promedio segundos en un mes
            return 'hace ' . $meses . ($meses > 1 ? ' meses' : ' mes');
        } else {
            $anios = round($diferencia / 31536000);
            return 'hace ' . $anios . ($anios > 1 ? ' años' : ' año');
        }
    } catch (Exception $e) {
        // Manejar error si la fecha no es válida
        error_log('Error en TiempoRelativoNoti: ' . $e->getMessage());
        return $fecha; // Devolver la fecha original o un mensaje de error
    }
}
