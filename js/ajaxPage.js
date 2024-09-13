const pageCache = {};

function inicializarScripts() {
    const scripts = [
        'inicializarWaveforms', 
        'inicializarReproductorAudio', 
        'minimizarform'
        // Aquí va la lista completa de scripts a reinicializar
    ];

    scripts.forEach(func => {
        if (typeof window[func] === 'function') {
            try {
                window[func]();
            } catch (error) {
                console.error(`Error al ejecutar ${func}:`, error);
            }
        }
    });

    if (typeof window.manageSeparatorsAndOrder === 'function') {
        window.manageSeparatorsAndOrder('.spaceprogreso', '#toggleOrderButton');
    }

    if (typeof window.updateDaysElapsed === 'function') {
        window.updateDaysElapsed('2024-01-01');
    }
}

function reinicializar() {
    inicializarScripts();
    if (window.location.hash && typeof window.mostrarPestana === 'function') {
        window.mostrarPestana(window.location.hash);
    }
}

window.reinicializar = reinicializar;

function loadStripe(callback) {
    if (typeof Stripe !== 'undefined') {
        callback();
    } else {
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.async = true;
        script.onload = callback;
        document.head.appendChild(script);
    }
}

function initializeStripeFunctions() {
    const functions = ['stripepro', 'stripecompra'];
    functions.forEach(func => {
        if (typeof window[func] === 'function') {
            window[func]();
        } else {
            console.warn(`${func} no está definida`);
        }
    });
}

function shouldCache(url) {
    const noCacheUrls = ['https://2upra.com/nocache'];
    return !noCacheUrls.some(noCacheUrl => new RegExp(noCacheUrl.replace('*', '.*')).test(url));
}

function loadContent(enlace, isPushState) {
    console.log('Iniciando carga de contenido:', enlace);

    if (!enlace || enlace.startsWith('javascript:') || enlace.includes('#')) return;
    if (enlace.includes('descarga_token')) {
        console.log('Descarga en proceso, no se carga el contenido por AJAX');
        return;
    }

    if (pageCache[enlace] && shouldCache(enlace)) {
        console.log('Cargando desde caché:', enlace);
        document.getElementById('content').innerHTML = pageCache[enlace];
        if (isPushState) history.pushState(null, '', enlace);
        reinicializar();
    } else {
        console.log('Cargando contenido vía AJAX:', enlace);

        const loadingBar = document.getElementById('loadingBar');
        loadingBar.style.width = '0%';
        loadingBar.style.opacity = '1';
        loadingBar.style.transition = 'width 0.4s ease';
        loadingBar.style.width = '70%';

        fetch(enlace)
            .then(response => response.text())
            .then(data => {
                console.log('Contenido cargado exitosamente');

                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const content = doc.getElementById('content').innerHTML;
                document.getElementById('content').innerHTML = content;

                if (shouldCache(enlace)) {
                    console.log('Almacenando en caché:', enlace);
                    pageCache[enlace] = content;
                }

                loadingBar.style.transition = 'width 0.1s ease, opacity 0.3s ease';
                loadingBar.style.width = '100%';
                setTimeout(() => {
                    loadingBar.style.opacity = '0';
                    loadingBar.style.width = '0%';
                }, 100);

                if (isPushState) history.pushState(null, '', enlace);

                const scripts = doc.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    newScript.textContent = script.textContent;
                    document.body.appendChild(newScript);
                });

                setTimeout(reinicializar, 100);
            })
            .catch(error => {
                console.error('Error al cargar la página:', error);
            });
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

    function handleContentLoad(event, enlace, element) {
        if (element.classList.contains('no-ajax') || element.closest('.no-ajax')) {
            return true;
        }

        const lowerCaseLink = enlace.trim().toLowerCase();

        if (!enlace || lowerCaseLink.endsWith('.pdf') || enlace === 'https://2upra.com/nocache' || lowerCaseLink.startsWith('javascript:') || lowerCaseLink.startsWith('data:') || lowerCaseLink.startsWith('vbscript:') || enlace.includes('#')) {
            return true;
        }

        event.preventDefault();
        loadContent(enlace, true);
    }

    document.querySelectorAll('a, button a').forEach(element => {
        element.addEventListener('click', function (event) {
            const enlace = this.getAttribute('href') || this.querySelector('a')?.getAttribute('href');
            return handleContentLoad(event, enlace, this);
        });
    });

    document.querySelectorAll('.botones-panel').forEach(element => {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            loadContent(this.getAttribute('data-href'), true);
        });
    });

    window.addEventListener('popstate', function () {
        loadContent(location.href, false);
    });
});