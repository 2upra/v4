const pageCache = {};

const isFirefox = typeof InstallTrigger !== 'undefined';

if (isFirefox) {
  // Agrega la clase "firefox" al body
  document.body.classList.add('firefox');

  // Inserta el SVG para el filtro de desenfoque
  const svg = `
    <svg style="display: none;">
      <filter id="blur-effect">
        <feGaussianBlur stdDeviation="10" />
      </filter>
    </svg>`;
  document.body.insertAdjacentHTML('afterbegin', svg);
}

function inicializarScripts() {
    ['inicializarWaveforms', 'inicializarReproductorAudio', 'minimizarform', 'selectorformtipo', 'ajax_submit', 'borrarcomentario', 'colab', 'configuser', 'deletepost', 'diferidopost', 'editarcomentario', 'like', 'notificacioncolab', 'busqueda', 'updateBackgroundColor', 'presentacionmusic', 'seguir', 'registro', 'comentarios', 'botoneditarpost', 'fan', 'perfilpanel', 'smooth', 'navpanel', 'borderborder', 'initializeFormFunctions', 'initializeModalregistro', 'submenu', 'selectortipousuario', 'empezarcolab', 'subidaRolaForm', 'avances', 'updateDates', 'initializeProgressSegments', 'initializeCustomTooltips', 'fondoAcciones', 'pestanasgroup', 'manejoDeLogs', 'progresosinteractive', 'setupScrolling', 'inicializarDescargas', 'handleAllRequests', 'textflux', 'autoFillUserInfo', 'inicializarPestanas', 'meta', 'reporteScript', 'generarGrafico', 'grafico', 'IniciadoresConfigPerfil', 'proyectoForm', 'inicializarAlerta', 'autoRows', 'iniciarRS', 'initializeUI', 'tagsPosts', 'vistaPost', 'initEditWordPress', 'reiniciarCargaDiferida', 'registrarVistas', 'colec', 'cambiarFiltroTiempo', 'filtrosPost'].forEach(func => {
        if (typeof window[func] === 'function') {
            try {
                window[func]();
            } catch (error) {
                console.error(`Error al ejecutar ${func}:`, error);
            }
        }
    });
}

function reinicializar() {
    inicializarScripts();
    if (window.location.hash && typeof window.mostrarPestana === 'function') {
        window.mostrarPestana(window.location.hash);
    }
}

window.reinicializar = reinicializar;

function loadStripe(callback) {
    if (typeof Stripe !== 'undefined') return callback();
    const script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    script.async = true;
    script.onload = callback;
    document.head.appendChild(script);
}

function initializeStripeFunctions() {
    ['stripepro', 'stripecompra'].forEach(func => {
        if (typeof window[func] === 'function') window[func]();
        else console.warn(`${func} no está definida`);
    });
}

function shouldCache(url) {
    return !['https://2upra.com/nocache'].some(noCacheUrl => new RegExp(noCacheUrl.replace(/\*/g, '.*')).test(url));
}


function loadContent(enlace, isPushState) {
    console.log('Iniciando carga de contenido:', enlace);
    const lowerEnlace = enlace.trim().toLowerCase();
    if (!enlace || lowerEnlace.startsWith('javascript:') || lowerEnlace.startsWith('data:') || lowerEnlace.startsWith('vbscript:') || enlace.includes('#') || enlace.includes('descarga_token')) return;

    if (pageCache[enlace] && shouldCache(enlace)) {
        document.getElementById('content').innerHTML = pageCache[enlace];
        if (isPushState) history.pushState(null, '', enlace);
        return reinicializar();
    }

    const loadingBar = document.getElementById('loadingBar');
    loadingBar.style.cssText = 'width: 70%; opacity: 1; transition: width 0.4s ease';

    fetch(enlace)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const content = doc.getElementById('content').innerHTML;
            document.getElementById('content').innerHTML = content;

            if (shouldCache(enlace)) pageCache[enlace] = content;

            loadingBar.style.cssText = 'width: 100%; transition: width 0.1s ease, opacity 0.3s ease';
            setTimeout(() => loadingBar.style.cssText = 'width: 0%; opacity: 0', 100);

            if (isPushState) history.pushState(null, '', enlace);

            doc.querySelectorAll('script').forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                document.body.appendChild(newScript);
            });

            setTimeout(reinicializar, 100);
        })
        .catch(error => console.error('Error al cargar la página:', error));
}

document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.href.includes('?fb-edit=1')) {
        if (!window.galleInicializado && typeof window.galle === 'function') {
            window.galle();
            window.galleInicializado = true;
        }
        reinicializar();
        loadStripe(initializeStripeFunctions);
    }

    function handleContentLoad(event, enlace, element) {
        // If the element or its parent has 'no-ajax' class, return true (bypass AJAX).
        if (element.classList.contains('no-ajax') || element.closest('.no-ajax')) return true;
    
        // Ensure the 'enlace' is a valid string before proceeding.
        if (typeof enlace !== 'string' || !enlace) {
            console.warn('Invalid enlace:', enlace);
            return true;
        }
    
        // Convert the enlace to lowercase and trim it.
        const lowerCaseLink = enlace.trim().toLowerCase();
    
        // Check if it's a valid link that should be handled via AJAX.
        if (lowerCaseLink.endsWith('.pdf') || 
            ['https://2upra.com/nocache', 'javascript:', 'data:', 'vbscript:'].some(prefix => lowerCaseLink.startsWith(prefix)) || 
            enlace.includes('#')) {
            return true;
        }
    
        // Prevent the default link click behavior and load the content via AJAX.
        event.preventDefault();
        loadContent(enlace, true);
    }
    
    document.querySelectorAll('a, button a, .botones-panel').forEach(element => {
        element.addEventListener('click', function (event) {
            const enlace = this.getAttribute('href') || this.getAttribute('data-href') || this.querySelector('a')?.getAttribute('href');
            return handleContentLoad(event, enlace, this);
        });
    });

    window.addEventListener('popstate', () => loadContent(location.href, false));
});