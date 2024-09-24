const A07 = false;
const log07 = A07 ? console.log : () => {};
let cargando = false;
let paged = 2;
let publicacionesCargadas = [];
let identifier = '';
const ajaxUrl = ajax_params?.ajax_url || '/wp-admin/admin-ajax.php';
let eventoBusquedaConfigurado = false;

// Cache del input de búsqueda para evitar múltiples búsquedas en el DOM
let searchInputCache = null;

//FUNCION REINICIADORA CADA VEZ QUE SE CAMBIA DE PAGINA MEDIANTE AJAX
function reiniciarDiferidoPost() {
    log07('Reiniciando diferidopost');
    window.removeEventListener('scroll', manejarScroll);
    cargando = false;
    paged = 2;
    publicacionesCargadas = [];
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
            document.querySelector('.custom-uprofile-container')?.dataset.authorId = userId;
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
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;

    log07('Evento de scroll detectado:', { scrollTop, windowHeight, documentHeight, cargando });

    if (scrollTop + windowHeight > documentHeight - 100 && !cargando) {
        log07('Condiciones para cargar más contenido cumplidas');
        cargarMasContenido();
    } else {
        log07('Condiciones para cargar más contenido no cumplidas');
    }
}


function cargarMasContenido() {
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

    const filtroActual = activeTab.dataset.filtro;
    const tabIdActual = activeTab.dataset.tabId;
    const user_id = window.currentUserId || document.querySelector('.custom-uprofile-container')?.dataset.authorId || '';

    log07('Parámetros de carga:', { filtroActual, tabIdActual, identifier, user_id, paged });

    const body = new URLSearchParams({
        action: 'cargar_mas_publicaciones',
        paged,
        filtro: filtroActual,
        identifier,
        tab_id: tabIdActual,
        user_id,
        cargadas: publicacionesCargadas.join(',')
    });


    fetch(ajaxUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
        .then(response => response.text())
        .then(procesarRespuesta)
        .catch(error => {
            log07('Error AJAX:', error);
            cargando = false;
        });
}

function procesarRespuesta(response) {
    log07('Respuesta recibida:', response.substring(0, 100) + '...');

    if (response.trim() === '') {
        log07('No hay más publicaciones');
        detenerCarga();
        return; // Sale de la función si no hay más publicaciones
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(response, 'text/html');

    doc.querySelectorAll('.EDYQHV').forEach(post => {
        const postId = post.getAttribute('id-post');
        if (postId && !publicacionesCargadas.includes(postId)) {
            publicacionesCargadas.push(postId);
            log07('Post añadido:', postId);
        }
    });

    const activeTab = document.querySelector('.tab.active .social-post-list');

     if (response.trim() !== '' && !doc.querySelector('#no-more-posts')) { //<- aqui está la parte importante de la validacion
        activeTab.insertAdjacentHTML('beforeend', response);
        log07('Contenido añadido');
        paged++;
        window.inicializarWaveforms?.(); // Llamadas condicionales
        window.empezarcolab?.();
        window.submenu?.();
        window.seguir?.();
        window.modalDetallesIA?.();
    } else {
        log07('No más publicaciones o respuesta vacía');
        detenerCarga();
    }
	
    cargando = false;
}


function cargarContenidoPorScroll() {
    log07('Configurando evento de scroll');
    window.addEventListener('scroll', manejarScroll, { passive: true }); // passive para mejor rendimiento
    log07('Evento de scroll configurado');
}

function configurarEventoBusqueda() {

    // Cache del input
    if(!searchInputCache) searchInputCache = document.getElementById('identifier')


    if (searchInputCache) {
        searchInputCache.removeEventListener('keypress', manejadorEventoBusqueda);


        function manejadorEventoBusqueda(e) {
            log07('Evento keypress detectado en searchInput', e);
            if (e.key === 'Enter') {
                publicacionesCargadas = [];
                e.preventDefault();
                identifier = this.value;
                log07('Enter presionado, valor de identifier:', identifier);
                resetearCarga();
                cargarMasContenido();
                paged = 1; // o 2, dependiendo de la lógica
            }
        }

        searchInputCache.addEventListener('keypress', manejadorEventoBusqueda);
    } else {
        log07('No se encontró el elemento searchInput');
    }
}

function resetearCarga() {
    paged = 1; // O 2, según tu lógica
    publicacionesCargadas = [];
    window.removeEventListener('scroll', manejarScroll);
    cargarContenidoPorScroll();
    log07('Ejecutando resetearCarga');
    document.querySelector('.tab.active .social-post-list')?.innerHTML = '';
}


function detenerCarga() {
    log07('Carga detenida');
    cargando = true;
    window.removeEventListener('scroll', manejarScroll);
}

function ajustarAlturaMaxima() {
    const contenedor = document.querySelector('.SAOEXP .clase-rolastatus');
    if (!contenedor) return;

    const elementos = contenedor.querySelectorAll('li[filtro="rolastatus"]');
    if (elementos.length > 0) {
        const alturaMaxima = elementos[0].offsetHeight + 40;
        contenedor.style.maxHeight = `${alturaMaxima}px`;
    }
}

window.addEventListener('resize', ajustarAlturaMaxima);