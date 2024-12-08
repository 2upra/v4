/*
VM2585:1  Uncaught SyntaxError: Failed to execute 'appendChild' on 'Node': Unexpected token ':'
    at ajaxPage.js?ver=3.0.54:141:39
    at NodeList.forEach (<anonymous>)
    at ajaxPage.js?ver=3.0.54:137:48
(anónimo) @ ajaxPage.js?ver=3.0.54:141
(anónimo) @ ajaxPage.js?ver=3.0.54:137
Promise.then
load @ ajaxPage.js?ver=3.0.54:129
handleLoad @ ajaxPage.js?ver=3.0.54:165
(anónimo) @ ajaxPage.js?ver=3.0.54:169
VM2592:1  Uncaught SyntaxError: Failed to execute 'appendChild' on 'Node': Identifier 'wpAdminUrl' has already been declared
    at ajaxPage.js?ver=3.0.54:141:39
    at NodeList.forEach (<anonymous>)
    at ajaxPage.js?ver=3.0.54:137:48
*/

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

    function load(url, pushState) {
        if (!url || /^(javascript|data|vbscript):|#/.test(url.toLowerCase()) || url.includes('descarga_token')) return;

        // Si la URL está en la caché, cargarla desde allí
        if (pageCache[url]) {
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
                // Guardar en caché para futuras solicitudes
                pageCache[url] = content;
                document.getElementById('loadingBar').style.cssText = 'width: 100%; transition: width 0.1s ease, opacity 0.3s ease';
                setTimeout(() => (document.getElementById('loadingBar').style.cssText = 'width: 0%; opacity: 0'), 100);
                if (pushState) history.pushState(null, '', url);

                // Manejar scripts externos
                const externalScripts = [];
                doc.querySelectorAll('script').forEach(s => {
                    if (s.src && !document.querySelector(`script[src="${s.src}"]`)) {
                        externalScripts.push(s.src);
                    }
                });

                // Cargar scripts externos primero
                loadExternalScripts(externalScripts, () => {
                    // Ejecutar scripts inline después de cargar los externos
                    doc.querySelectorAll('script').forEach(s => {
                        if (!s.src) {
                            try {
                                // Evaluar el código de forma segura
                                new Function(s.textContent)();
                            } catch (error) {
                                console.error('Error evaluating inline script:', error);
                            }
                        }
                    });
                    setTimeout(reinit, 100);
                });
            })
            .catch(e => console.error('Load error:', e));
    }

    // Función para cargar scripts externos de forma secuencial
    function loadExternalScripts(scripts, callback) {
        if (scripts.length === 0) {
            callback();
            return;
        }

        const src = scripts.shift();
        const script = document.createElement('script');
        script.src = src;
        script.async = false; // Asegura el orden de ejecución
        script.onload = () => loadExternalScripts(scripts, callback);
        script.onerror = () => {
            console.error('Error loading external script:', src);
            loadExternalScripts(scripts, callback); // Continuar con los demás aunque haya error
        };
        document.body.appendChild(script);
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

        // Función para manejar los clics en los enlaces
        function handleLinkClick(event) {
            const link = event.target.closest('a');

            // Si no hay enlace, no hacer nada
            if (!link) return;

            const url = link.getAttribute('href');

            // Si la URL es inválida, o es un PDF, o comienza con 'javascript', 'data', 'vbscript', o '#', o si tiene la clase 'no-ajax', o si está dentro de un elemento 'no-ajax' no hacer nada
            if (typeof url !== 'string' || !url || /\.pdf$|^(javascript|data|vbscript):|#/.test(url.toLowerCase()) || link.classList.contains('no-ajax') || link.closest('.no-ajax')) return;

            // Prevenir el comportamiento por defecto y cargar la URL con AJAX
            event.preventDefault();
            load(url, true);
        }

        // Añadir la función de manejo de clics a todos los enlaces
        document.addEventListener('click', handleLinkClick);

        window.addEventListener('popstate', () => load(location.href, false));
    });
})();
