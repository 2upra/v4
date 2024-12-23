function inicializarBuscadores() {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent) || userAgent.includes('AppAndroid');

    const inputBusqueda = document.getElementById('identifier');
    const divResultados = document.getElementById('resultadoBusqueda');
    const inputBusquedaLocal = document.getElementById('buscadorLocal');
    const divResultadosBL = document.querySelector('.resultadosBL');

    // Función para manejar la búsqueda en el buscador local
    function manejarInputBusquedaLocal() {
        if (!inputBusquedaLocal || !divResultadosBL) return;

        const textoBusqueda = inputBusquedaLocal.value.trim();

        if (textoBusqueda.length > 0) {
            divResultadosBL.style.display = 'block';
            buscar(textoBusqueda, divResultadosBL);
        } else {
            divResultadosBL.style.display = 'none';
            divResultadosBL.innerHTML = '';
        }
    }

    // Función para crear fondo oscuro al buscar
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

    // Función para remover el fondo oscuro
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

    // Función para ocultar el fondo oscuro y los resultados de búsqueda
    function ocultarFondoYResultados() {
        removeBusquedaDarkBackground();
        if (divResultados) {
            divResultados.style.display = 'none';
            divResultados.classList.add('hidden');
        }
    }

    // Función para manejar la búsqueda en el input principal
    function manejarInputBusqueda() {
        if (!inputBusqueda || !divResultados) return;

        const textoBusqueda = inputBusqueda.value.trim();

        if (textoBusqueda.length > 0) {
            divResultados.style.display = 'flex';
            divResultados.classList.remove('hidden');
            buscar(textoBusqueda, divResultados);
            mostrarFondoOscuro();
        } else {
            ocultarFondoYResultados();
            divResultados.innerHTML = '';
        }
    }

    // Función para realizar la búsqueda (simulación de búsqueda AJAX)
    async function buscar(texto, divResultados) {
        if (!divResultados) return;

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

    // Función para mostrar los resultados de búsqueda
    function mostrarResultados(html, divResultados) {
        if (!divResultados) return;
        divResultados.innerHTML = html;
    }

    // Agregar listeners solo si no estamos en móvil o en la app Android
    if (!isMobile) {
        if (inputBusqueda) {
            inputBusqueda.removeEventListener('input', manejarInputBusqueda);
            inputBusqueda.addEventListener('input', manejarInputBusqueda);
        }
        if (divResultados) {
            // Asegúrate de manejar cualquier configuración adicional en desktop
        }
    }

    // Siempre habilitar el buscador local independientemente del dispositivo
    if (inputBusquedaLocal) {
        inputBusquedaLocal.removeEventListener('input', manejarInputBusquedaLocal);
        inputBusquedaLocal.addEventListener('input', manejarInputBusquedaLocal);
    }
}