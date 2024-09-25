(function () {
    'use strict';

    const A07 = false;
    const log07 = A07 ? console.log.bind(console) : () => {};

    let cargando = false;
    let paged = 2;
    const publicacionesCargadas = new Set();
    let identifier = '';
    let eventoBusquedaConfigurado = false;
    let scrollTimeout = null;



    // Función que se llama cada vez que se cambia de página mediante AJAX
    function reiniciarDiferidoPost() {
        log07('Reiniciando diferidopost');
        window.removeEventListener('scroll', manejarScroll);
        cargando = false;
        paged = 2;
        publicacionesCargadas.clear();
        identifier = '';
        window.currentUserId = null;

        if (!eventoBusquedaConfigurado) {
            configurarEventoBusqueda();
            eventoBusquedaConfigurado = true;
        }
        ajustarAlturaMaxima();
        cargarContenidoPorScroll();
        establecerUserIdDesdeInput();
    }

    function establecerUserIdDesdeInput() {
        const paginaActualInput = document.getElementById('pagina_actual');
        if (paginaActualInput?.value.toLowerCase() === 'sello') {
            const userIdInput = document.getElementById('user_id');
            if (userIdInput) {
                const userId = userIdInput.value;
                const profileContainer = document.querySelector('.custom-uprofile-container');
                if (profileContainer) {
                    profileContainer.setAttribute('data-author-id', userId);
                }
                window.currentUserId = userId;
                log07('User ID establecido:', userId);
            } else {
                log07('No se encontró el input de user_id');
            }
        } else {
            log07('La página actual no es "sello"');
        }
    }

    function manejarScroll() {
        if (scrollTimeout) return;
        scrollTimeout = setTimeout(() => {
            scrollTimeout = null;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const documentHeight = Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.clientHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight
            );

            log07('Evento de scroll detectado:', { scrollTop, windowHeight, documentHeight, cargando });

            if (scrollTop + windowHeight > documentHeight - 100 && !cargando) {
                log07('Condiciones para cargar más contenido cumplidas');
                cargarMasContenido();
            } else {
                log07('Condiciones para cargar más contenido no cumplidas');
            }
        }, 200); // Ajusta el tiempo de espera según sea necesario
    }

    async function cargarMasContenido() {
        cargando = true;
        log07('Iniciando carga de más contenido');

        const activeTabElement = document.querySelector('.tab.active');
        if (activeTabElement?.getAttribute('ajax') === 'no') {
            log07('La pestaña activa tiene ajax="no". No se cargará más contenido.');
            cargando = false;
            return;
        }

        const activeTab = document.querySelector('.tab.active .social-post-list');
        if (!activeTab) {
            log07('No se encontró una pestaña activa');
            cargando = false;
            return;
        }

        const { filtro: filtroActual = '', tabId: tabIdActual = '' } = activeTab.dataset;
        const user_id = window.currentUserId || document.querySelector('.custom-uprofile-container')?.dataset.authorId || '';

        log07('Parámetros de carga:', { filtroActual, tabIdActual, identifier, user_id, paged });

        try {
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'cargar_mas_publicaciones',
                    paged,
                    filtro: filtroActual,
                    identifier,
                    tab_id: tabIdActual,
                    user_id,
                    cargadas: Array.from(publicacionesCargadas).join(',')
                })
            });

            const textResponse = await response.text();
            await procesarRespuesta(textResponse);
        } catch (error) {
            log07('Error AJAX:', error);
            cargando = false;
        }
    }

    async function procesarRespuesta(response) {
        log07('Respuesta recibida:', response.substring(0, 100) + '...');
        if (response.trim() === '<div id="no-more-posts"></div>') {
            log07('No hay más publicaciones');
            detenerCarga();
        } else {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response, 'text/html');

            doc.querySelectorAll('.EDYQHV').forEach(post => {
                const postId = post.getAttribute('id-post');
                if (postId && !publicacionesCargadas.has(postId)) {
                    publicacionesCargadas.add(postId);
                    log07('Post añadido:', postId);
                }
            });

            const activeTab = document.querySelector('.tab.active .social-post-list');
            if (response.trim() && !doc.querySelector('#no-more-posts')) {
                activeTab.insertAdjacentHTML('beforeend', response);
                log07('Contenido añadido');
                paged++;
                ['inicializarWaveforms', 'empezarcolab', 'submenu', 'seguir', 'modalDetallesIA'].forEach(fn => {
                    if (typeof window[fn] === 'function') window[fn]();
                });
            } else {
                log07('No más publicaciones o respuesta vacía');
                detenerCarga();
            }
        }
        cargando = false;
    }

    function cargarContenidoPorScroll() {
        log07('Configurando evento de scroll');
        window.addEventListener('scroll', manejarScroll);
    }

    function configurarEventoBusqueda() {
        const searchInput = document.getElementById('identifier');
        if (searchInput) {
            searchInput.removeEventListener('keypress', manejadorEventoBusqueda);
            searchInput.addEventListener('keypress', manejadorEventoBusqueda);
        } else {
            log07('No se encontró el elemento searchInput');
        }

        function manejadorEventoBusqueda(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                identifier = e.target.value.trim();
                log07('Enter presionado, valor de identifier:', identifier);
                resetearCarga();
                cargarMasContenido();
            }
        }
    }

    function resetearCarga() {
        paged = 1;
        publicacionesCargadas.clear();
        window.removeEventListener('scroll', manejarScroll);
        cargarContenidoPorScroll();
        log07('Ejecutando resetearCarga');
        const socialPostList = document.querySelector('.tab.active .social-post-list');
        if (socialPostList) {
            socialPostList.innerHTML = '';
        }
    }

    function detenerCarga() {
        log07('Carga detenida');
        cargando = true;
        window.removeEventListener('scroll', manejarScroll);
    }

    function ajustarAlturaMaxima() {
        const contenedor = document.querySelector('.SAOEXP .clase-rolastatus');
        if (contenedor) {
            const elemento = contenedor.querySelector('li[filtro="rolastatus"]');
            if (elemento) {
                contenedor.style.maxHeight = `${elemento.offsetHeight + 40}px`;
            }
        }
    }

    window.addEventListener('resize', ajustarAlturaMaxima);

    // Iniciar al cargar el script
    reiniciarDiferidoPost();
})();