<?

function calcularFeedPersonalizado($userId, $identifier = '', $similarTo = null, $tipoUsuario = null)
{
    $tiempoInicio = microtime(true);
    $log = "calcularFeedPersonalizado: Inicio, usuario ID: $userId, identifier: $identifier, similarTo: $similarTo, tipoUsuario: $tipoUsuario \n";

    $datos = obtenerDatosFeedConCache($userId);
    $log .= "obtenerDatosFeedConCache: Duración: " . (microtime(true) - $tiempoInicio) . " segundos \n";

    if (empty($datos)) {
        $log .= "calcularFeedPersonalizado: Datos vacíos, finalizando. \n";
        //guardarLog($log);
        return [];
    }

    $usuario = obtenerUsuario($userId);
    if (empty($usuario)) {
        $log .= "calcularFeedPersonalizado: Usuario no encontrado, finalizando. \n";
        //guardarLog($log);
        return [];
    }

    $vistas = obtenerVistas($userId);
    $esAdmin = esUsuarioAdmin($usuario);
    $decaimiento = calcularDecaimiento($datos);
    $log .= "obtenerVistas, esUsuarioAdmin, calcularDecaimiento: Duración: " . (microtime(true) - $tiempoInicio) . " segundos \n";

    $puntos = calcularPuntos($datos, $esAdmin, $vistas, $identifier, $similarTo, $userId, $decaimiento, $tipoUsuario);
    $log .= "calcularPuntos: Duración: " . (microtime(true) - $tiempoInicio) . " segundos \n";

    if (empty($puntos)) {
        $log .= "calcularFeedPersonalizado: No hay puntos calculados, finalizando. \n";
        //guardarLog($log);
        return [];
    }

    $puntos = ordenarYLimitarPuntos($puntos);
    $log .= "ordenarYLimitarPuntos: Duración: " . (microtime(true) - $tiempoInicio) . " segundos \n";
    $log .= "calcularFeedPersonalizado: Fin, Duración total: " . (microtime(true) - $tiempoInicio) . " segundos \n";
    //guardarLog($log);
    return $puntos;
}

function obtenerUsuario($userId)
{
    $usuario = get_userdata($userId);
    if (!$usuario || !is_object($usuario)) {
        return [];
    }
    return $usuario;
}

function obtenerVistas($userId)
{
    $tiempoInicio = microtime(true);
    $vistas = obtenerYProcesarVistasPosts($userId);
    $duracion = microtime(true) - $tiempoInicio;
    //guardarLog("obtenerYProcesarVistasPosts: Duración: $duracion segundos");
    return $vistas;
}

function esUsuarioAdmin($usuario)
{
    return in_array('administrator', (array)$usuario->roles);
}

function calcularDecaimiento($datos)
{
    $tiempoInicio = microtime(true);
    $actual = current_time('timestamp');
    $decaimiento = [];
    foreach ($datos['author_results'] as $post) {
        $fecha = is_string($post->post_date) ? strtotime($post->post_date) : $post->post_date;
        $dias = floor(($actual - $fecha) / (3600 * 24));
        if (!isset($decaimiento[$dias])) {
            $decaimiento[$dias] = getDecayFactor($dias);
        }
    }
    $duracion = microtime(true) - $tiempoInicio;
    //guardarLog("calcularDecaimiento: Duración: $duracion segundos");
    return $decaimiento;
}

function calcularPuntos($datos, $esAdmin, $vistas, $identifier, $similarTo, $userId, $decaimiento, $tipoUsuario)
{
    $tiempoInicio = microtime(true);
    $puntos = calcularPuntosPostBatch(
        $datos['author_results'],
        $datos,
        $esAdmin,
        $vistas,
        $identifier,
        $similarTo,
        null,
        $userId,
        $decaimiento,
        $tipoUsuario
    );
    $duracion = microtime(true) - $tiempoInicio;
    //guardarLog("calcularPuntosPostBatch: Duración: $duracion segundos");
    return $puntos;
}

function ordenarYLimitarPuntos($puntos)
{
    $tiempoInicio = microtime(true);
    if (!empty($puntos)) {
        arsort($puntos);
        $puntos = array_slice($puntos, 0, POSTINLIMIT, true);
    }
    $duracion = microtime(true) - $tiempoInicio;
    //guardarLog("ordenarYLimitarPuntos: Duración: $duracion segundos");
    return $puntos;
}

function calcularPuntosPostBatch(
    $posts,
    $datos,
    $esAdmin,
    $vistas,
    $identifier = '',
    $similarTo = null,
    $actual = null,
    $usu = null,
    $decaimiento = [],
    $tipoUsuario = null
) {
    $tiempoInicio = microtime(true);
    $log = "calcularPuntosPostBatch: Inicio \n";

    if ($actual === null) {
        $actual = current_time('timestamp');
    }

    $puntos = [];
    foreach ($posts as $id => $post) {
        try {
            $pFinal = calcularPuntosParaPost(
                $id,
                $post,
                $datos,
                $esAdmin,
                $vistas,
                $identifier,
                $similarTo,
                $actual,
                $decaimiento,
                $tipoUsuario
            );

            if (is_numeric($pFinal) && $pFinal > 0) {
                $puntos[$id] = max($pFinal, 0);
            }
        } catch (Exception $e) {
            $log .= "calcularPuntosPostBatch: Excepción en post ID $id: " . $e->getMessage() . " \n";
            continue;
        }
    }

    $duracion = microtime(true) - $tiempoInicio;
    $log .= "calcularPuntosPostBatch: Fin, Duración: $duracion segundos \n";
    //guardarLog($log);
    return $puntos;
}

// Refactor(Exec): Moved function obtenerPostsSimilares from app/Services/FeedService.php
// Refactor(Org): Funciones movidas desde app/Content/Logic/feed.php
function obtenerPostsSimilares($current_user_id, $similar_to)
{
    $post_not_in = [];
    if ($similar_to) {
        $post_not_in[] = $similar_to;
        $similar_to_cache_key = "similar_to_{$similar_to}";

        // Verificar caché global en archivos
        $cached_data = obtenerCache($similar_to_cache_key);

        if ($cached_data) {
            ////guardarLog("Usuario ID: $current_user_id usando caché global para posts similares a $similar_to");
            return [
                'posts_personalizados' => $cached_data,
                'post_not_in' => $post_not_in,
            ];
        } else {
            ////guardarLog("Usuario ID: $current_user_id calculando nuevo feed similar para post ID: $similar_to");
            $posts_personalizados = calcularFeedPersonalizado(44, '', $similar_to);

            if (!$posts_personalizados) {
                ////guardarLog("Error: Fallo al calcular posts similares para post ID: $similar_to");
                return ['posts_personalizados' => [], 'post_not_in' => $post_not_in];
            }

            guardarCache($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);

            return [
                'posts_personalizados' => $posts_personalizados,
                'post_not_in' => $post_not_in,
            ];
        }
    }

    return ['posts_personalizados' => [], 'post_not_in' => $post_not_in];
}
