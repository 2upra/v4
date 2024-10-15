(function () {
    'use strict';

    const DEPURAR = true;
    const log = DEPURAR ? console.log.bind(console) : () => {};

    let estaCargando = false;
    let hayMasContenido = true;
    let paginaActual = 2;
    const publicacionesCargadas = new Set();
    let identificador = '';
    let eventoBusquedaConfigurado = false;
    let scrollTimeout = null;

    // Función que se llama cada vez que se cambia de página mediante AJAX
    function reiniciarCargaDiferida() {
        log('Reiniciando carga diferida');
        window.removeEventListener('scroll', manejarScroll);
        estaCargando = false;
        hayMasContenido = true;
        paginaActual = 2;
        publicacionesCargadas.clear();
        identificador = '';
        window.idUsuarioActual = null;
    
        if (!eventoBusquedaConfigurado) {
            configurarEventoBusqueda();
            eventoBusquedaConfigurado = true;
        }

        configurarEventosPostTag();
        ajustarAlturaMaxima();
        habilitarCargaPorScroll();
        establecerIdUsuarioDesdeInput();
    
        // **Configurar inicialmente los eventos de <span class="postTag">**
        reiniciarEventosPostTag();
    }

    function establecerIdUsuarioDesdeInput() {
        const inputPaginaActual = document.getElementById('pagina_actual');
        if (inputPaginaActual?.value.toLowerCase() === 'sello') {
            const inputIdUsuario = document.getElementById('user_id');
            if (inputIdUsuario) {
                const idUsuario = inputIdUsuario.value;
                const contenedorPerfil = document.querySelector('.custom-uprofile-container');
                contenedorPerfil?.setAttribute('data-author-id', idUsuario);
                window.idUsuarioActual = idUsuario;
                log('ID de usuario establecido:', idUsuario);
            } else {
                log('No se encontró el input de user_id');
            }
        } else {
            log('La página actual no es "sello"');
        }
    }

    function manejarScroll() {
        if (scrollTimeout) return;
        scrollTimeout = setTimeout(() => {
            scrollTimeout = null;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const alturaVentana = window.innerHeight;
            const alturaDocumento = Math.max(document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight);

            log('Evento de scroll detectado:', {scrollTop, alturaVentana, alturaDocumento, estaCargando});

            if (scrollTop + alturaVentana > alturaDocumento - 100 && !estaCargando && hayMasContenido) {
                log('Condiciones para cargar más contenido cumplidas');
                cargarMasContenido();
            } else {
                log('Condiciones para cargar más contenido no cumplidas');
            }
        }, 200); // Ajusta el tiempo de espera según sea necesario
    }

    async function cargarMasContenido() {
        estaCargando = true;
        log('Iniciando carga de más contenido');

        const elementoPestañaActiva = document.querySelector('.tab.active');
        if (elementoPestañaActiva?.getAttribute('ajax') === 'no') {
            log('La pestaña activa tiene ajax="no". No se cargará más contenido.');
            estaCargando = false;
            return;
        }

        const listaPublicaciones = document.querySelector('.tab.active .social-post-list');
        if (!listaPublicaciones) {
            log('No se encontró una pestaña activa');
            estaCargando = false;
            return;
        }

        const {filtro = '', tabId = '', posttype = ''} = listaPublicaciones.dataset;
        const idUsuario = window.idUsuarioActual || document.querySelector('.custom-uprofile-container')?.dataset.authorId || '';

        log('Parámetros de carga:', {filtro, tabId, identificador, idUsuario, paginaActual});

        try {
            const respuesta = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'cargar_mas_publicaciones',
                    paged: paginaActual,
                    filtro,
                    posttype,
                    identifier: identificador,
                    tab_id: tabId,
                    user_id: idUsuario,
                    cargadas: Array.from(publicacionesCargadas).join(',')
                })
            });

            const textoRespuesta = await respuesta.text();
            await procesarRespuesta(textoRespuesta);
        } catch (error) {
            log('Error en la petición AJAX:', error);
        } finally {
            estaCargando = false;
        }
    }

    async function procesarRespuesta(respuesta) {
        log('Respuesta recibida:', respuesta.substring(0, 100) + '...');
        const respuestaLimpia = respuesta.trim();
        if (respuestaLimpia === '<div id="no-more-posts"></div>') {
            log('No hay más publicaciones');
            detenerCarga();
        } else {
            const parser = new DOMParser();
            const doc = parser.parseFromString(respuesta, 'text/html');
    
            doc.querySelectorAll('.EDYQHV').forEach(publicacion => {
                const idPublicacion = publicacion.getAttribute('id-post');
                if (idPublicacion && !publicacionesCargadas.has(idPublicacion)) {
                    publicacionesCargadas.add(idPublicacion);
                    log('Publicación añadida:', idPublicacion);
                }
            });
    
            const listaPublicaciones = document.querySelector('.tab.active .social-post-list');
            if (respuestaLimpia && !doc.querySelector('#no-more-posts')) {
                listaPublicaciones.insertAdjacentHTML('beforeend', respuesta);
                log('Contenido añadido');
                paginaActual++;
                ['inicializarWaveforms', 'empezarcolab', 'submenu', 'seguir', 'modalDetallesIA', 'tagsPosts'].forEach(funcion => {
                    if (typeof window[funcion] === 'function') window[funcion]();
                });
    
                // **Aquí llamamos a reiniciarEventosPostTag para configurar los nuevos tags**
                reiniciarEventosPostTag();
            } else {
                log('No más publicaciones o respuesta vacía');
                detenerCarga();
            }
        }
    }

    /**
     * Reinicia los eventos de clic en los <span class="postTag">
     */
    function reiniciarEventosPostTag() {
        log('Reiniciando eventos de clic en <span class="postTag">');
        configurarEventosPostTag();
    }

    function habilitarCargaPorScroll() {
        log('Configurando evento de scroll');
        window.addEventListener('scroll', manejarScroll);
    }

    function configurarEventosPostTag() {
        const postTags = document.querySelectorAll('.postTag');

        postTags.forEach(tag => {
            tag.removeEventListener('click', manejadorClickPostTag);
            tag.addEventListener('click', manejadorClickPostTag);
        });

        log('Eventos de clic configurados para <span class="postTag">');
    }

    function manejadorClickPostTag(e) {
        const tag = e.currentTarget;
        const valorTag = tag.textContent.trim();
        log('Tag clicado:', valorTag);
        identificador = valorTag;
        resetearCarga();
        cargarMasContenido();
    }

    function configurarEventoBusqueda() {
        const inputBusqueda = document.getElementById('identifier');
        if (inputBusqueda) {
            inputBusqueda.removeEventListener('keypress', manejadorEventoBusqueda);
            inputBusqueda.addEventListener('keypress', manejadorEventoBusqueda);
        } else {
            log('No se encontró el elemento input de búsqueda');
        }
    }

    function manejadorEventoBusqueda(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            identificador = e.target.value.trim();
            log('Enter presionado, valor de identificador:', identificador);
            resetearCarga();
            cargarMasContenido();
        }
    }

    function resetearCarga() {
        paginaActual = 1;
        publicacionesCargadas.clear();
        habilitarCargaPorScroll();
        log('Ejecutando resetearCarga');
        const listaPublicaciones = document.querySelector('.tab.active .social-post-list');
        if (listaPublicaciones) {
            listaPublicaciones.innerHTML = '';
        }
        hayMasContenido = true;
    }

    function detenerCarga() {
        log('Carga detenida');
        hayMasContenido = false;
        window.removeEventListener('scroll', manejarScroll);
    }

    function ajustarAlturaMaxima() {
        const contenedor = document.querySelector('.SAOEXP .clase-rolastatus');
        const elemento = contenedor?.querySelector('li[filtro="rolastatus"]');
        if (contenedor && elemento) {
            contenedor.style.maxHeight = `${elemento.offsetHeight + 40}px`;
        }
    }

    window.addEventListener('resize', ajustarAlturaMaxima);

    // Inicializar al cargar el script
    reiniciarCargaDiferida();
})();
