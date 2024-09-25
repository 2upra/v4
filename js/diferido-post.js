// Caché para las páginas cargadas
const pageCache = {};

// Inicializa los scripts definidos en el array
function inicializarScripts() {
    const scriptsToInitialize = [
        'inicializarWaveforms', 'inicializarReproductorAudio', 'minimizarform', 'selectorformtipo',
        'ajax_submit', 'borrarcomentario', 'colab', 'configuser', 'deletepost', 'diferidopost', 'editarcomentario',
        'like', 'notificacioncolab', 'busqueda', 'updateBackgroundColor', 'presentacionmusic', 'seguir', 'registro',
        'comentarios', 'botoneditarpost', 'fan', 'perfilpanel', 'smooth', 'navpanel', 'borderborder',
        'initializeFormFunctions', 'initializeModalregistro', 'submenu', 'selectortipousuario', 'empezarcolab',
        'subidaRolaForm', 'avances', 'updateDates', 'initializeProgressSegments', 'initializeCustomTooltips',
        'fondoAcciones', 'pestanasgroup', 'manejoDeLogs', 'progresosinteractive', 'setupScrolling', 'inicializarDescargas',
        'handleAllRequests', 'textflux', 'autoFillUserInfo', 'inicializarPestanas', 'meta', 'reporteScript',
        'reiniciarDiferidoPost', 'generarGrafico', 'grafico', 'IniciadoresConfigPerfil', 'proyectoForm',
        'inicializarAlerta', 'autoRows', 'iniciarRS'
    ];

    scriptsToInitialize.forEach(funcName => {
        const func = window[funcName];
        if (typeof func === 'function') {
            try {
                func();
            } catch (error) {
                console.error(`Error al ejecutar ${funcName}:`, error);
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
    const stripeFunctions = ['stripepro', 'stripecompra'];
    stripeFunctions.forEach(funcName => {
        const func = window[funcName];
        if (typeof func === 'function') {
            try {
                func();
            } catch (error) {
                console.error(`Error al ejecutar ${funcName}:`, error);
            }
        } else {
            console.warn(`${funcName} no está definida`);
        }
    });
}

function shouldCache(url) {
    const noCacheUrls = ['https://2upra.com/nocache'];
    return !noCacheUrls.some(noCacheUrl => new RegExp(noCacheUrl.replace('*', '.*')).test(url));
}

async function loadContent(url, isPushState) {
    console.log('Iniciando carga de contenido:', url);
    if (!url || url.startsWith('javascript:') || url.includes('#') || url.includes('descarga_token')) return;

    const contentElement = document.getElementById('content');

    if (pageCache[url] && shouldCache(url)) {
        contentElement.innerHTML = pageCache[url];
        if (isPushState) history.pushState(null, '', url);
        reinicializar();
        return;
    }

    const loadingBar = document.getElementById('loadingBar');
    loadingBar.style.width = '70%';
    loadingBar.style.opacity = '1';
    loadingBar.style.transition = 'width 0.4s ease';

    try {
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`Error HTTP! Estado: ${response.status}`);

        const data = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, 'text/html');
        const newContent = doc.getElementById('content');

        if (newContent) {
            contentElement.innerHTML = newContent.innerHTML;
            if (shouldCache(url)) pageCache[url] = newContent.innerHTML;
        } else {
            console.warn('No se encontró el elemento de contenido en la página cargada.');
            contentElement.innerHTML = data;
        }

        loadingBar.style.width = '100%';
        loadingBar.style.transition = 'width 0.1s ease, opacity 0.3s ease';
        setTimeout(() => {
            loadingBar.style.width = '0%';
            loadingBar.style.opacity = '0';
        }, 100);

        if (isPushState) history.pushState(null, '', url);

        // Ejecutar scripts del contenido cargado
        const scripts = doc.querySelectorAll('script');
        for (const script of scripts) {
            const newScript = document.createElement('script');
            if (script.src) {
                newScript.src = script.src;
            } else {
                newScript.textContent = script.textContent;
            }
            document.body.appendChild(newScript);
        }

        reinicializar();
    } catch (error) {
        console.error('Error al cargar la página:', error);
        // Mostrar un mensaje de error al usuario aquí
    }
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

    function handleContentLoad(event, link, element) {
        if (element.closest('.no-ajax')) return true;

        const enlace = link.trim().toLowerCase();
        if (!link ||
            enlace.endsWith('.pdf') ||
            enlace.startsWith('https://2upra.com/nocache') ||
            enlace.startsWith('javascript:') ||
            enlace.startsWith('data:') ||
            enlace.startsWith('vbscript:') ||
            enlace.includes('#')
        ) {
            return true;
        }

        event.preventDefault();
        loadContent(link, true);
        return false;
    }

    document.body.addEventListener('click', function (event) {
        const target = event.target.closest('a, button a, .botones-panel');
        if (!target) return;

        const link = target.getAttribute('href') || target.getAttribute('data-href') || target.querySelector('a')?.getAttribute('href');
        if (link) {
            handleContentLoad(event, link, target);
        }
    });

    window.addEventListener('popstate', () => loadContent(location.href, false));
});