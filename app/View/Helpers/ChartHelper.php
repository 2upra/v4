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

?>