<?


/**
 * Función auxiliar para remover acentos y caracteres especiales
 * 
 * @param string $string Cadena de entrada
 * @return string Cadena sin acentos
 */
function remove_accents($string) {
    if (!preg_match('/[\x80-\xff]/', $string)) {
        return $string;
    }

    $chars = array(
        // Decompositions for Latin-1 Supplement
        chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
        chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
        chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
        chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
        chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
        chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
        chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
        chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
        chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
        chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
        chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
        chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
        chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
        chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
        chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
        chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
        chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
        chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
        chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
        chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
        chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
        chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
        chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
        chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
        chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
        chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
        chr(195).chr(189) => 'y', chr(195).chr(191) => 'y'
    );

    return strtr($string, $chars);
}


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