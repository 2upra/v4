<?

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

    // Eliminar cualquier carácter no alfanumérico
    $texto = preg_replace('/[^a-z0-9\s]+/u', '', $texto);

    return $texto;
}

function logResumenDePuntos($userId, $resumenPuntos)
{
    logAlgoritmo("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($resumenPuntos));
    $resumen_formateado = [];
    foreach ($resumenPuntos as $post_id => $puntos) {
        $resumen_formateado[] = "$post_id:$puntos";
    }
    logAlgoritmo("Resumen de puntos - " . implode(', ', $resumen_formateado));
}

add_action('wp_ajax_ajustar_zona_horaria', 'ajustarZonaHoraria');
add_action('wp_ajax_nopriv_ajustar_zona_horaria', 'ajustarZonaHoraria');

function ajustarZonaHoraria()
{
    $zona_horaria = isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC';
    setcookie('usuario_zona_horaria', $zona_horaria, time() + 86400, '/');
    wp_die();
}

function TiempoRelativoNoti($fecha)
{
    $zona_horaria_usuario = isset($_COOKIE['usuario_zona_horaria']) ? $_COOKIE['usuario_zona_horaria'] : 'UTC';
    $fechaNotificacionUTC = new DateTime($fecha, new DateTimeZone('UTC'));
    $fechaNotificacion = $fechaNotificacionUTC->setTimezone(new DateTimeZone($zona_horaria_usuario));
    $ahora = new DateTime('now', new DateTimeZone($zona_horaria_usuario));
    $diferencia = $ahora->getTimestamp() - $fechaNotificacion->getTimestamp();

    if ($diferencia < 60) {
        return 'Justo ahora';
    } elseif ($diferencia < 3600) {
        return 'hace ' . round($diferencia / 60) . ' minutos';
    } elseif ($diferencia < 86400) {
        return 'hace ' . round($diferencia / 3600) . ' horas';
    } elseif ($diferencia < 604800) {
        return 'hace ' . round($diferencia / 86400) . ' días';
    } elseif ($diferencia < 2419200) {
        return 'hace ' . round($diferencia / 604800) . ' semanas';
    } elseif ($diferencia < 29030400) {
        return 'hace ' . round($diferencia / 2419200) . ' meses';
    } else {
        return 'hace ' . round($diferencia / 29030400) . ' años';
    }
}