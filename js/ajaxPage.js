(function () {
    const pageCache = {},
        isFirefox = typeof InstallTrigger !== 'undefined';

    if (isFirefox) {
        document.body.classList.add('firefox');
    }

    const userAgent = navigator.userAgent;

    // Verificar si contiene "AppAndroid"
    if (userAgent.includes('AppAndroid')) {
        document.body.classList.add('appAndroid');
    }

    const funcs = [
        'actualizarUIBusquedaNoURL',
        'stopAllWaveSurferPlayers',
        'inicializarPestanas',
        'inicializarWaveforms',
        'inicializarReproductorAudio',
        'minimizarform',
        'ajax_submit',
        'borrarcomentario',
        'colab',
        'configuser',
        'deletepost',
        'diferidopost',
        'editarcomentario',
        'like',
        'notificacioncolab',
        'busqueda',
        'updateBackgroundColor',
        'presentacionmusic',
        'seguir',
        'registro',
        'comentarios',
        'botoneditarpost',
        'fan',
        'perfilpanel',
        'smooth',
        'navpanel',
        'borderborder',
        'initializeFormFunctions',
        'initializeModalregistro',
        'submenu',
        'selectortipousuario',
        'empezarcolab',
        'subidaRolaForm',
        'avances',
        'updateDates',
        'initializeProgressSegments',
        'initializeCustomTooltips',
        'fondoAcciones',
        'pestanasgroup',
        'manejoDeLogs',
        'progresosinteractive',
        'setupScrolling',
        'inicializarDescargas',
        'handleAllRequests',
        'textflux',
        'autoFillUserInfo',
        'meta',
        'reporteScript',
        'inicializarGraficos',
        'grafico',
        'IniciadoresConfigPerfil',
        'proyectoForm',
        'inicializarAlerta',
        'autoRows',
        'iniciarRS',
        'initializeUI',
        'tagsPosts',
        'vistaPost',
        'initEditWordPress',
        'reiniciarCargaDiferida',
        'registrarVistas',
        'colec',
        'cambiarFiltroTiempo',
        'filtrosPost',
        'contadorDeSamples',
        'establecerFiltros',
        'actualizarBotonFiltro',
        'iniciarCargaNotificaciones',
        'busquedaMenuMovil',
        'iniciarcm'
    ];

    function initScripts() {
        funcs.forEach(f => (typeof window[f] === 'function' ? window[f]() : console.warn(`Función ${f} no definida.`)));
    }

    function reinit() {
        initScripts();
        window.location.hash && window.mostrarPestana && window.mostrarPestana(window.location.hash);
    }
    window.reinicializar = reinit;

    const login = window.ajaxPage && window.ajaxPage.logeado;

    function loadStripe(cb) {
        if (!login) return console.log('Stripe skipped, not logged in');
        if (typeof Stripe !== 'undefined') return cb();
        const s = document.createElement('script');
        s.src = 'https://js.stripe.com/v3/';
        s.async = true;
        s.onload = cb;
        document.head.appendChild(s);
    }

    function initStripeFuncs() {
        ['stripepro', 'stripecompra'].forEach(f => (window[f] ? window[f]() : console.warn(f + ' undefined')));
    }

    function shouldCache(url) {
        return !/https:\/\/2upra\.com\/nocache/.test(url);
    }

    function load(url, pushState) {
        if (!url || /^(javascript|data|vbscript):|#/.test(url.toLowerCase()) || url.includes('descarga_token')) return;
        if (pageCache[url] && shouldCache(url)) {
            document.getElementById('content').innerHTML = pageCache[url];
            if (pushState) history.pushState(null, '', url);
            return reinit();
        }
        document.getElementById('loadingBar').style.cssText = 'width: 70%; opacity: 1; transition: width 0.4s ease';
        fetch(url)
            .then(r => r.text())
            .then(data => {
                const doc = new DOMParser().parseFromString(data, 'text/html');
                const content = doc.getElementById('content').innerHTML;
                document.getElementById('content').innerHTML = content;
                if (shouldCache(url)) pageCache[url] = content;
                document.getElementById('loadingBar').style.cssText = 'width: 100%; transition: width 0.1s ease, opacity 0.3s ease';
                setTimeout(() => (document.getElementById('loadingBar').style.cssText = 'width: 0%; opacity: 0'), 100);
                if (pushState) history.pushState(null, '', url);
                doc.querySelectorAll('script').forEach(s => {
                    if (s.src && !document.querySelector(`script[src="${s.src}"]`)) {
                        document.body.appendChild(Object.assign(document.createElement('script'), {src: s.src, async: false}));
                    } else if (!s.src) {
                        document.body.appendChild(Object.assign(document.createElement('script'), {textContent: s.textContent}));
                    }
                });
                setTimeout(reinit, 100);
            })
            .catch(e => console.error('Load error:', e));
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (!window.location.href.includes('?fb-edit=1')) {
            reinit();
            if (login && !window.galleInicializado && typeof window.galle === 'function') {
                window.galle();
                window.galleInicializado = true;
            }
            if (login && typeof loadStripe === 'function') loadStripe(initStripeFuncs);
        }

        function handleLoad(e, url, el) {
            if (el.classList.contains('no-ajax') || el.closest('.no-ajax')) return true;
            if (typeof url !== 'string' || !url) return console.warn('Invalid URL:', url), true;
            const lowerUrl = url.trim().toLowerCase();
            if (/\.pdf$|^(https:\/\/2upra\.com\/nocache|javascript|data|vbscript):|#/.test(lowerUrl)) return true;
            e.preventDefault();
            load(url, true);
        }

        //sin motivo alguno sigue sin funcionar para <button class="iralpost"><a ajaxurl="https://2upra.com/sample/memphis-rap-vocal-sample-19/">Ir al post</a></button>

        document.body.addEventListener('click', e => {
            // Selección optimizada de elementos 'a' y botones con 'data-href'
            const el = e.target.closest('a, button[data-href], .botones-panel [data-href], .post-image-container [data-href]');

            if (el) {
                let url;

                // Prioridad 1: ajaxUrl dentro de button.iralpost
                const buttonIralpost = el.closest('button.iralpost');
                if (buttonIralpost) {
                    const innerLink = buttonIralpost.querySelector('a');
                    url = innerLink && innerLink.hasAttribute('ajaxUrl') ? innerLink.getAttribute('ajaxUrl') : buttonIralpost.getAttribute('ajaxUrl');
                }

                // Prioridad 2: href en elementos 'a'
                if (!url && el.tagName === 'A') {
                    url = el.getAttribute('href');
                }

                // Prioridad 3: data-href en cualquier elemento (incluyendo botones)
                if (!url) {
                    url = el.getAttribute('data-href');
                }

                // Si encontramos una URL, manejamos la carga
                if (url) {
                    handleLoad(e, url, el);
                }
            }
        });

        window.addEventListener('popstate', () => load(location.href, false));
    });
})();
