<?php

// Funciones auxiliares para generar el código HTML/JS de gráficos (ej: Chart.js)
// Función movida desde app/Finanza/Graficos.php

function generarCodigoGrafico($idCanvas, $datosJSON) {
    return '
    <canvas id="' . $idCanvas . '"></canvas>
    <script type="text/javascript">
        (function() {
            var chart_' . $idCanvas . ';

            function generarGrafico_' . $idCanvas . '() {
                var ctx = document.getElementById("' . $idCanvas . '").getContext("2d");
                var datos = ' . $datosJSON . ';

                var labels = datos.map(function(e) { return e.time; });
                var data = datos.map(function(e) { return e.value; });

                // Destruir el gráfico existente si ya existe
                if (chart_' . $idCanvas . ') {
                    chart_' . $idCanvas . '.destroy();
                }

                chart_' . $idCanvas . ' = new Chart(ctx, {
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
                                    unit: "week", // Se muestra por semanas
                                    stepSize: 7 // 1 semana por tick
                                },
                                ticks: {
                                    display: false
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
            }

            // Verificar si Chart.js está cargado antes de ejecutar la función generarGrafico
            function esperarChartJS_' . $idCanvas . '() {
                if (typeof Chart !== "undefined") {
                    generarGrafico_' . $idCanvas . '();
                } else {
                    setTimeout(esperarChartJS_' . $idCanvas . ', 100); // Vuelve a verificar en 100ms
                }
            }

            // Llamar a la función de espera
            esperarChartJS_' . $idCanvas . '();
        })();
    </script>';
}

// Refactor(Org): Moved function capitalValores from app/Finanza/Graficos.php
function capitalValores() {
    $resultado = calc_ing(48, false); // Requires EconomyCalculationService.php
    $valEmp = $resultado['valEmp'];

    // Asegúrate de que DatabaseUtils.php se incluye donde se llama esta función
    $mysqli = getDatabaseConnection(); // Requires DatabaseUtils.php
    limpiarDatosHistoricos($mysqli, 'capital', 'time1'); // Requires DatabaseUtils.php
    // La siguiente llamada fallará porque actualizarOInsertarValor fue movida y modificada (ya no usa $mysqli)
    // Se necesitará refactorizar esta llamada para usar la nueva función de DatabaseUtils.php
    actualizarOInsertarValor('capital', 'time1', 'value1', $valEmp); // Requires DatabaseUtils.php (adapted to $wpdb)
    // Llama a la función movida (asegúrate de que DatabaseUtils.php esté incluido)
    // Esta llamada también necesita ser actualizada para no pasar $mysqli si se adapta obtenerDatosJSON a $wpdb
    $datosJSON = obtenerDatosJSON('capital', 'time1', 'value1'); // Requires DatabaseUtils.php (adapted to $wpdb)
    // $mysqli->close(); // No longer needed if using $wpdb

    // Llama a la función local generarCodigoGrafico
    return generarCodigoGrafico('myChart', $datosJSON);
}

// Refactor(Org): Moved function bolsavalores from app/Finanza/Graficos.php
function bolsavalores() {
    $resultado = calc_ing(48, false); // Requires EconomyCalculationService.php
    $valAcc = $resultado['valAcc'];

    // Asegúrate de que DatabaseUtils.php se incluye donde se llama esta función
    $mysqli = getDatabaseConnection(); // Requires DatabaseUtils.php (Note: getDatabaseConnection now returns $wpdb)
    limpiarDatosHistoricos($mysqli, 'bolsa', 'time'); // Requires DatabaseUtils.php (Note: This still expects mysqli, potential issue later)
    // La siguiente llamada fallará porque actualizarOInsertarValor fue movida y modificada (ya no usa $mysqli)
    // Se necesitará refactorizar esta llamada para usar la nueva función de DatabaseUtils.php
    actualizarOInsertarValor('bolsa', 'time', 'value', $valAcc); // Requires DatabaseUtils.php (adapted to $wpdb)
    // Llama a la función movida (asegúrate de que DatabaseUtils.php esté incluido)
    // Esta llamada también necesita ser actualizada para no pasar $mysqli si se adapta obtenerDatosJSON a $wpdb
    $datosJSON = obtenerDatosJSON('bolsa', 'time', 'value'); // Requires DatabaseUtils.php (adapted to $wpdb)
    // $mysqli->close(); // No longer needed if using $wpdb, and $mysqli might be $wpdb now depending on getDatabaseConnection

    // Llama a la función local generarCodigoGrafico
    return generarCodigoGrafico('myChartBolsa', $datosJSON); // This function is now local
}

// Refactor(Org): Moved function graficoHistorialAcciones from app/Finanza/Graficos.php
function graficoHistorialAcciones() {
    $historial = obtenerHistorialAccionesUsuario(); // Requires EconomyCalculationService.php
    $datos = [];
    foreach ($historial as $registro) {
        $datos[] = ['time' => $registro->fecha, 'value' => $registro->acciones]; 
    }
    $datosJSON = json_encode($datos);

    // Llama a la función local generarCodigoGrafico
    return generarCodigoGrafico('myChartHistorial', $datosJSON); // This function is now local
}

?>
