(function ($) {
    const pageCache = {};

    function inicializarScripts() {
        const funciones = ['inicializarWaveforms', 'inicializarReproductorAudio', 'minimizarform', 'selectorformtipo', 'ajax_submit', 'borrarcomentario', 'colab', 'configuser', 'deletepost', 'diferidopost', 'editarcomentario', 'like', 'notificacioncolab', 'busqueda', 'updateBackgroundColor', 'presentacionmusic', 'seguir', 'registro', 'comentarios', 'botoneditarpost', 'fan', 'perfilpanel', 'smooth', 'navpanel', 'borderborder', 'initializeFormFunctions', 'initializeModalregistro', 'submenu', 'selectortipousuario', 'subidaRolaForm', 'avances', 'updateDates', 'initializeProgressSegments', 'initializeCustomTooltips', 'fondoAcciones', 'pestanasgroup', 'manejoDeLogs', 'progresosinteractive', 'setupScrolling', 'inicializarDescargas', 'handleAllRequests', 'textflux', 'autoFillUserInfo', 'inicializarPestanas', 'reporteScript', 'IniciadorSample', 'inicialRsForm', 'reiniciarDiferidoPost', 'grafico', 'IniciadoresConfigPerfil', 'proyectoForm'];

        funciones.forEach(func => {
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
        const stripeFunctions = ['stripe_suscripcion', 'stripeventa', 'stripepro', 'stripecompra'];

        stripeFunctions.forEach(func => {
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
        if (!enlace || enlace.startsWith('javascript:') || enlace.includes('#')) return;

        if (enlace.includes('descarga_token')) {
            console.log('Descarga en proceso, no se carga el contenido por AJAX');
            return;
        }

        if (pageCache[enlace] && shouldCache(enlace)) {
            $('#content').html(pageCache[enlace]);
            if (isPushState) history.pushState(null, '', enlace);
            reinicializar();
        } else {
            $('#loadingBar').stop(true, true).css({width: '0%', opacity: 1}).animate({width: '70%'}, 400);
            $.ajax({
                url: enlace,
                dataType: 'html',
                success: function (data) {
                    const $data = $(data);
                    const content = $data.find('#content').html();
                    $('#content').html(content);

                    if (shouldCache(enlace)) {
                        pageCache[enlace] = content;
                    }

                    $('#loadingBar').animate({width: '100%'}, 100, () => {
                        $('#loadingBar').animate({opacity: 0}, 300, function () {
                            $(this).css({width: '0%'}); // Reinicia el ancho después de la desaparición
                        });
                    });

                    if (isPushState) history.pushState(null, '', enlace);

                    $data.filter('script').each(function () {
                        $.globalEval(this.text || this.textContent || this.innerHTML || '');
                    });

                    setTimeout(reinicializar, 100);
                },
                error: function () {
                    console.error('Error al cargar la página');
                }
            });
        }
    }

    $(document).ready(function () {
        // Verificación e inicialización de galle si es necesario
        if (!window.location.href.includes('?fb-edit=1')) {
            if (!window.galleInicializado && typeof window.galle === 'function') {
                window.galle();
                window.galleInicializado = true;
            }

            reinicializar();
            loadStripe(initializeStripeFunctions);
        }

        // Función para manejar la carga de contenido
        function handleContentLoad(event, enlace, element) {
            // Verificar si el elemento o alguno de sus padres tiene la clase 'no-ajax'
            if ($(element).hasClass('no-ajax') || $(element).parents('.no-ajax').length > 0) {
                return true; // Permite el comportamiento predeterminado, sin AJAX
            }
        
            // Convertir el enlace a minúsculas y eliminar espacios en blanco
            const lowerCaseLink = enlace.trim().toLowerCase();
        
            // Verificar esquemas de URL potencialmente peligrosos
            if (!enlace || lowerCaseLink.endsWith('.pdf') || enlace === 'https://2upra.com/nocache' ||
                lowerCaseLink.startsWith('javascript:') || lowerCaseLink.startsWith('data:') || 
                lowerCaseLink.startsWith('vbscript:') || enlace.includes('#')) {
                return true;
            }
            
            event.preventDefault();
            loadContent(enlace, true);
        }

        // Manejador de clics para enlaces y botones que contienen enlaces
        $(document).on('click', 'a, button a', function (event) {
            const enlace = $(this).attr('href') || $(this).find('a').attr('href');
            return handleContentLoad(event, enlace, this);
        });

        // Manejador de clics para botones con la clase .botones-panel
        $(document).on('click', '.botones-panel', function (event) {
            event.preventDefault();
            loadContent($(this).data('href'), true);
        });

        // Manejo del evento popstate para navegación con historial
        $(window).on('popstate', function () {
            loadContent(location.href, false);
        });
    });
})(jQuery);
