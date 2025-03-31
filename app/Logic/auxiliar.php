<?

function logResumenDePuntos($userId, $resumenPuntos)
{
    logAlgoritmo("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($resumenPuntos));
    $resumen_formateado = [];
    foreach ($resumenPuntos as $post_id => $puntos) {
        $resumen_formateado[] = "$post_id:$puntos";
    }
    logAlgoritmo("Resumen de puntos - " . implode(', ', $resumen_formateado));
}

