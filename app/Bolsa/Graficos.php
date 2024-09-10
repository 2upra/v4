<?php

// Función para obtener la conexión a la base de datos
function getDatabaseConnection() {
    $mysqli = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    if ($mysqli->connect_error) {
        die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }
    return $mysqli;
}

// Función para limpiar datos históricos de una tabla
function limpiarDatosHistoricos($mysqli, $tabla, $columnaTiempo) {
    $mysqli->query("
        DELETE t1 FROM $tabla t1
        INNER JOIN (
            SELECT DATE($columnaTiempo) as date, MAX($columnaTiempo) as max_time
            FROM $tabla
            GROUP BY DATE($columnaTiempo)
        ) t2 ON DATE(t1.$columnaTiempo) = t2.date AND t1.$columnaTiempo < t2.max_time
    ");
}

// Función para actualizar o insertar un valor en una tabla
function actualizarOInsertarValor($mysqli, $tabla, $columnaTiempo, $columnaValor, $valor) {
    $current_time = time();
    $current_date = date('Y-m-d');

    $result = $mysqli->query("SELECT * FROM $tabla WHERE DATE($columnaTiempo) = '$current_date'");
    $existing_row = $result->fetch_assoc();

    if ($existing_row) {
        $last_time = strtotime($existing_row[$columnaTiempo]);
        if ($current_time - $last_time >= 600) { // Actualizar si han pasado 10 minutos
            $time = date('Y-m-d H:i:s');
            $stmt = $mysqli->prepare("UPDATE $tabla SET $columnaTiempo = ?, $columnaValor = ? WHERE DATE($columnaTiempo) = ?");
            $stmt->bind_param("sds", $time, $valor, $current_date);
            $stmt->execute();
        }
    } else { // Insertar nuevo registro
        $time = date('Y-m-d H:i:s');
        $stmt = $mysqli->prepare("INSERT INTO $tabla ($columnaTiempo, $columnaValor) VALUES (?, ?)");
        $stmt->bind_param("sd", $time, $valor);
        $stmt->execute();
    }
}

// Función para obtener datos de una tabla y convertirlos a JSON
function obtenerDatosJSON($mysqli, $tabla, $columnaTiempo, $columnaValor) {
    $datos = [];
    $result = $mysqli->query("SELECT * FROM $tabla ORDER BY $columnaTiempo DESC");
    while ($row = $result->fetch_assoc()) {
        $datos[] = ['time' => $row[$columnaTiempo], 'value' => $row[$columnaValor]];
    }
    return json_encode($datos);
}

// Función para generar el código del gráfico
function generarCodigoGrafico($idCanvas, $datosJSON) {
    return '
    <canvas id="' . $idCanvas . '"></canvas>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("' . $idCanvas . '").getContext("2d");
            var datos = ' . $datosJSON . ';

            var labels = datos.map(function(e) { return e.time; });
            var data = datos.map(function(e) { return e.value; });

            new Chart(ctx, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        borderColor: "#fff",
                        borderWidth: 2,
                        pointRadius: 0, 
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            type: "time",
                            time: {
                                unit: "day",
                                stepSize: 1
                            },
                            ticks: {
                                display: true
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: false,
                            ticks: {
                                display: false
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>';
}


function capitalValores() {
    $resultado = calc_ing(48, false);
    $valEmp = $resultado['valEmp'];

    $mysqli = getDatabaseConnection();
    limpiarDatosHistoricos($mysqli, 'capital', 'time1');
    actualizarOInsertarValor($mysqli, 'capital', 'time1', 'value1', $valEmp);
    $datosJSON = obtenerDatosJSON($mysqli, 'capital', 'time1', 'value1');
    $mysqli->close();

    return generarCodigoGrafico('myChart', $datosJSON);
}

function bolsavalores() {
    $resultado = calc_ing(48, false);
    $valAcc = $resultado['valAcc'];

    $mysqli = getDatabaseConnection();
    limpiarDatosHistoricos($mysqli, 'bolsa', 'time');
    actualizarOInsertarValor($mysqli, 'bolsa', 'time', 'value', $valAcc);
    $datosJSON = obtenerDatosJSON($mysqli, 'bolsa', 'time', 'value');
    $mysqli->close();

    return generarCodigoGrafico('myChartBolsa', $datosJSON);
}

function graficoHistorialAcciones() {
    $historial = obtenerHistorialAccionesUsuario();
    $datos = [];
    foreach ($historial as $registro) {
        $datos[] = ['time' => $registro->fecha, 'value' => $registro->acciones]; 
    }
    $datosJSON = json_encode($datos);

    return generarCodigoGrafico('myChartHistorial', $datosJSON);
}

