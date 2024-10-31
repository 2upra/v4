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
    a('#btnCrearColec')?.addEventListener('click', crearNuevaColec);
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
    a.gregar('body', 'no-scroll');
}

function abrirModalCrearColec() {
    ocultar(a('.modalColec'));
    mostrar(a('.modalCrearColec'));
}

function volverColec() {
    ocultar(a('.modalCrearColec'));
    mostrar(a('.modalColec'));
}

function verificarColec() {
    const titulo = a('#tituloColec').value;
    function verificarCamposColec() {
        if (!colecPostId && !colecSelecionado) {
            alert('Por favor, selecciona una colección.');
            return false;
        }
        if (titulo.length < 3) {
            alert('Por favor, ingresa un nombre para tu colección.');
            return false;
        }
        return true;
    }
    return verificarCamposColec;
}

async function crearNuevaColec() {
    // Verificar si los campos son válidos
    const esValido = verificarColec()(); // Ejecuta la función de validación
    if (!esValido) return;

    // Recolectar los datos del formulario
    const titulo = a('#tituloColec').value;
    const descripcion = a('#descripColec').value || ''; 
    

    const data = {
        colecPostId,       
        colecSelecionado, 
        imgColec,         
        titulo,           
        descripcion        
    };
)
    const button = a('#btnCrearColec');
    const originalText = button.innerText;
    button.innerText = 'Guardando...';
    button.disabled = true;

    try {
        // Enviar la petición AJAX
        const response = await enviarAjax('crearColeccion', data);

        // Manejar la respuesta del servidor
        if (response?.success) {
            alert('Colección creada con éxito');
            cerrarColec();        
        } else {
            alert(`Error al crear la colección: ${response?.message || 'Desconocido'}`);
        }
    } catch (error) {
        console.error('Error al enviar los datos:', error);
        alert('Ocurrió un error durante la creación de la colección. Por favor, inténtelo de nuevo.');
    } finally {
        // Restaurar el estado original del botón
        button.innerText = originalText;
        button.disabled = false;
    }
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
    a.quitar('body', 'no-scroll');
    resetColec();
}

function manejarClickColec(coleccion) {
    a.quitar('.coleccion', 'seleccion');
    a.gregar(coleccion, 'seleccion');
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
    a.quitar('.coleccion', 'seleccion');
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
