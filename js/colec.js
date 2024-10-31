
let colecPostId = null;
let colecSelecionado = null;
let colecIniciado = false;

function colec() {
    if (!colecIniciado) {
        iniciarColec();
        colecIniciado = true;
    }
}

function iniciarColec() {
    // Delegación de eventos para los botones de colección
    pun.delegado('click', '.botonColeccionBtn', e => {
        e.preventDefault();
        colecPostId = e.currentTarget.getAttribute('data-post_id');
        console.log('Post ID seleccionado:', colecPostId);
        abrirColec();
    });

    // Delegación para las colecciones
    pun.delegado('click', '.coleccion', e => {
        if (e.currentTarget.closest('.listaColeccion')) {
            manejarClickColec(e.currentTarget);
        }
    });

    // Eventos individuales
    pun('#btnListo').evento('click', manejarClickListoColec);
    pun('#btnEmpezarCreaColec').evento('click', abrirModalCrearColec);
    pun('#btnVolverColec').evento('click', volverColec);

    // Evento input
    pun('#buscarColeccion').evento('input', e => {
        const query = e.currentTarget.value.toLowerCase();
        busquedaColec(query);
    });

    // Evento personalizado
    document.addEventListener('modalOpened', resetColec);
}

function abrirColec() {
    const modal = pun('.modalColec');
    if (!modal) {
        console.error('No se encontró el elemento .modalColec');
        return;
    }
    mostrar(modal);
    crearBackgroundColec();
    pun.agregarClase('body', 'no-scroll');
}

function abrirModalCrearColec() {
    ocultar(pun('.modalColec'));
    mostrar(pun('.modalCrearColec'));
}

function volverColec() {
    ocultar(pun('.modalCrearColec'));
    mostrar(pun('.modalColec'));
}

function busquedaColec(query) {
    pun('.listaColeccion .coleccion').forEach(coleccion => {
        const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
        if (titulo.includes(query)) {
            mostrar(coleccion);
        } else {
            ocultar(coleccion);
        }
    });
}

function cerrarColec() {
    ocultar(pun('.modalColec'));
    ocultar(pun('.modalCrearColec'));
    quitBackground();
    pun.removerClase('body', 'no-scroll');
    resetColec();
}

function manejarClickColec(coleccion) {
    pun.removerClase('.coleccion', 'seleccion');
    pun.agregarClase(coleccion, 'seleccion');
    colecSelecionado = coleccion.getAttribute('data-id') || coleccion.id;
}

function manejarClickListoColec() {
    if (colecPostId && colecSelecionado) {
        cerrarColec();
    } else {
        cerrarColec();
    }
}

function resetColec() {
    colecPostId = null;
    colecSelecionado = null;
    pun.removerClase('.coleccion', 'seleccion');
}

function quitBackground() {
    const darkBackground = pun('.submenu-background');
    if (darkBackground) {
        darkBackground.remove();
    }
}

function crearBackgroundColec() {
    if (pun('.submenu-background')) return;

    const darkBackground = document.createElement('div');
    darkBackground.classList.add('submenu-background');
    Object.assign(darkBackground.style, {
        position: 'fixed',
        top: '0',
        left: '0',
        width: '100vw',
        height: '100vh',
        backgroundColor: 'rgba(0, 0, 0, 0.5)',
        zIndex: '998',
        pointerEvents: 'auto'
    });
    document.body.appendChild(darkBackground);
    darkBackground.addEventListener('click', cerrarColec, {once: true});
    return darkBackground;
}