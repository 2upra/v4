// Variable global para almacenar las referencias de los gráficos
var charts = {};

function generarGrafico(idCanvas, datos, color = '#fff') {
    // Obtener el contexto del canvas
    var ctx = document.getElementById(idCanvas).getContext('2d');

    // Verificar si existe un gráfico previo en este canvas y destruirlo
    if (charts[idCanvas]) {
        charts[idCanvas].destroy();
    }

    // Extraer las etiquetas y datos de los datos proporcionados
    var labels = datos.map(function (e) {
        return e.time;
    });
    var data = datos.map(function (e) {
        return e.value;
    });

    // Crear un nuevo gráfico y almacenarlo en la variable global
    charts[idCanvas] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    data: data,
                    borderColor: color,
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false
                }
            ]
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
                    type: 'time',
                    time: {
                        unit: 'week', // Se muestra por semanas
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

// Función para inicializar los gráficos
function inicializarGraficos() {
    if (typeof graficoData !== 'undefined') {
        generarGrafico('myChart', graficoData.capital);
        generarGrafico('myChartBolsa', graficoData.bolsa);
        generarGrafico('myChartHistorial', graficoData.historial);
    }
}


