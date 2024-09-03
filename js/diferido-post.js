//////////////////////////////////////////////
//ACTIVAR O DESACTIVAR LOGS
const A07 = true; // Cambia a true para activar los logs
const log07 = A07 ? console.log : function () {};
//////////////////////////////////////////////

let cargando = false;
let paged = 2;
let publicacionesCargadas = [];
let identifier = '';
let ultimoLog = 0;
const intervaloLog = 1000;
let eventoBusquedaConfigurado = false;
const ajaxUrl = (typeof ajax_params !== 'undefined' && ajax_params.ajax_url) 
                ? ajax_params.ajax_url 
                : '/wp-admin/admin-ajax.php';
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
        eventoBusquedaConfigurado = true; // Marca como configurado
    }
    ajustarAlturaMaxima();
    cargarContenidoPorScroll();
    establecerUserIdDesdeInput();
}

function establecerUserIdDesdeInput() {
    const paginaActualInput = document.getElementById('pagina_actual');

    if (paginaActualInput && paginaActualInput.value.toLowerCase() === 'sello') {
        const userIdInput = document.getElementById('user_id');

        if (userIdInput) {
            const userId = userIdInput.value;
            const userProfileContainer = document.querySelector('.custom-uprofile-container');
            if (userProfileContainer) {
                userProfileContainer.dataset.authorId = userId;
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
    const ahora = Date.now();

    if (ahora - ultimoLog >= intervaloLog) {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = Math.max(document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight);
        // const noMorePostsExists = document.getElementById('no-more-posts');

        log07('Evento de scroll detectado:', {
            scrollTop,
            windowHeight,
            documentHeight,
            cargando
            //noMorePostsExists: !!noMorePostsExists
        });

        if (scrollTop + windowHeight > documentHeight - 100 && !cargando) {
            log07('Condiciones para cargar más contenido cumplidas');
            cargarMasContenido();
        } else {
            log07('Condiciones para cargar más contenido no cumplidas');
        }

        ultimoLog = ahora;
    }
}

//de alguna forma, tiene que evitar cargar mas contenido si <div id="no-more-posts-two" no-more="<?php echo esc_attr($filtro);?>"></div> si no-more contiene el filtro que se intenta cargar, te muestro la parte relavante del codigo

function cargarMasContenido() {
    cargando = true;
    log07('Iniciando carga de más contenido');

    // Primero, encontrar la pestaña activa
    const activeTabElement = document.querySelector('.tab.active');

    // Verificar si la pestaña activa tiene ajax="no"
    if (activeTabElement && activeTabElement.getAttribute('ajax') === 'no') {
        log07('La pestaña activa tiene ajax="no". No se cargará más contenido.');
        cargando = false;
        return;
    }

    // Si no tiene ajax="no", proceder con la búsqueda del contenedor de posts
    const activeTab = document.querySelector('.tab.active .social-post-list');
    if (!activeTab) {
        log07('No se encontró una pestaña activa');
        cargando = false;
        return;
    }

    const filtroActual = activeTab.dataset.filtro;
    const tabIdActual = activeTab.dataset.tabId;
    const userProfileContainer = document.querySelector('.custom-uprofile-container');

    let user_id = ''; // Declare user_id here
    if (window.currentUserId) {
        user_id = window.currentUserId;
    } else if (userProfileContainer) {
        user_id = userProfileContainer.dataset.authorId;
    }

    log07('Parámetros de carga:', {
        filtroActual,
        tabIdActual,
        identifier,
        user_id,
        paged
    });

    fetch(ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cargar_mas_publicaciones&paged=${paged}&filtro=${filtroActual}&identifier=${identifier}&tab_id=${tabIdActual}&user_id=${user_id}&cargadas=${publicacionesCargadas.join(',')}`
    })
        .then(response => response.text())
        .then(procesarRespuesta)
        .catch(error => {
            log07('Error AJAX:', error);
            cargando = false;
        });
}

function procesarRespuesta(response) {
    log07('Respuesta recibida:', response.substring(0, 100) + '...');
    if (response.trim() === '<div id="no-more-posts"></div>') {
        log07('No hay más publicaciones');
        detenerCarga();
    } else {
        const parser = new DOMParser();
        const doc = parser.parseFromString(response, 'text/html');

        // Buscar todos los elementos con la clase EDYQHV
        doc.querySelectorAll('.EDYQHV').forEach(post => {
            const postId = post.getAttribute('id-post');
            if (postId && !publicacionesCargadas.includes(postId)) {
                publicacionesCargadas.push(postId);
                log07('Post añadido:', postId);
            }
        });

        const activeTab = document.querySelector('.tab.active .social-post-list');
        if (response.trim() !== '' && !doc.querySelector('#no-more-posts')) {
            activeTab.insertAdjacentHTML('beforeend', response);
            log07('Contenido añadido');
            paged++;
            if (typeof window.inicializarWaveforms === 'function') {
                window.inicializarWaveforms();
            } else {
                log07('La función inicializarWaveforms no está definida');
            }
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
    log07('Evento de scroll configurado');
}

// Agregar evento para el campo de búsqueda
function configurarEventoBusqueda() {
    const searchInput = document.getElementById('identifier');

    if (searchInput) {
        searchInput.removeEventListener('keypress', manejadorEventoBusqueda);

        function manejadorEventoBusqueda(e) {
            log07('Evento keypress detectado en searchInput', e);
            if (e.key === 'Enter') {
                publicacionesCargadas = [];
                e.preventDefault();
                identifier = this.value;
                log07('Enter presionado, valor de identifier:', identifier);
                resetearCarga();
                cargarMasContenido();
                paged = 1;
            }
        }

        searchInput.addEventListener('keypress', manejadorEventoBusqueda);
    } else {
        log07('No se encontró el elemento searchInput');
    }
}

function resetearCarga() {
    paged = 1;
    publicacionesCargadas = [];
    window.removeEventListener('scroll', manejarScroll);
    cargarContenidoPorScroll();
    log07('Ejecutando resetearCarga');
    const activeTab = document.querySelector('.tab.active .social-post-list');
    if (activeTab) {
        log07('Encontrado activeTab, reseteando contenido');
        activeTab.innerHTML = '';
    } else {
        log07('No se encontró el elemento activeTab');
    }
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
        const alturaElemento = elementos[0].offsetHeight;
        const alturaMaxima = alturaElemento + 40;
        contenedor.style.maxHeight = `${alturaMaxima}px`;
    }
}

window.addEventListener('resize', ajustarAlturaMaxima);
