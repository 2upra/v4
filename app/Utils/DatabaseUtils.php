<?php

// Refactor(Org): Moved function comprobarConexionBD from app/Content/Logic/datosParaCalculo.php
function comprobarConexionBD() {
    global $wpdb;
    $tiempoInicio = microtime(true);

    if (!$wpdb) {
        //guardarLog("[comprobarConexionBD] Error crítico: No se pudo acceder a la base de datos wpdb");
        //rendimientolog("[comprobarConexionBD] Terminó con error crítico (sin acceso a \$wpdb) en " . (microtime(true) - $tiempoInicio) . " segundos");
        return false;
    }
    return true;
}

// Refactor(Org): Moved function obtenerDatosJSON from app/Finanza/Graficos.php and adapted to use $wpdb
/**
 * Obtiene datos de una tabla específica y los devuelve en formato JSON para gráficos.
 *
 * @param string $tabla Nombre de la tabla (sin prefijo de WP).
 * @param string $columnaTiempo Nombre de la columna que contiene la marca de tiempo.
 * @param string $columnaValor Nombre de la columna que contiene el valor.
 * @return string JSON con los datos [{time: ..., value: ...}, ...] o '[]' en caso de error.
 */
function obtenerDatosJSON($tabla, $columnaTiempo, $columnaValor) {
    global $wpdb;
    $datos = [];
    $tabla_completa = $wpdb->prefix . $tabla;

    // Validar $tabla, $columnaTiempo, $columnaValor sería ideal aquí para prevenir SQL Injection
    // si estos valores pudieran venir de fuentes no confiables.
    // Asumiendo que son seguros por ahora, ya que provienen de llamadas internas.

    // Construir la consulta SQL de forma segura
    // Nota: $wpdb->prepare no sustituye identificadores (nombres de tabla/columna).
    // Escapamos los nombres de tabla y columna con backticks.
    $query = $wpdb->prepare(
        "SELECT `%s` as time, `%s` as value FROM `%s` ORDER BY `%s` DESC",
        $columnaTiempo, $columnaValor, $tabla_completa, $columnaTiempo
    );
    // La consulta anterior usa prepare incorrectamente para identificadores.
    // Corrección: Construir la consulta validando/escapando identificadores manualmente si es necesario.
    // Dado que los nombres de columna vienen del código, se asumen seguros y se usan directamente.
    // Se usan backticks para asegurar que funcionen aunque sean palabras reservadas.
    $query = $wpdb->prepare("SELECT * FROM `{$tabla_completa}` ORDER BY `{$columnaTiempo}` DESC");

    $resultados = $wpdb->get_results($query);

    if ($resultados === null) {
        // Opcional: Registrar el error de $wpdb
        // error_log("Error de WPDB en obtenerDatosJSON para tabla {$tabla_completa}: " . $wpdb->last_error);
        return json_encode([]); // Devolver array vacío en caso de error
    }

    foreach ($resultados as $row) {
        // Acceder a las propiedades del objeto $row usando los nombres de columna originales
        if (isset($row->$columnaTiempo) && isset($row->$columnaValor)) {
             $datos[] = ['time' => $row->$columnaTiempo, 'value' => $row->$columnaValor];
        } else {
             // Opcional: Registrar advertencia si falta alguna columna esperada
             // error_log("Advertencia en obtenerDatosJSON: Fila incompleta encontrada en tabla {$tabla_completa}.");
        }
    }
    return json_encode($datos);
}

// Refactor(Org): Moved function getDatabaseConnection from app/Finanza/Graficos.php
// Función para obtener la conexión a la base de datos
function getDatabaseConnection() {
    $mysqli = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    if ($mysqli->connect_error) {
        die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }
    return $mysqli;
}

// Refactor(Org): Moved function limpiarDatosHistoricos from app/Finanza/Graficos.php
// Función para limpiar datos históricos de una tabla (usa conexión mysqli)
function limpiarDatosHistoricos($mysqli, $tabla, $columnaTiempo) {
    // Asegurarse de que $tabla y $columnaTiempo son seguros (pueden requerir validación/escapado)
    // Asumiendo que son seguros por ahora.
    $mysqli->query("
        DELETE t1 FROM `$tabla` t1
        INNER JOIN (
            SELECT DATE(`$columnaTiempo`) as date, MAX(`$columnaTiempo`) as max_time
            FROM `$tabla`
            GROUP BY DATE(`$columnaTiempo`)
        ) t2 ON DATE(t1.`$columnaTiempo`) = t2.date AND t1.`$columnaTiempo` < t2.max_time
    ");
}


?>
