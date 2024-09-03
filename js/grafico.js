function grafico() {
    // Verificar si los datos están definidos
    if (typeof datos1 === 'undefined' || !Array.isArray(datos1)) {
        return;
    }

    // Convertir las fechas a timestamps
    datos1 = datos1.map(dato => {
        dato.time = new Date(dato.time).getTime() / 1000;
        return dato;
    });

    // Obtener el contenedor del gráfico
    var chartContainer = document.getElementById("miGraficoCapital");
    if (!chartContainer) {
        return;
    }

    // Establecer un tamaño inicial para el contenedor del gráfico
    chartContainer.style.width = '100%';
    chartContainer.style.height = '400px'; // Ajusta esto al tamaño que necesites

    // Crear el gráfico con el tamaño del contenedor
    var chart = LightweightCharts.createChart(chartContainer, {
        width: chartContainer.clientWidth,
        height: chartContainer.clientHeight,
        rightPriceScale: {
            borderVisible: false,
        },
        timeScale: {
            borderVisible: false,
        },
    });

    // Añadir la serie de área
    var areaSeries = chart.addAreaSeries({
        topColor: "rgba(33, 150, 243, 0.56)",
        bottomColor: "rgba(33, 150, 243, 0.04)",
        lineColor: "rgba(33, 150, 243, 1)",
        lineWidth: 2,
    });

    areaSeries.setData(datos1);

    // Definir los temas
    var darkTheme = {
        chart: {
            layout: {
                background: {
                    type: "solid",
                    color: "#0a0a0a00",
                },
                lineColor: "#2B2B4300",
                textColor: "#D9D9D9",
            },
            watermark: {
                color: "rgba(0, 0, 0, 0)",
            },
            crosshair: {
                color: "#758696",
            },
            grid: {
                vertLines: {
                    color: "#2B2B4300",
                },
                horzLines: {
                    color: "#363C4E00",
                },
            },
        },
        series: {
            topColor: "rgba(251, 0, 0, 0.56)",
            bottomColor: "rgba(251, 0, 0, 0.04)",
            lineColor: "rgba(251, 0, 0, 1)",
        },
    };

    var lightTheme = {
        chart: {
            layout: {
                background: {
                    type: "solid",
                    color: "#FFFFFF",
                },
                lineColor: "#2B2B43",
                textColor: "#191919",
            },
            watermark: {
                color: "rgba(0, 0, 0, 0)",
            },
            grid: {
                vertLines: {
                    visible: false,
                },
                horzLines: {
                    color: "#f0f3fa",
                },
            },
        },
        series: {
            topColor: "rgba(33, 150, 243, 0.56)",
            bottomColor: "rgba(33, 150, 243, 0.04)",
            lineColor: "rgba(33, 150, 243, 1)",
        },
    };

    var themesData = {
        Dark: darkTheme,
        Light: lightTheme,
    };

    function syncToTheme(theme) {
        if (themesData[theme]) {
            chart.applyOptions(themesData[theme].chart);
            areaSeries.applyOptions(themesData[theme].series);
        } else {
            console.error("Tema desconocido:", theme);
        }
    }

    syncToTheme("Dark");

    // Función para redimensionar el gráfico
    function resizeChart() {
        if (chartContainer.clientWidth > 0 && chartContainer.clientHeight > 0) {
            chart.resize(chartContainer.clientWidth, chartContainer.clientHeight);
            chart.timeScale().fitContent();
        }
    }

    // Verificar si ResizeObserver está disponible
    if (typeof ResizeObserver !== 'undefined') {
        // Observador de mutaciones para detectar cambios en el tamaño del contenedor
        var resizeObserver = new ResizeObserver(entries => {
            for (let entry of entries) {
                if (entry.target === chartContainer) {
                    resizeChart();
                }
            }
        });

        resizeObserver.observe(chartContainer);
    } else {
        console.warn("ResizeObserver no está disponible en este navegador.");
    }

    // Redimensionar al cargar la página y cuando se redimensiona la ventana
    window.addEventListener("load", resizeChart);
    window.addEventListener("resize", resizeChart);

    // Forzar múltiples redimensionamientos con diferentes retrasos
    setTimeout(resizeChart, 0);
    setTimeout(resizeChart, 50);
    setTimeout(resizeChart, 100);
    setTimeout(resizeChart, 200);
}
