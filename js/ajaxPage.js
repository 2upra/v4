(function ($) {
    const pageCache = {};
    function inicializarScripts() {
        ['inicializarWaveforms', 'inicializarReproductorAudio', 'minimizarform', 'selectorformtipo', 'ajax_submit', 'borrarcomentario', 'colab', 'configuser', 'deletepost', 'diferidopost', 'editarcomentario', 'like', 'notificacioncolab', 'busqueda', 'updateBackgroundColor', 'presentacionmusic', 'seguir', 'registro', 'comentarios', 'botoneditarpost', 'fan', 'perfilpanel', 'smooth', 'navpanel', 'borderborder', 'initializeFormFunctions', 'initializeModalregistro', 'submenu', 'selectortipousuario', 'empezarcolab', 'subidaRolaForm', 'avances', 'updateDates', 'initializeProgressSegments', 'initializeCustomTooltips', 'fondoAcciones', 'pestanasgroup', 'manejoDeLogs', 'progresosinteractive', 'setupScrolling', 'inicializarDescargas', 'handleAllRequests', 'textflux', 'autoFillUserInfo', 'inicializarPestanas', 'meta', 'reporteScript', 'IniciadorSample', 'inicialRsForm', 'reiniciarDiferidoPost', 'generarGrafico', 'grafico', 'IniciadoresConfigPerfil', 'proyectoForm', 'inicializarAlerta'].forEach(
            func => {
                if (typeof window[func] === 'function') {
                    try {
                        window[func]();
                    } catch (error) {
                        console.error(`Error al ejecutar ${func}:`, error);
                    }
                }
            }
        );
        if (typeof window.manageSeparatorsAndOrder === 'function') {
            window.manageSeparatorsAndOrder('.spaceprogreso', '#toggleOrderButton');
        }
        if (typeof window.updateDaysElapsed === 'function') {
            window.updateDaysElapsed('2024-01-01');
        }
    }

    function reinicializar() {
        console.log('Iniciando reinicialización');
        inicializarScripts();
        if (window.location.hash && typeof window.mostrarPestana === 'function') {
            console.log('Mostrando pestaña:', window.location.hash);
            window.mostrarPestana(window.location.hash);
        }
        console.log('Reinicialización completada');
    }

    window.reinicializar = reinicializar;

    function loadStripe(callback) {
        console.log('Cargando Stripe');
        if (typeof Stripe !== 'undefined') {
            console.log('Stripe ya está cargado');
            callback();
        } else {
            console.log('Cargando script de Stripe');
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.async = true;
            script.onload = () => {
                console.log('Script de Stripe cargado');
                callback();
            };
            document.head.appendChild(script);
        }
    }

    function initializeStripeFunctions() {
        console.log('Inicializando funciones de Stripe');
        ['stripepro', 'stripecompra'].forEach(func => {
            if (typeof window[func] === 'function') {
                console.log(`Ejecutando función ${func}`);
                window[func]();
            } else {
                console.warn(`${func} no está definida`);
            }
        });
    }

    function shouldCache(url) {
        console.log('Verificando si se debe cachear:', url);
        const noCacheUrls = ['https://2upra.com/nocache'];
        const shouldCache = !noCacheUrls.some(noCacheUrl => new RegExp(noCacheUrl.replace('*', '.*')).test(url));
        console.log('Resultado de shouldCache:', shouldCache);
        return shouldCache;
    }

    function loadContent(enlace, isPushState) {
        console.log('Iniciando carga de contenido:', enlace);
        if (!enlace || enlace.startsWith('javascript:') || enlace.includes('#')) {
            console.log('Enlace no válido para carga AJAX');
            return;
        }
        if (enlace.includes('descarga_token')) {
            console.log('Descarga en proceso, no se carga el contenido por AJAX');
            return;
        }
        if (pageCache[enlace] && shouldCache(enlace)) {
            console.log('Cargando desde caché:', enlace);
            $('#content').html(pageCache[enlace]);
            if (isPushState) {
                console.log('Actualizando historial');
                history.pushState(null, '', enlace);
            }
            reinicializar();
        } else {
            console.log('Cargando contenido vía AJAX:', enlace);
            $('#loadingBar').stop(true, true).css({width: '0%', opacity: 1}).animate({width: '70%'}, 400);
            $.ajax({
                url: enlace,
                dataType: 'html',
                success: function (data) {
                    console.log('Contenido cargado exitosamente');
                    const $data = $(data);
                    const content = $data.find('#content').html();
                    console.log('Contenido encontrado:', content ? 'Sí' : 'No');
                    $('#content').html(content);
                    if (shouldCache(enlace)) {
                        console.log('Almacenando en caché:', enlace);
                        pageCache[enlace] = content;
                    }
                    $('#loadingBar').animate({width: '100%'}, 100, () => {
                        $('#loadingBar').animate({opacity: 0}, 300, function () {
                            $(this).css({width: '0%'});
                        });
                    });
                    if (isPushState) {
                        console.log('Actualizando historial');
                        history.pushState(null, '', enlace);
                    }
                    console.log('Evaluando scripts');
                    $data.filter('script').each(function () {
                        $.globalEval(this.text || this.textContent || this.innerHTML || '');
                    });
                    console.log('Programando reinicialización');
                    setTimeout(reinicializar, 100);
                },
                error: function (xhr, status, error) {
                    console.error('Error al cargar la página:', status, error);
                    console.log('Respuesta del servidor:', xhr.responseText);
                }
            });
        }
    }

    $(document).ready(function () {
        console.log('Documento listo');
        if (!window.location.href.includes('?fb-edit=1')) {
            if (!window.galleInicializado && typeof window.galle === 'function') {
                console.log('Inicializando galle');
                window.galle();
                window.galleInicializado = true;
            }
            reinicializar();
            loadStripe(initializeStripeFunctions);
        }

        function handleContentLoad(event, enlace, element) {
            console.log('Manejando carga de contenido:', enlace);
            if ($(element).hasClass('no-ajax') || $(element).parents('.no-ajax').length > 0) {
                console.log('Elemento marcado como no-ajax');
                return true;
            }
            const lowerCaseLink = enlace.trim().toLowerCase();
            if (!enlace || lowerCaseLink.endsWith('.pdf') || enlace === 'https://2upra.com/nocache' || lowerCaseLink.startsWith('javascript:') || lowerCaseLink.startsWith('data:') || lowerCaseLink.startsWith('vbscript:') || enlace.includes('#')) {
                console.log('Enlace no apto para carga AJAX');
                return true;
            }
            event.preventDefault();
            loadContent(enlace, true);
        }

        $(document).on('click', 'a, button a', function (event) {
            const enlace = $(this).attr('href') || $(this).find('a').attr('href');
            console.log('Clic en enlace:', enlace);
            return handleContentLoad(event, enlace, this);
        });

        $(document).on('click', '.botones-panel', function (event) {
            const enlace = $(this).data('href');
            console.log('Clic en botón de panel:', enlace);
            event.preventDefault();
            loadContent(enlace, true);
        });

        $(window).on('popstate', function () {
            console.log('Evento popstate detectado');
            loadContent(location.href, false);
        });
    });
})(jQuery);
