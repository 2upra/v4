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

        ajustarAlturaMaxima();
        habilitarCargaPorScroll();
        establecerIdUsuarioDesdeInput();
        configurarDelegacionEventosPostTag();

        // Configurar el botón de limpiar
        const botonLimpiar = document.getElementById('clearSearch');
        if (botonLimpiar) {
            botonLimpiar.addEventListener('click', e => {
                e.preventDefault();
                limpiarBusqueda();
            });
        }

        const busquedaInicial = obtenerBusquedaDeURL();
        if (busquedaInicial) {
            identificador = busquedaInicial;
            actualizarUIBusqueda(busquedaInicial);
            cargarMasContenido();
        }

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
    //resto del codigo omitido...
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
        }, 20);
    }

    // Inicializar con publicaciones existentes
    document.querySelectorAll('.social-post-list .EDYQHV').forEach(publicacion => {
        const idPublicacion = publicacion.getAttribute('id-post')?.trim();
        if (idPublicacion) {
            publicacionesCargadas.add(idPublicacion);
        }
    });

    async function cargarMasContenido() {
        if (estaCargando) {
            log('Carga en progreso. Espera a que finalice antes de intentar nuevamente.');
            return;
        }

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

            if (!respuesta.ok) {
                throw new Error(`HTTP error! status: ${respuesta.status}`);
            }

            const textoRespuesta = await respuesta.text();
            await procesarRespuesta(textoRespuesta);
        } catch (error) {
            log('Error en la petición AJAX:', error);
        } finally {
            estaCargando = false;
        }
    }
    const MAX_POSTS = 50;

    async function procesarRespuesta(respuesta) {
        log('Respuesta recibida:', respuesta.substring(0, 100) + '...');
        const respuestaLimpia = respuesta.trim();

        if (respuestaLimpia === '<div id="no-more-posts"></div>') {
            log('No hay más publicaciones');
            detenerCarga();
            return;
        }

        if (!respuestaLimpia) {
            log('Respuesta vacía recibida');
            detenerCarga();
            return;
        }

        const parser = new DOMParser();
        const doc = parser.parseFromString(respuesta, 'text/html');

        // Extraer y actualizar el valor de total-posts-sampleList de la respuesta
        const totalPostsInputFromResponse = doc.querySelector('.total-posts-sampleList');
        if (totalPostsInputFromResponse) {
            const totalPostsValue = totalPostsInputFromResponse.getAttribute('value');
            const totalPostsInputInDOM = document.querySelector('.total-posts-sampleList');
            if (totalPostsInputInDOM) {
                totalPostsInputInDOM.value = totalPostsValue;
                log('Campo total-posts-sampleList actualizado desde la respuesta:', totalPostsValue);
                
            }
        }

        const publicacionesNuevas = doc.querySelectorAll('.EDYQHV');
        if (publicacionesNuevas.length === 0) {
            log('No se encontraron publicaciones nuevas en la respuesta');
            detenerCarga();
            return;
        }

        // Filtrar publicaciones duplicadas
        const publicacionesValidas = [];
        publicacionesNuevas.forEach(publicacion => {
            const idPublicacion = publicacion.getAttribute('id-post')?.trim();
            const existeEnDOM = document.querySelector(`.social-post-list .EDYQHV[id-post="${idPublicacion}"]`);

            if (idPublicacion && !publicacionesCargadas.has(idPublicacion) && !existeEnDOM) {
                publicacionesCargadas.add(idPublicacion);
                publicacionesValidas.push(publicacion.outerHTML);
                log('Publicación añadida:', idPublicacion);
            } else {
                log('Publicación duplicada omitida:', idPublicacion);
            }
        });

        if (publicacionesValidas.length > 0) {
            const listaPublicaciones = document.querySelector('.tab.active .social-post-list');
            if (listaPublicaciones) {
                listaPublicaciones.insertAdjacentHTML('beforeend', publicacionesValidas.join(''));
                log('Contenido añadido');
                paginaActual++;

                // Limitar el número de publicaciones en el DOM
                const publicacionesEnDOM = listaPublicaciones.querySelectorAll('.EDYQHV');
                if (publicacionesEnDOM.length > MAX_POSTS) {
                    const exceso = publicacionesEnDOM.length - MAX_POSTS;
                    for (let i = 0; i < exceso; i++) {
                        const elementoAEliminar = publicacionesEnDOM[i];
                        const idPublicacion = elementoAEliminar.getAttribute('id-post')?.trim();
                        if (idPublicacion) {
                            publicacionesCargadas.delete(idPublicacion);
                        }
                        listaPublicaciones.removeChild(elementoAEliminar);
                        log('Publicación eliminada para mantener el límite:', idPublicacion);
                    }
                }

                // Inicializar funciones necesarias
                ['inicializarWaveforms', 'empezarcolab', 'submenu', 'seguir', 'modalDetallesIA', 'tagsPosts', 'handleAllRequests', 'registrarVistas', 'colec'].forEach(funcion => {
                    if (typeof window[funcion] === 'function') window[funcion]();
                });

                // Actualiza los eventos de delegación si es necesario
                reiniciarEventosPostTag();
            } else {
                log('No se encontró .social-post-list para añadir contenido');
            }
        } else {
            log('No hay publicaciones válidas para añadir');
            detenerCarga();
        }
    }
    

    function reiniciarEventosPostTag() {
        log('Reiniciando eventos de clic mediante delegación en <span class="postTag">');
        configurarDelegacionEventosPostTag();
    }

    function habilitarCargaPorScroll() {
        log('Configurando evento de scroll');
        window.addEventListener('scroll', manejarScroll);
    }

    function configurarDelegacionEventosPostTag() {
        document.removeEventListener('click', delegarClickPostTag);
        document.addEventListener('click', delegarClickPostTag);
        log('Delegación de eventos de clic configurada globalmente');
    }

    function delegarClickPostTag(e) {
        const tag = e.target.closest('.postTag');
        if (tag) {
            e.preventDefault();
            e.stopPropagation();
            const valorTag = tag.textContent.trim();

            if (valorTag) {
                identificador = valorTag;
                actualizarUIBusqueda(valorTag);
                log('Nuevo identificador establecido:', identificador);
                resetearCarga();
                cargarMasContenido();
            }
        }
    }

    window.limpiarBusqueda = function() {
        //publicacionesCargadas.clear();
        identificador = '';
        actualizarUIBusqueda('');
        resetearCarga();
        cargarMasContenido();
    }

    function configurarEventoBusqueda() {
        const inputBusqueda = document.getElementById('identifier');
        if (inputBusqueda) {
            inputBusqueda.removeEventListener('keypress', manejadorEventoBusqueda);
            inputBusqueda.addEventListener('keypress', manejadorEventoBusqueda);
            log('Evento de búsqueda configurado para el input #identifier');
        } else {
            log('No se encontró el elemento input de búsqueda');
        }
    }

    function manejadorEventoBusqueda(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            identificador = e.target.value.trim();
            actualizarUIBusqueda(identificador);
            log('Enter presionado en búsqueda, valor de identificador:', identificador);
            resetearCarga();
            cargarMasContenido();
        }
    }

    function resetearCarga() {
        log('Ejecutando resetearCarga');
        paginaActual = 1;
        publicacionesCargadas.clear();
        hayMasContenido = true;

        const listaPublicaciones = document.querySelector('.tab.active .social-post-list');
        if (listaPublicaciones) {
            listaPublicaciones.innerHTML = '';
        } else {
            log('No se encontró .social-post-list para limpiar contenido');
        }

        // Opcional: Scroll hacia la parte superior
        window.scrollTo(0, 0);
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
            log('Altura máxima ajustada');
        } else {
            log('No se encontró contenedor o elemento para ajustar altura máxima');
        }
    }

    window.addEventListener('resize', ajustarAlturaMaxima);

    // Inicializar al cargar el script
    reiniciarCargaDiferida();

    /////////////////////////////////
    // Actualizar URL con el parámetro de búsqueda
    function actualizarURL(busqueda) {
        const nuevaURL = new URL(window.location);
        if (busqueda) {
            nuevaURL.searchParams.set('search', busqueda);
        } else {
            nuevaURL.searchParams.delete('search');
        }
        window.history.pushState({}, '', nuevaURL);
    }

    // Obtener búsqueda de la URL al cargar
    function obtenerBusquedaDeURL() {
        const params = new URLSearchParams(window.location.search);
        return params.get('search') || '';
    }

    // Modificar la función actualizarUIBusqueda
    function actualizarUIBusqueda(valor) {
        const inputBusqueda = document.getElementById('identifier');
        const botonLimpiar = document.getElementById('clearSearch');

        if (inputBusqueda) {
            inputBusqueda.value = valor;
        }

        if (botonLimpiar) {
            botonLimpiar.style.display = valor ? 'block' : 'none';
        }

        actualizarURL(valor);
    }
})();