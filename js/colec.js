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
    const btnListo = document.getElementById('btnListo');
    if (btnListo) {
        btnListo.addEventListener('click', manejarClickListoColec);
    } else {
        return;
    }
    const btnEmpezarCrearColec = document.getElementById('btnEmpezarCreaColec');
    if (btnEmpezarCrearColec) {
        btnEmpezarCrearColec.addEventListener('click', abrirModalCrearColec);
    } else {
        return;
    }
    const crearColec = document.getElementById('btnCrearColec');
    if (crearColec) {
        crearColec.addEventListener('click', crearNuevaColec);
    } else {
        return;
    }
    const volverColec = document.getElementById('btnVolverColec'); 
    if (volverColec) {
        volverColec.addEventListener('click', volverColec);
    } else {
        return;
    }
    const buscarInput = document.getElementById('buscarColeccion');
    if (buscarInput) {
        buscarInput.addEventListener('input', () => {
            const query = buscarInput.value.toLowerCase();
            busquedaColec(query);
        });
    } else {
        return;
    }
    document.addEventListener('modalOpened', () => {
        resetColec();
    });
}

function abrirColec() {
    const modal = document.querySelector('.modalColec');
    modal.style.display = 'block';
    crearBackgroundColec();
    document.body.classList.add('no-scroll');
}

function abrirModalCrearColec() {
    const modal = document.querySelector('.modalColec');
    modal.style.display = 'none';
    const modalCreaColec = document.querySelector('.modalCrearColec');
    modalCreaColec.style.display = 'block';
}
function crearNuevaColec() {
    if (colecPostId && colecSelecionado) {
 
        cerrarColec();
    } else {
        cerrarColec();
    }

}
function volverColec() {
    const modalCreaColec = document.querySelector('.modalCrearColec');
    modalCreaColec.style.display = 'none';
    const modal = document.querySelector('.modalColec');
    modal.style.display = 'block';
}

function manejarClickColec(coleccion) {
    document.querySelectorAll('.coleccion').forEach(item => item.classList.remove('seleccion'));
    coleccion.classList.add('seleccion');
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
    document.querySelectorAll('.coleccion').forEach(item => item.classList.remove('seleccion'));
}

function quitBackground() {
    const darkBackground = document.querySelector('.submenu-background');
    if (darkBackground) {
        //console.log('Eliminando fondo oscuro.');
        darkBackground.remove();
    } else {
        //console.log('No hay fondo oscuro para eliminar.');
    }
}

function busquedaColec(query) {
    document.querySelectorAll('.listaColeccion .coleccion').forEach(coleccion => {
        const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
        coleccion.style.display = titulo.includes(query) ? 'flex' : 'none';
    });
}

function cerrarColec() {
    const modal = document.querySelector('.modalColec');
    if (modal) {
        modal.style.display = 'none';
    }
    quitBackground();
    document.body.classList.remove('no-scroll');
    resetColec();
}

function crearBackgroundColec() {
    let existingBackground = document.querySelector('.submenu-background');
    if (existingBackground) {
        //console.log('Fondo oscuro ya existe.');
        return existingBackground;
    }

    //console.log('Creando fondo oscuro.');
    let darkBackground = document.createElement('div');
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
