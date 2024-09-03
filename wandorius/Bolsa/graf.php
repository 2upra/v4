<?php

function capitalValores()
{
    $resultado = calc_ing(48, false);
    $valEmp = $resultado['valEmp'];

    $mysqli = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    if ($mysqli->connect_error) {
        die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }

    // Primero, limpiar los datos históricos
    $mysqli->query("
        DELETE c1 FROM capital c1
        INNER JOIN (
            SELECT DATE(time1) as date, MAX(time1) as max_time
            FROM capital
            GROUP BY DATE(time1)
        ) c2 ON DATE(c1.time1) = c2.date AND c1.time1 < c2.max_time
    ");

    $current_time = time();
    $current_date = date('Y-m-d');

    // Verificar si existe un registro para el día actual
    $result = $mysqli->query("SELECT * FROM capital WHERE DATE(time1) = '$current_date'");
    $existing_row = $result->fetch_assoc();

    if ($existing_row) {
        $last_time = strtotime($existing_row['time1']);

        // Actualizar si han pasado 10 minutos
        if ($current_time - $last_time >= 600) {
            $time1 = date('Y-m-d H:i:s');
            $stmt = $mysqli->prepare("UPDATE capital SET time1 = ?, value1 = ? WHERE DATE(time1) = ?");
            $stmt->bind_param("sds", $time1, $valEmp, $current_date);
            $stmt->execute();
        }
    } else {
        // Insertar nuevo registro para el día actual
        $time1 = date('Y-m-d H:i:s');
        $stmt = $mysqli->prepare("INSERT INTO capital (time1, value1) VALUES (?, ?)");
        $stmt->bind_param("sd", $time1, $valEmp);
        $stmt->execute();
    }

    // Obtener todos los datos para JSON
    $datos1 = [];
    $result = $mysqli->query("SELECT * FROM capital ORDER BY time1 DESC");
    while ($row = $result->fetch_assoc()) {
        $datos1[] = ['time' => $row['time1'], 'value' => $row['value1']];
    }
    $mysqli->close();

    // Convertir datos a JSON
    $datos1_json = json_encode($datos1);
    // Generar código para el gráfico
    $codigo = '
    <canvas id="myChart"></canvas>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("myChart").getContext("2d");
            var datos1 = ' . $datos1_json . ';

            // No filtramos los datos, los usamos todos
            var labels = datos1.map(function(e) { return e.time; });
            var data = datos1.map(function(e) { return e.value; });

            new Chart(ctx, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        borderColor: "#fff",
                        borderWidth: 2,
                        pointRadius: 0,  // Eliminar puntos en la línea
                        fill: false
                    }]
                },
                options: {
                    responsive: true,  // Habilitar la capacidad de respuesta
                    maintainAspectRatio: false,  // No mantener la proporción original
                    plugins: {
                        legend: {
                            display: false  // Ocultar la leyenda
                        }
                    },
                    scales: {
                        x: {
                            type: "time",
                            time: {
                                unit: "day",  // Mostrar solo días
                                stepSize: 1
                            },
                            ticks: {
                                display: false  // Ocultar etiquetas del eje X
                            },
                            grid: {
                                display: false  // Ocultar líneas de la cuadrícula
                            }
                        },
                        y: {
                            beginAtZero: false,
                            ticks: {
                                display: false  // Ocultar etiquetas del eje Y
                            },
                            grid: {
                                display: false  // Ocultar líneas de la cuadrícula
                            }
                        }
                    }
                }
            });
        });
    </script>';

    return $codigo;
}
function bolsavalores()
{
    $resultado = calc_ing(48, false);
    $valAcc = $resultado['valAcc'];

    $mysqli = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    if ($mysqli->connect_error) {
        die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }

    // Limpiar los datos históricos
    $mysqli->query("
        DELETE b1 FROM bolsa b1
        INNER JOIN (
            SELECT DATE(time) as date, MAX(time) as max_time
            FROM bolsa
            GROUP BY DATE(time)
        ) b2 ON DATE(b1.time) = b2.date AND b1.time < b2.max_time
    ");

    $current_time = time();
    $current_date = date('Y-m-d');

    // Verificar si existe un registro para el día actual
    $result = $mysqli->query("SELECT * FROM bolsa WHERE DATE(time) = '$current_date'");
    $existing_row = $result->fetch_assoc();

    if ($existing_row) {
        $last_time = strtotime($existing_row['time']);

        // Actualizar si han pasado 10 minutos
        if ($current_time - $last_time >= 600) {
            $time = date('Y-m-d H:i:s');
            $stmt = $mysqli->prepare("UPDATE bolsa SET time = ?, value = ? WHERE DATE(time) = ?");
            $stmt->bind_param("sds", $time, $valAcc, $current_date);
            $stmt->execute();
        }
    } else {
        // Insertar nuevo registro para el día actual
        $time = date('Y-m-d H:i:s');
        $stmt = $mysqli->prepare("INSERT INTO bolsa (time, value) VALUES (?, ?)");
        $stmt->bind_param("sd", $time, $valAcc);
        $stmt->execute();
    }

    // Obtener todos los datos para JSON
    $datos = [];
    $result = $mysqli->query("SELECT * FROM bolsa ORDER BY time DESC");
    while ($row = $result->fetch_assoc()) {
        $datos[] = ['time' => $row['time'], 'value' => $row['value']];
    }
    $mysqli->close();

    // Convertir datos a JSON
    $datos_json = json_encode($datos);

    // Generar código para el gráfico
    $codigo = '
    <canvas id="myChartBolsa"></canvas>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("myChartBolsa").getContext("2d");
            var datos = ' . $datos_json . ';

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
                        pointRadius: 0,  // Eliminar puntos en la línea
                        fill: false
                    }]
                },
                options: {
                    responsive: true,  // Habilitar la capacidad de respuesta
                    maintainAspectRatio: false,  // No mantener la proporción original
                    plugins: {
                        legend: {
                            display: false  // Ocultar la leyenda
                        }
                    },
                    scales: {
                        x: {
                            type: "time",
                            time: {
                                unit: "day",  // Mostrar solo días
                                stepSize: 1
                            },
                            ticks: {
                                display: false  // Ocultar etiquetas del eje X
                            },
                            grid: {
                                display: false  // Ocultar líneas de la cuadrícula
                            }
                        },
                        y: {
                            beginAtZero: false,
                            ticks: {
                                display: false  // Ocultar etiquetas del eje Y
                            },
                            grid: {
                                display: false  // Ocultar líneas de la cuadrícula
                            }
                        }
                    }
                }
            });
        });
    </script>';

    return $codigo;
}
function graficoHistorialAcciones()
{

    $historial = obtenerHistorialAccionesUsuario();
    $datos = [];
    foreach ($historial as $registro) {
        $datos[] = ['fecha' => $registro->fecha, 'acciones' => $registro->acciones];
    }
    $datos_json = json_encode($datos);
    $codigo = '
    <canvas id="myChartHistorial"></canvas>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("myChartHistorial").getContext("2d");
            var datos = ' . $datos_json . ';

            var labels = datos.map(function(e) { return e.fecha; });
            var data = datos.map(function(e) { return e.acciones; });

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
                    responsive: true,  // Habilitar la capacidad de respuesta
                    maintainAspectRatio: false,  // No mantener la proporción original
                    plugins: {
                        legend: {
                            display: false  // Ocultar la leyenda
                        }
                    },
                    scales: {
                        x: {
                            type: "time",
                            time: {
                                unit: "day",  // Mostrar solo días
                                stepSize: 1
                            },
                            ticks: {
                                display: false  // Ocultar etiquetas del eje X
                            },
                            grid: {
                                display: false  // Ocultar líneas de la cuadrícula
                            }
                        },
                        y: {
                            beginAtZero: false,
                            ticks: {
                                display: false  // Ocultar etiquetas del eje Y
                            },
                            grid: {
                                display: false  // Ocultar líneas de la cuadrícula
                            }
                        }
                    }
                }
            });
        });
    </script>';

    return $codigo;
}
