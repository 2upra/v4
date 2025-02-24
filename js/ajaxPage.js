const ajaxUrl = typeof ajax_params !== 'undefined' && ajax_params.ajax_url ? ajax_params.ajax_url : '/wp-admin/admin-ajax.php';

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
        'iniciarMasonry',
        'actualizarUIBusquedaNoURL',
        'stopAllWaveSurferPlayers',
        'inicializarPestanas',
        'scrollToSection',
        'inicializarWaveforms',
        'inicializarReproductorAudio',
        'minimizarform',
        'ajax_submit',
        'borrarcomentario',
        'colab',
        'tooltips',
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
        'iniciarcm',
        'inicializarBuscadores',
        'stripecomprabeat',
        'initTareas',
        'iniciarPestanasPf',
        'redir',
        'inicIcAy'
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
            requestAnimationFrame(() => {
                document.getElementById('main').scrollTop = 0;
            });
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
                requestAnimationFrame(() => {
                    document.getElementById('content').scrollTop = 0;
                });
                doc.querySelectorAll('script').forEach(s => {
                    if (s.src && !document.querySelector(`script[src="${s.src}"]`)) {
                        document.body.appendChild(Object.assign(document.createElement('script'), {src: s.src, async: false}));
                    } else if (!s.src) {
                        document.body.appendChild(Object.assign(document.createElement('script'), {textContent: s.textContent}));
                    }
                });
                setTimeout(reinit, 200);
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

            // Cerrar submenús
            // Usar setTimeout para retrasar la ejecución de load
            setTimeout(() => {
                window.hideAllSubmenus();
                load(url, true);
            }, 0);

            requestAnimationFrame(() => {
                document.getElementById('content').scrollTop = 0;
            });

            e.stopImmediatePropagation(); // Asegurarse de que no se propague a otros listeners después del setTimeout
        }

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

                if (!url) {
                    url = el.getAttribute('data-href');
                }

                if (url) {
                    handleLoad(e, url, el);
                }
            }
        });

        document.body.addEventListener('click', function (e) {
            const notificacionItem = e.target.closest('.notificacion-item');
            if (notificacionItem) {
                const enlace = notificacionItem.querySelector('.notificacion-enlace');
                if (enlace && enlace.href) {
                    e.preventDefault();
                    const url = enlace.href;
                    handleLoad(e, url, enlace);
                }
            }
        });

        window.addEventListener('popstate', () => load(location.href, false));
    });
})();

function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);

    if (element) {
        element.scrollIntoView({behavior: 'smooth'}); // Puedes usar 'auto' para scroll instantáneo
    }
}

function redir() {
    const elems = document.querySelectorAll('.RLSDSAE');

    elems.forEach(el => {
        el.addEventListener('click', () => {
            const url = el.dataset.url;
            if (url) {
                window.open(url, '_blank'); // Usa window.open para nueva pestaña
            }
        });
    });
}

//

function inicIcAy() {
    const it = document.querySelector('.menu-item.iconoInver');
    if (!it) return;

    const svg = it.querySelector('svg');
    const txtAy = it.querySelector('.textoAyuda');
    const clrRj = 'fill:#d43333;';
    const tmpIcAy = 'tmpIcAy';
    const tmpMax = 24 * 60 * 60 * 1000;
    const tmpInc = [8, 12, 24];

    // Si no hay un timestamp guardado, se asigna la hora actual
    if (!localStorage.getItem(tmpIcAy)) {
        localStorage.setItem(tmpIcAy, Date.now());
    }

    function tmpSig() {
        let tmpUlt = parseInt(localStorage.getItem(tmpIcAy));
        let difTmp = Date.now() - tmpUlt;
        let indTmp = 0;
        let tmpSigTime = tmpInc[indTmp] * 60 * 60 * 1000;

        while (difTmp > tmpSigTime && indTmp < tmpInc.length - 1) {
            indTmp++;
            tmpSigTime = tmpInc[indTmp] * 60 * 60 * 1000;
        }
        if (difTmp > tmpMax) {
            return 0;
        }
        return tmpSigTime - difTmp;
    }

    function ponRj() {
        svg.style.cssText = clrRj;
        txtAy.textContent = '2upra necesita tu ayuda';
        txtAy.style.display = 'block';
    }

    function qtRj() {
        svg.style.cssText = '';
        txtAy.style.display = 'none';
        localStorage.setItem(tmpIcAy, Date.now());
        let tiempoRestante = tmpSig();
        if (tiempoRestante > 0) {
            setTimeout(ponRj, tiempoRestante);
        } else {
            ponRj();
        }
    }

    let tmpRest = tmpSig();

    if (tmpRest === 0) {
        ponRj();
    } else {
        setTimeout(ponRj, tmpRest);
    }

    // Al hacer click en el elemento, se oculta el mensaje y se reinicia el contador
    it.addEventListener('click', qtRj);

    // Además, al pasar el mouse sobre el texto, se oculta
    txtAy.addEventListener('mouseenter', () => {
        txtAy.style.display = 'none';
    });
}
