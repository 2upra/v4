const A07 = false, log07 = A07 ? console.log : () => {};
let cargando = false, paged = 2, publicacionesCargadas = [], identifier = '', ultimoLog = 0, eventoBusquedaConfigurado = false;
const intervaloLog = 1000, ajaxUrl = (typeof ajax_params !== 'undefined' && ajax_params.ajax_url) ? ajax_params.ajax_url : '/wp-admin/admin-ajax.php';

// Manejador de evento de búsqueda, ahora está definido globalmente
function manejadorEventoBusqueda(e) {
    log07('Evento keypress detectado en searchInput', e);
    if (e.key === 'Enter') {
        publicacionesCargadas = [];
        e.preventDefault();
        identifier = e.target.value;
        log07('Enter presionado, valor de identifier:', identifier);
        resetearCarga();
        cargarMasContenido();
        paged = 1;
    }
}

function reiniciarDiferidoPost() {
    log07('Reiniciando diferidopost');
    window.removeEventListener('scroll', manejarScroll);
    cargando = false; paged = 2; publicacionesCargadas = []; identifier = ''; window.currentUserId = null;
    if (!eventoBusquedaConfigurado) configurarEventoBusqueda(); // Evento ahora configurado correctamente
    ajustarAlturaMaxima(); cargarContenidoPorScroll(); establecerUserIdDesdeInput();
}

function establecerUserIdDesdeInput() {
    const paginaActualInput = document.getElementById('pagina_actual');
    if (paginaActualInput?.value.toLowerCase() === 'sello') {
        const userId = document.getElementById('user_id')?.value;
        if (userId) {
            document.querySelector('.custom-uprofile-container')?.setAttribute('data-author-id', userId);
            window.currentUserId = userId;
            log07('User ID establecido:', userId);
        } else log07('No se encontró el input de user_id');
    } else log07('La página actual no es "sello"');
}

function manejarScroll() {
    const ahora = Date.now();
    if (ahora - ultimoLog < intervaloLog) return;

    const { scrollTop, innerHeight: windowHeight } = window;
    const documentHeight = Math.max(document.body.scrollHeight, document.body.offsetHeight, document.documentElement.scrollHeight);
    log07('Evento de scroll detectado:', { scrollTop, windowHeight, documentHeight, cargando });

    if (scrollTop + windowHeight > documentHeight - 100 && !cargando) {
        log07('Condiciones para cargar más contenido cumplidas');
        cargarMasContenido();
    }
    ultimoLog = ahora;
}

function cargarMasContenido() {
    cargando = true;
    log07('Iniciando carga de más contenido');
    
    const activeTabElement = document.querySelector('.tab.active');
    if (activeTabElement?.getAttribute('ajax') === 'no') return detenerCarga();
    
    const activeTab = activeTabElement.querySelector('.social-post-list');
    if (!activeTab) return detenerCarga();

    const filtroActual = activeTab.dataset.filtro, tabIdActual = activeTab.dataset.tabId;
    const user_id = window.currentUserId || document.querySelector('.custom-uprofile-container')?.dataset.authorId || '';
    
    log07('Parámetros de carga:', { filtroActual, tabIdActual, identifier, user_id, paged });

    fetch(ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cargar_mas_publicaciones&paged=${paged}&filtro=${filtroActual}&identifier=${identifier}&tab_id=${tabIdActual}&user_id=${user_id}&cargadas=${publicacionesCargadas.join(',')}`
    })
    .then(res => res.text())
    .then(procesarRespuesta)
    .catch(err => log07('Error AJAX:', err))
    .finally(() => cargando = false);
}

function procesarRespuesta(response) {
    log07('Respuesta recibida:', response.substring(0, 100) + '...');
    if (response.trim() === '') return detenerCarga();

    const doc = new DOMParser().parseFromString(response, 'text/html');
    doc.querySelectorAll('.EDYQHV').forEach(post => {
        const postId = post.getAttribute('id-post');
        if (postId && !publicacionesCargadas.includes(postId)) {
            publicacionesCargadas.push(postId);
            log07('Post añadido:', postId);
        }
    });

    const activeTab = document.querySelector('.tab.active .social-post-list');
    if (response.trim() && !doc.querySelector('#no-more-posts')) {
        activeTab.insertAdjacentHTML('beforeend', response);
        log07('Contenido añadido');
        paged++;
        window.inicializarWaveforms?.();
        window.empezarcolab?.();
        window.submenu?.();
        window.seguir?.();
        window.modalDetallesIA?.();
    } else detenerCarga();
}

function cargarContenidoPorScroll() {
    log07('Configurando evento de scroll');
    window.addEventListener('scroll', manejarScroll);
}

function configurarEventoBusqueda() {
    const searchInput = document.getElementById('identifier');
    if (!searchInput) return log07('No se encontró el elemento searchInput');

    // Remueve el evento anterior si es necesario
    searchInput.removeEventListener('keypress', manejadorEventoBusqueda);

    // Agrega el evento de nuevo con la función manejadorEventoBusqueda
    searchInput.addEventListener('keypress', manejadorEventoBusqueda);
}

function resetearCarga() {
    paged = 1; publicacionesCargadas = [];
    window.removeEventListener('scroll', manejarScroll);
    cargarContenidoPorScroll();
    log07('Ejecutando resetearCarga');
    const activeTab = document.querySelector('.tab.active .social-post-list');
    if (activeTab) activeTab.innerHTML = '';
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
    if (elementos.length) {
        contenedor.style.maxHeight = `${elementos[0].offsetHeight + 40}px`;
    }
}

window.addEventListener('resize', ajustarAlturaMaxima);