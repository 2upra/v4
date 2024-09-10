function generarGrafico(idCanvas, datos, color = '#fff') {
    var ctx = document.getElementById(idCanvas).getContext('2d');

    var labels = datos.map(function (e) {
        return e.time;
    });
    var data = datos.map(function (e) {
        return e.value;
    });

    new Chart(ctx, {
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

// Generar los gráficos utilizando los datos de PHP
if (typeof graficoData !== 'undefined') {
    generarGrafico('myChart', graficoData.capital);
    generarGrafico('myChartBolsa', graficoData.bolsa);
    generarGrafico('myChartHistorial', graficoData.historial);
}
