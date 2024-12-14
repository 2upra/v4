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

        <button class="iralpost">
            <a href="https://2upra.com/sample/joe-bataan-vocal-loop-ideal-for-hip-hop/">Ir al post</a>
        </button>;

        // Usando delegación de eventos en el body
        document.body.addEventListener('click', e => {
            // Seleccionamos el elemento 'a' más cercano o los elementos específicos que nos interesan
            const el = e.target.closest('a, button.iralpost > a, button a, .botones-panel a, .post-image-container a');

            if (el) {
                // Obtenemos la URL del href si es un 'a', o del 'data-href' si está definido
                let url;
                if (el.tagName === 'A') {
                    url = el.getAttribute('href');
                } else if (el.closest('button.iralpost') && el.closest('button.iralpost').querySelector('a')) {
                    url = el.closest('button.iralpost').querySelector('a').getAttribute('href');
                } else if (el.hasAttribute('data-href')) {
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
