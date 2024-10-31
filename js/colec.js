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
    // Delegación para botones de colección
    pin.delegar('click', '.botonColeccionBtn', function (e) {
        e.preventDefault();
        colecPostId = this.getAttribute('data-post_id');
        console.log('Post ID seleccionado:', colecPostId);
        abrirColec();
    });

    // Delegación para items de colección
    pin.delegar('click', '.coleccion', function (e) {
        if (this.closest('.listaColeccion')) {
            manejarClickColec(this);
        }
    });

    // Eventos para botones específicos
    pin('#btnListo')?.en('click', manejarClickListoColec);
    pin('#btnEmpezarCreaColec')?.en('click', abrirModalCrearColec);
    pin('#btnVolverColec')?.en('click', volverColec);

    // Evento para búsqueda
    pin('#buscarColeccion')?.en('input', function() {
        const query = this.value.toLowerCase();
        pin.filtrar('.coleccion', coleccion => {
            const nombreElement = coleccion.querySelector('.nombreColec');
            return nombreElement ? nombreElement.textContent.toLowerCase().includes(query) : false;
        });
    });

    // Evento para reset del modal
    pin(document).en('modalOpened', resetColec);
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
