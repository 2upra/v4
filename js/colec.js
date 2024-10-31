let colecSampleId = null;
let colecSelecionado = null;
let colecIniciado = false;
let imgColec = null;
let imgColecId = null;

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
            colecSampleId = btn.getAttribute('data-post_id');
            // console.log('Post ID seleccionado:', colecSampleId);
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


async function abrirColec() {
    const modal = a('.modalColec');
    mostrar(modal);
    crearBackgroundColec();
    a.gregar('body', 'no-scroll');

    // Verificar las colecciones que contienen el sample
    await verificarSampleEnColecciones();
}

async function verificarSampleEnColecciones() {
    try {
        const response = await enviarAjax('verificar_sample_en_colecciones', {
            sample_id: colecSampleId
        });

        if (response.success) {
            // Recorrer todas las colecciones en el modal
            const colecciones = document.querySelectorAll('.coleccion');
            colecciones.forEach(coleccion => {
                const coleccionId = coleccion.dataset.post_id;
                
                // Verificar si esta colección contiene el sample
                if (response.data.colecciones.includes(coleccionId)) {
                    // Agregar indicador visual
                    const existeSpan = document.createElement('span');
                    existeSpan.className = 'ya-existe';
                    existeSpan.textContent = 'Ya existe';
                    coleccion.appendChild(existeSpan);
                }
            });
        } else {
            console.error('Error al verificar las colecciones:', response.message);
        }
    } catch (error) {
        console.error('Error al verificar las colecciones:', error);
    }
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
        if (!colecSampleId) {
            alert('Parece que hay un error, intenta seleccionar algo para guardar nuevamente.');
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

/*
esta funcion hay que expandirla para que guarde colecSample y colecSelecionado en el servidor
los ajax funcionan asi ejemplo 
const response = await enviarAjax('guardarSampleEnColec', {colecSampleId, colecSelecionado});

*/

async function manejarClickListoColec() {
    if (colecSampleId && colecSelecionado) {
        const button = a('#btnListo');
        const originalText = button.innerText;
        button.innerText = 'Guardando...';
        button.disabled = true;

        try {
            const response = await enviarAjax('guardarSampleEnColec', {
                colecSampleId,
                colecSelecionado
            });

            if (response?.success) {
                alert('Sample guardado en la colección con éxito');
                cerrarColec();
            } else {
                alert(`Error al guardar en la colección: ${response?.message || 'Desconocido'}`);
            }
        } catch (error) {
            console.error('Error al guardar el sample:', error);
            alert('Ocurrió un error al guardar en la colección. Por favor, inténtelo de nuevo.');
        } finally {
            button.innerText = originalText;
            button.disabled = false;
        }
    } else {
        cerrarColec();
    }
}

//ejemplo de como se crea una nueva coleccion
async function crearNuevaColec() {
    const esValido = verificarColec();
    if (!esValido) return;

    const titulo = a('#tituloColec').value;
    const descripcion = a('#descripColec').value || '';

    const data = {
        colecSampleId,
        imgColec,
        titulo,
        imgColecId,
        descripcion
    };
    const button = a('#btnCrearColec');
    const originalText = button.innerText;
    button.innerText = 'Guardando...';
    button.disabled = true;

    try {
        const response = await enviarAjax('crearColeccion', data);
        if (response?.success) {
            alert('Colección creada con éxito');
            // Actualizar la lista de colecciones
            await actualizarListaColecciones();
            cerrarColec();
        } else {
            alert(`Error al crear la colección: ${response?.message || 'Desconocido'}`);
        }
    } catch (error) {
        // console.error('Error al enviar los datos:', error);
        alert('Ocurrió un error durante la creación de la colección. Por favor, inténtelo de nuevo.');
    } finally {
        button.innerText = originalText;
        button.disabled = false;
    }
}

async function actualizarListaColecciones() {
    try {
        const response = await enviarAjax('obtener_colecciones');
        if (response) {
            const listaColeccion = document.querySelector('.listaColeccion');
            const elementosFijos = listaColeccion.querySelectorAll('#favoritos, #despues');
            listaColeccion.innerHTML = '';
            elementosFijos.forEach(elemento => {
                listaColeccion.appendChild(elemento);
            });
        
            listaColeccion.insertAdjacentHTML('beforeend', response);
        } else {
        }
    } catch (error) {
    }
}


function subidaImagenColec() {
    const previewImagenColec = a('#previewImagenColec');
    const formRs = a('#formRs');

    const inicialSubida = event => {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (!file) return;
        if (file.size > 3 * 1024 * 1024) return alert('El archivo no puede superar los 3 MB.');
        if (!file.type.startsWith('image/')) return alert('Por favor, seleccione una imagen.');

        subidaImagen(file);
    };

    const subidaImagen = async file => {
        try {
            const { fileUrl, fileId } = await subidaRsBackend(file, 'barraProgresoImagen');
            imgColec = fileUrl;
            imgColecId = fileId;
            updatePreviewImagen(file);
        } catch {
            alert('Hubo un problema al cargar la imagen. Inténtalo de nuevo.');
        }
    };

    const updatePreviewImagen = file => {
        const reader = new FileReader();
        reader.onload = e => {
            previewImagenColec.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
            previewImagenColec.style.display = 'block';
        };
        reader.readAsDataURL(file);
    };

    previewImagenColec.addEventListener('click', () => {
        const inputFile = document.createElement('input');
        inputFile.type = 'file';
        inputFile.accept = 'image/*';
        inputFile.onchange = inicialSubida;
        inputFile.click();
    });

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        formRs.addEventListener(eventName, e => {
            e.preventDefault();
            formRs.style.backgroundColor = eventName === 'dragover' ? '#e9e9e9' : '';
            if (eventName === 'drop') inicialSubida(e);
        });
    });
}


function busquedaColec(query) {
    document.querySelectorAll('.listaColeccion .coleccion').forEach(coleccion => {
        const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
        coleccion.style.display = titulo.includes(query) ? 'flex' : 'none';
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
    colecSelecionado = coleccion.getAttribute('data-post_id') || coleccion.id;
}



function resetColec() {
    colecSampleId = null;
    colecSelecionado = null;
    a.quitar('.coleccion', 'seleccion');
    const existeSpans = document.querySelectorAll('.ya-existe');
    existeSpans.forEach(span => span.remove());
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
