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
        if (typeof inicializarScripts === 'function') {
            inicializarScripts();
        }
        console.log('Reinicialización completada');
    }
    
    function loadContent(enlace, isPushState) {
        console.log('Iniciando carga de contenido:', enlace);
        
        if (pageCache[enlace]) {
            console.log('Cargando desde caché:', enlace);
            $('#content').html(pageCache[enlace]);
            if (isPushState) {
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
                    
                    pageCache[enlace] = content;
                    
                    $('#loadingBar').animate({width: '100%'}, 100, () => {
                        $('#loadingBar').animate({opacity: 0}, 300, function () {
                            $(this).css({width: '0%'});
                        });
                    });
                    
                    if (isPushState) {
                        history.pushState(null, '', enlace);
                    }
                    
                    $data.filter('script').each(function () {
                        $.globalEval(this.text || this.textContent || this.innerHTML || '');
                    });
                    
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
    
        // Prueba: cargar automáticamente una página al inicio
        let testUrl = '/sello'; // Cambia esto a una URL válida de tu sitio
        console.log('Cargando página de prueba:', testUrl);
        loadContent(testUrl, true);
    
        $(document).on('click', 'a, button a, .botones-panel', function (event) {
            event.preventDefault();
            let enlace = $(this).attr('href') || $(this).data('href') || $(this).find('a').attr('href');
            console.log('Clic detectado, cargando:', enlace);
            loadContent(enlace, true);
        });
    
        $(window).on('popstate', function () {
            console.log('Evento popstate detectado');
            loadContent(location.href, false);
        });
    });
})(jQuery);
