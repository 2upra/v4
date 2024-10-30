let selectedPostId = null;
let selectedCollectionId = null;

function colec() {
    initializeColec();
}

function initializeColec() {
    // Delegación de eventos para botones de colección
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.botonColeccionBtn');
        if (btn) {
            e.preventDefault();
            selectedPostId = btn.getAttribute('data-post_id');
            openColecModal();
        }
    });

    document.addEventListener('click', (e) => {
        const coleccion = e.target.closest('.coleccion');
        if (coleccion && coleccion.closest('.listaColeccion')) {
            handleCollectionClick(coleccion);
        }
    });

    // Evento para el botón "Listo"
    const btnListo = document.getElementById('btnListo');
    if (btnListo) {
        btnListo.addEventListener('click', handleListoClick);
    } else {
        console.error('No se encontró el botón con el ID #btnListo');
    }

    // Evento para el input de búsqueda
    const buscarInput = document.getElementById('buscarColeccion');
    if (buscarInput) {
        buscarInput.addEventListener('input', () => {
            const query = buscarInput.value.toLowerCase();
            filterCollections(query);
        });
    } else {
        console.error('No se encontró el input con el ID #buscarColeccion');
    }

    // Escuchar la apertura de otros modales para resetear selecciones
    document.addEventListener('modalOpened', () => {
        resetSelections();
    });
}

// Función para eliminar el fondo oscuro
function removeDarkBackgroundColec() {
    const darkBackground = document.querySelector('.submenu-background');
    if (darkBackground) {
        darkBackground.remove();
    }
}

// Función para resetear las selecciones
function resetSelections() {
    selectedPostId = null;
    selectedCollectionId = null;
    document.querySelectorAll('.coleccion').forEach(item => item.classList.remove('seleccion'));
}

// Función para manejar el clic en una colección
function handleCollectionClick(coleccion) {
    document.querySelectorAll('.coleccion').forEach(item => item.classList.remove('seleccion'));
    coleccion.classList.add('seleccion');
    selectedCollectionId = coleccion.getAttribute('data-id') || coleccion.id;
}

// Función para manejar el clic en "Listo"
function handleListoClick() {
    if (selectedPostId && selectedCollectionId) {
        console.log('Post ID:', selectedPostId);
        console.log('Collection ID:', selectedCollectionId);
        closeColecModal();
    } else {
        alert('Por favor, selecciona una colección.');
    }
}

// Función para filtrar colecciones
function filterCollections(query) {
    document.querySelectorAll('.listaColeccion .coleccion').forEach(coleccion => {
        const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
        coleccion.style.display = titulo.includes(query) ? 'flex' : 'none';
    });
}

// Función para abrir el modal
function openColecModal() {
    const modal = document.querySelector('.modalColec');
    if (!modal) {
        console.error('No se encontró el elemento con la clase .modalColec');
        return;
    }
    modal.style.display = 'block';
    createDarkBackgroundColec();
    document.body.classList.add('no-scroll');
}

// Función para cerrar el modal
function closeColecModal() {
    const modal = document.querySelector('.modalColec');
    if (modal) {
        modal.style.display = 'none';
    }
    removeDarkBackgroundColec();
    document.body.classList.remove('no-scroll');
    resetSelections();
}

// Función para crear el fondo oscuro
function createDarkBackgroundColec() {
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
    darkBackground.addEventListener('click', closeColecModal, { once: true });
    return darkBackground;
}




