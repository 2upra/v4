
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

    a('#btnListo')?.addEventListener('click', manejarClickListoColec);
    a('#btnEmpezarCreaColec')?.addEventListener('click', abrirModalCrearColec);
    //a('#btnCrearColec')?.addEventListener('click', crearNuevaColec);
    a('#btnVolverColec')?.addEventListener('click', volverColec);

    const buscarInput = a('#buscarColeccion');
    if (buscarInput) {
        buscarInput.addEventListener('input', () => {
            const query = buscarInput.value.toLowerCase();
            busquedaColec(query);
        });
    }

    document.addEventListener('modalOpened', resetColec);
}

function abrirColec() {
    const modal = a('.modalColec');
    mostrar(modal);
    crearBackgroundColec();
    a.agregarClase('body', 'no-scroll');
}

function abrirModalCrearColec() {
    ocultar(a('.modalColec'));
    mostrar(a('.modalCrearColec'));
}

function volverColec() {
    ocultar(a('.modalCrearColec'));
    mostrar(a('.modalColec'));
}

function busquedaColec(query) {
    a('.listaColeccion .coleccion').forEach(coleccion => {
        const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
        if (titulo.includes(query)) {
            mostrar(coleccion);
        } else {
            ocultar(coleccion);
        }
    });
}

function cerrarColec() {
    ocultar(a('.modalColec'));
    ocultar(a('.modalCrearColec'));
    quitBackground();
    a.removerClase('body', 'no-scroll');
    resetColec();
}

function manejarClickColec(coleccion) {
    a.removerClase('.coleccion', 'seleccion');
    a.agregarClase(coleccion, 'seleccion');
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
    a.removerClase('.coleccion', 'seleccion');
}

function quitBackground() {
    const darkBackground = a('.submenu-background');
    if (darkBackground) {
        darkBackground.remove();
    }
}

function crearBackgroundColec() {
    if (a('.submenu-background')) return;

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