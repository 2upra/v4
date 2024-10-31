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
        //console.error('No se encontró el botón con el ID #btnListo');
    }
    const buscarInput = document.getElementById('buscarColeccion');
    if (buscarInput) {
        buscarInput.addEventListener('input', () => {
            const query = buscarInput.value.toLowerCase();
            busquedaColec(query);
        });
    } else {
        //console.error('No se encontró el input con el ID #buscarColeccion');
    }
    document.addEventListener('modalOpened', () => {
        resetColec();
    });
}

function abrirColec() {
    quitBackground();
    const modal = document.querySelector('.modalColec');
    if (!modal) {
        //console.error('No se encontró el elemento con la clase .modalColec');
        return;
    }
    modal.style.display = 'block';
    crearBackgroundColec();
    document.body.classList.add('no-scroll');
}

// Función para eliminar el fondo oscuro
function quitBackground() {
    const darkBackground = document.querySelector('.submenu-background');
    if (darkBackground) {
        //console.log('Eliminando fondo oscuro.');
        darkBackground.remove();
    } else {
        //console.log('No hay fondo oscuro para eliminar.');
    }
}

function resetColec() {
    colecPostId = null;
    colecSelecionado = null;
    document.querySelectorAll('.coleccion').forEach(item => item.classList.remove('seleccion'));
}

function manejarClickColec(coleccion) {
    document.querySelectorAll('.coleccion').forEach(item => item.classList.remove('seleccion'));
    coleccion.classList.add('seleccion');
    colecSelecionado = coleccion.getAttribute('data-id') || coleccion.id;
}

function manejarClickListoColec() {
    if (colecPostId && colecSelecionado) {
        //console.log('Post ID:', colecPostId);
        //console.log('Collection ID:', colecSelecionado);
        cerrarColec();
    } else {
        alert('Por favor, selecciona una colección.');
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
