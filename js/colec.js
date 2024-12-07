let colecSampleId = null;
let colecSelecionado = null;
let colecIniciado = false;
let imgColec = null;
let imgColecId = null;
let colecABorrar = null;

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
            e.stopPropagation(); // Añade esta línea
            colecSampleId = btn.getAttribute('data-post_id');
            abrirColec();
        }
    });

    document.body.addEventListener('click', e => {
        const btnEliminar = e.target.closest('.borrarColec');
        if (btnEliminar) {
            e.preventDefault();
            colecABorrar = btnEliminar.getAttribute('data-post_id');
            borrarColec();
        }
    });

    document.addEventListener('click', e => {
        const coleccion = e.target.closest('.coleccion');
        if (coleccion && coleccion.closest('.listaColeccion')) {
            manejarClickColec(coleccion);
        }
    });

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

async function borrarColec() {
    await window.accionClick(
        '.borrarColec',
        'borrarColec',
        '¿Estas seguro de borrar la colección? No podras recuperarla despues :O',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('Colección eliminada.');
            await actualizarListaColecciones();
        },
        '.EDYQHV'
    );
}

//Cuando se da click, el boton no se desactiva y si doy varios click se crea varias colecciones, especialmente para el caso de busqueda vacía
async function manejarClickListoColec() {
    //console.log('Función manejarClickListoColec iniciada');

    if (colecSampleId && colecSelecionado) {
        //console.log('colecSampleId y colecSelecionado existen:', colecSampleId, colecSelecionado);

        const button = a('#btnListo');
        const originalText = button.innerText;

        // Desactivar el botón para evitar múltiples clics
        button.innerText = 'Guardando...';
        button.disabled = true;

        try {
            //console.log('Enviando petición AJAX para guardar sample en colección');
            const response = await enviarAjax('guardarSampleEnColec', {
                colecSampleId,
                colecSelecionado
            });
            //console.log('Respuesta recibida:', response);

            if (response?.success) {
                alert('Sample guardado en la colección con éxito');
                cerrarColec(); // Cerrar modal o interfaz de colecciones
            } else {
                alert(`Error al guardar en la colección: ${response?.message || 'Desconocido'}`);
            }
        } catch (error) {
            console.error('Error al guardar el sample:', error);
            alert('Ocurrió un error al guardar en la colección. Por favor, inténtelo de nuevo.');
        } finally {
            // Reactivar el botón después de que la operación haya terminado
            button.innerText = originalText;
            button.disabled = false;
        }
    } else {
        //console.log('colecSampleId o colecSelecionado faltan:', colecSampleId, colecSelecionado);
        cerrarColec(); // Cerrar modal si no hay selección válida
    }
}

function busquedaColec(query) {
    const button = a('#btnListo');
    let hayResultados = false;

    document.querySelectorAll('.listaColeccion .coleccion').forEach(coleccion => {
        const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
        const visible = titulo.includes(query.toLowerCase());
        coleccion.style.display = visible ? 'flex' : 'none';

        if (visible) hayResultados = true; // Verificar si hay al menos un resultado visible
    });

    // Configuración dinámica del texto y acción del botón
    if (!hayResultados && query) {
        button.innerText = 'Crear Colección';
        button.onclick = () => crearNuevaColecConTitulo(query);
    } else {
        button.innerText = colecSelecionado ? 'Guardar' : 'Listo';
        button.onclick = colecSelecionado ? manejarClickListoColec : null;
    }
}

function manejarClickColec(coleccion) {
    const button = a('#btnListo');

    if (coleccion.classList.contains('seleccion')) {
        coleccion.classList.remove('seleccion');
        colecSelecionado = null;
        button.innerText = 'Listo';
        button.onclick = null;
    } else {
        a.quitar('.coleccion', 'seleccion');
        coleccion.classList.add('seleccion');
        colecSelecionado = coleccion.getAttribute('data-post_id') || coleccion.id;
        button.innerText = 'Guardar';
        button.onclick = manejarClickListoColec;
    }
}

async function crearNuevaColecConTitulo(titulo) {
    const button = a('#btnListo');
    if (button.disabled) return; // Previene múltiples clics

    button.disabled = true;
    a('#tituloColec').value = titulo;
    button.innerText = 'Creando nueva colección...';

    await crearNuevaColec(); // Espera a que la creación termine
    button.disabled = false; // Rehabilita el botón después de finalizar
}

// Funcion para crear colec
async function crearNuevaColec() {
    const esValido = verificarColec();
    if (!esValido) return;
    const button = a('#btnCrearColec');
    const originalText = button.innerText;
    button.innerText = 'Guardando...';
    button.disabled = true;

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
    //console.log('Datos enviados:', data); // Log de la data que se envía

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

// Función para abrir el modal "Colec"
async function abrirColec() {
    if (!colecSampleId) {
        console.warn('colecSampleId no está definido');
        return; // Evitar abrir el modal sin un ID válido
    }

    const modal = document.querySelector('.modalColec');
    if (!modal) {
        console.error('Modal no encontrado');
        return;
    }

    mostrar(modal);
    createSubmenuDarkBackground();
    document.body.classList.add('no-scroll');

    await verificarSampleEnColecciones();
}

// Función para cerrar el modal "Colec"
window.cerrarColec = function () {
    const modal = document.querySelector('.modalColec');
    if (!modal) {
        console.error('Modal no encontrado');
        return;
    }
    ocultar(modal); // Oculta el modal
    ocultar(document.querySelector('.modalCrearColec')); // Oculta el modal de creación

    removeSubmenuDarkBackground(); // Elimina el fondo oscuro
    document.body.classList.remove('no-scroll'); // Permite el scroll
    resetColec(); // Resetea cualquier estado relacionado con el modal
};

// Mostrar un elemento (con animación opcional)
window.mostrar = function (element) {
    if (!element || !(element instanceof Element)) {
        console.error('No se proporcionó un elemento válido o el elemento no es de tipo Element');
        return;
    }

    // Cambiar el estilo CSS para mostrar el elemento
    element.style.display = element._previousDisplay || 'block';
    element.style.opacity = '1';
    element.style.transition = 'opacity 0.3s ease';
};

// Ocultar un elemento (con animación opcional)
window.ocultar = function (element) {
    if (element && getComputedStyle(element).display !== 'none') {
        element.style.opacity = '0';
        element.style.transition = 'opacity 0.3s ease';

        // Esperar a que termine la transición antes de ocultar completamente
        setTimeout(() => {
            element.style.display = 'none';
        }, 300);
    }
};

// Crear el fondo oscuro
window.createSubmenuDarkBackground = function () {
    let darkBackground = document.getElementById('submenu-background5322');
    if (!darkBackground) {
        // Crear el fondo oscuro si no existe
        darkBackground = document.createElement('div');
        darkBackground.id = 'submenu-background5322';
        darkBackground.style.position = 'fixed';
        darkBackground.style.top = 0;
        darkBackground.style.left = 0;
        darkBackground.style.width = '100%';
        darkBackground.style.height = '100%';
        darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        darkBackground.style.zIndex = 1000;
        darkBackground.style.display = 'none';
        darkBackground.style.pointerEvents = 'none';
        document.body.appendChild(darkBackground);

        // Agregar evento para cerrar submenús al hacer clic en el fondo oscuro
        darkBackground.addEventListener('click', () => {
            document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
                hideSubmenu(submenu);
            });
            cerrarColec();
        });
    }

    darkBackground.style.display = 'block';
    darkBackground.style.pointerEvents = 'auto';
};

// Eliminar el fondo oscuro
window.removeSubmenuDarkBackground = function () {
    const darkBackground = document.getElementById('submenu-background5322');
    if (darkBackground) {
        darkBackground.style.opacity = '0';
        darkBackground.style.transition = 'opacity 0.3s ease';

        // Esperar a que termine la transición antes de ocultar completamente
        setTimeout(() => {
            darkBackground.style.display = 'none';
            darkBackground.style.pointerEvents = 'none';
        }, 300);
    }
};

async function verificarSampleEnColecciones() {
    //console.log('Función verificarSampleEnColecciones iniciada');
    try {
        //console.log('Enviando petición AJAX para verificar sample en colecciones con ID:', colecSampleId);
        const response = await enviarAjax('verificar_sample_en_colecciones', {
            sample_id: colecSampleId
        });
        //console.log('Respuesta recibida de verificarSampleEnColecciones:', response);

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
                        existeSpan.addEventListener('mouseenter', function () {
                            this.textContent = 'Eliminar';
                        });

                        existeSpan.addEventListener('mouseleave', function () {
                            this.textContent = 'Guardado aquí';
                        });

                        // Evento para manejar el clic en "Eliminar"
                        existeSpan.addEventListener('click', async function () {
                            const confirmacion = await confirm('¿Seguro que deseas eliminar este sample de la colección?');
                            if (confirmacion) {
                                try {
                                    //console.log('Enviando petición AJAX para eliminar el sample de la colección con ID:', coleccionId);
                                    const eliminarResponse = await enviarAjax('eliminarSampledeColec', {
                                        sample_id: colecSampleId,
                                        coleccion_id: coleccionId
                                    });

                                    if (eliminarResponse.success) {
                                        //console.log('Sample eliminado correctamente de la colección con ID:', coleccionId);
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
                        //console.log('Etiqueta "Guardado aquí" añadida a la colección con ID:', coleccionId);
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

async function abrirModalCrearColec() {
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
        console.log('Iniciando la actualización de la lista de colecciones...');
        const response = await enviarAjax('obtenerListaColec');
        console.log('Respuesta recibida del servidor:', response);

        if (response && response.success && response.data) {
            // Verifica que response sea un objeto con success y data
            const listaColeccion = document.querySelector('.listaColeccion');
            console.log('Elemento .listaColeccion encontrado:', listaColeccion);

            const elementosFijos = listaColeccion.querySelectorAll('#favoritos, #despues');
            console.log('Elementos fijos identificados:', elementosFijos);

            listaColeccion.innerHTML = '';
            console.log('Contenido de .listaColeccion limpiado.');

            elementosFijos.forEach(elemento => {
                listaColeccion.appendChild(elemento);
                console.log('Elemento fijo añadido nuevamente:', elemento);
            });

            listaColeccion.insertAdjacentHTML('beforeend', response.data); // Usa response.data que contiene el HTML
            console.log('Nuevos elementos añadidos a la lista desde la respuesta.');
        } else {
            if (response && response.data === '') {
                console.warn('No se encontraron colecciones.'); // Mensaje específico si no hay colecciones
            } else {
                console.warn('La respuesta del servidor está vacía, no válida o no tiene datos.'); // Manejo general de errores
            }
        }
    } catch (error) {
        console.error('Error al actualizar la lista de colecciones:', error);
    }
}
function subidaImagenColec() {
    const previewImagenColec = a('#previewImagenColec');
    const modalCrearColec = a('#modalCrearColec');

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
        modalCrearColec.addEventListener(eventName, e => {
            e.preventDefault();
            modalCrearColec.style.backgroundColor = eventName === 'dragover' ? '#e9e9e9' : '';
            if (eventName === 'drop') inicialSubida(e);
        });
    });
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
