function inicializarBuscadores() {
    const inputBusqueda = document.getElementById('identifier');
    const divResultados = document.getElementById('resultadoBusqueda');
    const inputBusquedaLocal = document.getElementById('buscadorLocal');
    const divResultadosBL = document.querySelector('.resultadosBL');

    function manejarInputBusquedaLocal() {
        const textoBusqueda = inputBusquedaLocal.value.trim();

        if (textoBusqueda.length > 0) {
            divResultadosBL.style.display = 'block';
            buscar(textoBusqueda, divResultadosBL);
        } else {
            divResultadosBL.style.display = 'none';
            divResultadosBL.innerHTML = '';
        }
    }

    function createBusquedaDarkBackground() {
        let darkBackground = document.getElementById('busquedaBackground');
        if (!darkBackground) {
            darkBackground = document.createElement('div');
            darkBackground.id = 'busquedaBackground';
            darkBackground.style.position = 'fixed';
            darkBackground.style.top = 0;
            darkBackground.style.left = 0;
            darkBackground.style.width = '100%';
            darkBackground.style.height = '100%';
            darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            darkBackground.style.zIndex = 998;
            darkBackground.style.display = 'none';
            darkBackground.style.pointerEvents = 'none';
            darkBackground.style.opacity = '0';
            darkBackground.style.transition = 'opacity 0.3s ease';
            document.body.appendChild(darkBackground);

            darkBackground.addEventListener('click', () => {
                ocultarFondoYResultados();
            });
        }

        darkBackground.style.display = 'block';
        setTimeout(() => {
            darkBackground.style.opacity = '1';
        }, 10);
        darkBackground.style.pointerEvents = 'auto';
    }

    function removeBusquedaDarkBackground() {
        const darkBackground = document.getElementById('busquedaBackground');
        if (darkBackground) {
            darkBackground.style.opacity = '0';
            setTimeout(() => {
                darkBackground.style.display = 'none';
                darkBackground.style.pointerEvents = 'none';
            }, 300);
        }
    }

    // Función para mostrar el fondo oscuro
    function mostrarFondoOscuro() {
        createBusquedaDarkBackground();
    }

    // Función para ocultar el fondo oscuro y resultadoBusqueda
    function ocultarFondoYResultados() {
        removeBusquedaDarkBackground();
        divResultados.style.display = 'none';
        divResultados.classList.add('hidden');
    }

    function manejarInputBusqueda() {
        const textoBusqueda = inputBusqueda.value.trim();

        if (textoBusqueda.length > 0) {
            divResultados.style.display = 'flex';
            divResultados.classList.remove('hidden');
            buscar(textoBusqueda, divResultados);
            mostrarFondoOscuro(); // Mostrar fondo oscuro al empezar a escribir en inputBusqueda
        } else {
            ocultarFondoYResultados(); // Ocultar si no hay texto
            divResultados.innerHTML = '';
        }
    }

    async function buscar(texto, divResultados) {
        const data = {
            busqueda: texto
        };

        const resultados = await enviarAjax('buscarResultado', data); // Asegúrate que 'enviarAjax' esté definida globalmente
        if (resultados && resultados.success) {
            mostrarResultados(resultados.data, divResultados);
        } else {
            divResultados.innerHTML = 'Error al realizar la búsqueda.';
        }
    }

    function mostrarResultados(html, divResultados) {
        divResultados.innerHTML = html;
    }

    // Elimina los listeners previos si existen
    inputBusquedaLocal.removeEventListener('input', manejarInputBusquedaLocal);
    inputBusqueda.removeEventListener('input', manejarInputBusqueda);

    // Agrega los listeners
    inputBusquedaLocal.addEventListener('input', manejarInputBusquedaLocal);
    inputBusqueda.addEventListener('input', manejarInputBusqueda);
}
