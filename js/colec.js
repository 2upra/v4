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
    subidaImagenColec();
    document.addEventListener('modalOpened', () => {
        resetColec();
    });
}

/*
Cuando se crea una coleccion, se envia el colecSampleId pero si se crea desde el buscador, no se envia, en que momento se quita si se establece desde que se da click a .botonColeccionBtn
una informacion adicional es que cerrar colec 
si desde que se abre el modal se establece
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

Datos enviados: {colecSampleId: '266705', imgColec: null, titulo: 'test 1', imgColecId: null, descripcion: '', …}colecSampleId: "266705"descripcion: ""imgColec: nullimgColecId: nullprivado: 0titulo: "test 1"[[Prototype]]: Object
colec.js?ver=1.0.1.1987316714:143 Función abrirColec iniciada
colec.js?ver=1.0.1.1987316714:148 Modal mostrado y fondo creado
colec.js?ver=1.0.1.1987316714:194 Función verificarSampleEnColecciones iniciada
colec.js?ver=1.0.1.1987316714:196 Enviando petición AJAX para verificar sample en colecciones con ID: 266705
colec.js?ver=1.0.1.1987316714:200 Respuesta recibida de verificarSampleEnColecciones: {success: true, data: {…}}data: {colecciones: Array(1)}success: true[[Prototype]]: Object
colec.js?ver=1.0.1.1987316714:248 Etiqueta "Guardado aquí" añadida a la colección con ID: 273488
colec.js?ver=1.0.1.1987316714:150 verificarSampleEnColecciones completado
colec.js?ver=1.0.1.1987316714:154 Función manejarClickListoColec iniciada
colec.js?ver=1.0.1.1987316714:185 colecSampleId o colecSelecionado faltan: 266705 null
colec.js?ver=1.0.1.1987316714:118 Datos enviados: {colecSampleId: null, imgColec: null, titulo: 'test 2', imgColecId: null, descripcion: '', …}
*/

function busquedaColec(query) {
    const button = a('#btnListo');
    let hayResultados = false;

    document.querySelectorAll('.listaColeccion .coleccion').forEach(coleccion => {
        const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
        const visible = titulo.includes(query);
        coleccion.style.display = visible ? 'flex' : 'none';

        if (visible) hayResultados = true; // Verificar si hay al menos un resultado visible
    });

    if (!hayResultados && query) {
        button.innerText = 'Crear Colección';
        button.onclick = () => {
            crearNuevaColecConTitulo(query);
        };
    } else {
        button.innerText = colecSelecionado ? 'Guardar' : 'Listo';
        button.onclick = colecSelecionado ? manejarClickListoColec : null;
    }
}

function manejarClickColec(coleccion) {
    const button = a('#btnListo');

    if (coleccion.classList.contains('seleccion')) {
        // Si ya está seleccionado, lo deseleccionamos
        coleccion.classList.remove('seleccion');
        colecSelecionado = null;
        button.innerText = 'Listo';
        button.onclick = null; // Restablecer a su funcionalidad original
    } else {
        // Si no está seleccionado, lo seleccionamos
        a.quitar('.coleccion', 'seleccion');
        coleccion.classList.add('seleccion');
        colecSelecionado = coleccion.getAttribute('data-post_id') || coleccion.id;
        button.innerText = 'Guardar';
        button.onclick = manejarClickListoColec;
    }
}

// Función auxiliar para crear una nueva colección con el título de búsqueda
function crearNuevaColecConTitulo(titulo) {
    // Aquí establecemos el título directamente para la creación
    a('#tituloColec').value = titulo;
    crearNuevaColec();
}

async function crearNuevaColec() {
    const esValido = verificarColec();
    if (!esValido) return;

    const titulo = a('#tituloColec').value;
    const descripcion = a('#descripColec').value || '';
    const privadoCheck = a('#privadoColec');
    const privado = privadoCheck.checked ? privadoCheck.value : 0;

    const data = {
        colecSampleId,
        imgColec,
        titulo,
        imgColecId,
        descripcion,
        privado
    };
    console.log('Datos enviados:', data); // Log de la data que se envía

    const button = a('#btnCrearColec');
    const originalText = button.innerText;
    button.innerText = 'Guardando...';
    button.disabled = true;

    try {
        const response = await enviarAjax('crearColeccion', data);
        if (response?.success) {
            alert('Colección creada con éxito');
            await actualizarListaColecciones();
            cerrarColec();
        } else {
            alert(`Error al crear la colección: ${response?.message || 'Desconocido'}`);
        }
    } catch (error) {
        alert('Ocurrió un error durante la creación de la colección. Por favor, inténtelo de nuevo.');
    } finally {
        button.innerText = originalText;
        button.disabled = false;
    }
}

async function abrirColec() {
    console.log('Función abrirColec iniciada');
    if (!colecSampleId) {
        console.warn('colecSampleId no está definido');
        return; // Evitar abrir el modal sin un ID válido
    }
    const modal = a('.modalColec');
    mostrar(modal);
    crearBackgroundColec();
    a.gregar('body', 'no-scroll');
    console.log('Modal mostrado y fondo creado');
    await verificarSampleEnColecciones();
    console.log('verificarSampleEnColecciones completado');
}


async function manejarClickListoColec() {
    console.log('Función manejarClickListoColec iniciada');
    if (colecSampleId && colecSelecionado) {
        console.log('colecSampleId y colecSelecionado existen:', colecSampleId, colecSelecionado);

        const button = a('#btnListo');
        const originalText = button.innerText;
        button.innerText = 'Guardando...';
        button.disabled = true;

        try {
            console.log('Enviando petición AJAX para guardar sample en colección');
            const response = await enviarAjax('guardarSampleEnColec', {
                colecSampleId,
                colecSelecionado
            });
            console.log('Respuesta recibida:', response);

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
        console.log('colecSampleId o colecSelecionado faltan:', colecSampleId, colecSelecionado);
        cerrarColec();
    }
}




async function verificarSampleEnColecciones() {
    console.log('Función verificarSampleEnColecciones iniciada');
    try {
        console.log('Enviando petición AJAX para verificar sample en colecciones con ID:', colecSampleId);
        const response = await enviarAjax('verificar_sample_en_colecciones', {
            sample_id: colecSampleId
        });
        console.log('Respuesta recibida de verificarSampleEnColecciones:', response);

        if (response.success) {
            const colecciones = document.querySelectorAll('.coleccion');
            colecciones.forEach(coleccion => {
                const coleccionId = coleccion.getAttribute('data-post_id');

                if (coleccionId && response.data.colecciones.includes(parseInt(coleccionId))) {
                    // Verificar si ya existe la etiqueta para no duplicarla
                    if (!coleccion.querySelector('.ya-existe')) {
                        const existeSpan = document.createElement('span');
                        existeSpan.className = 'ya-existe';
                        existeSpan.textContent = 'Guardado aquí';

                        // Evento para cambiar el contenido al hacer hover
                        existeSpan.addEventListener('mouseenter', function() {
                            this.textContent = 'Eliminar';
                        });

                        existeSpan.addEventListener('mouseleave', function() {
                            this.textContent = 'Guardado aquí';
                        });

                        // Evento para manejar el clic en "Eliminar"
                        existeSpan.addEventListener('click', async function() {
                            const confirmacion = await confirm('¿Seguro que deseas eliminar este sample de la colección?');
                            if (confirmacion) {
                                try {
                                    console.log('Enviando petición AJAX para eliminar el sample de la colección con ID:', coleccionId);
                                    const eliminarResponse = await enviarAjax('eliminarSampledeColec', {
                                        sample_id: colecSampleId,
                                        coleccion_id: coleccionId
                                    });

                                    if (eliminarResponse.success) {
                                        console.log('Sample eliminado correctamente de la colección con ID:', coleccionId);
                                        // Eliminar el span de la colección
                                        existeSpan.remove();
                                    } else {
                                        console.error('Error al eliminar el sample:', eliminarResponse.message);
                                    }
                                } catch (error) {
                                    console.error('Error al enviar la petición para eliminar el sample:', error);
                                }
                            }
                        });

                        coleccion.appendChild(existeSpan);
                        console.log('Etiqueta "Guardado aquí" añadida a la colección con ID:', coleccionId);
                    }
                } else if (!coleccionId) {
                    console.warn('Elemento sin data-post_id encontrado y omitido:', coleccion);
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
    } catch (error) {}
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
            const {fileUrl, fileId} = await subidaRsBackend(file, 'barraProgresoImagen');
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


function cerrarColec() {
    ocultar(a('.modalColec'));
    ocultar(a('.modalCrearColec'));
    quitBackground();
    a.quitar('body', 'no-scroll');
    resetColec();
}

function resetColec() {
    colecSampleId = null;
    colecSelecionado = null;
    a.quitar('.coleccion', 'seleccion');
    const existeSpans = document.querySelectorAll('.ya-existe');
    existeSpans.forEach(span => span.remove());
    const button = a('#btnListo');
    button.innerText = 'Listo';
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
