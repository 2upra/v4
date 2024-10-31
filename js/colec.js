
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
    document.body.addEventListener('click', e => {
        const btn = e.target.closest('.botonColeccionBtn');
        if (btn) {
            e.preventDefault();
            colecPostId = btn.getAttribute('data-post_id');
            console.log('Post ID seleccionado:', colecPostId);
            abrirColec();
        }
    });

    document.addEventListener('click', e => {
        const coleccion = e.target.closest('.coleccion');
        if (coleccion && coleccion.closest('.listaColeccion')) {
            manejarClickColec(coleccion);
        }
    });

    pun('#btnListo')?.addEventListener('click', manejarClickListoColec);
    pun('#btnEmpezarCreaColec')?.addEventListener('click', abrirModalCrearColec);
    //pun('#btnCrearColec')?.addEventListener('click', crearNuevaColec);
    pun('#btnVolverColec')?.addEventListener('click', volverColec);

    const buscarInput = pun('#buscarColeccion');
    if (buscarInput) {
        buscarInput.addEventListener('input', () => {
            const query = buscarInput.value.toLowerCase();
            busquedaColec(query);
        });
    }

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