window.initMoverTarea = () => {
    const tit = document.getElementById('tituloTarea');
    if (tit) moverTarea();
};

function manejarSeleccionTarea(ev) {
    const tarea = ev.target.closest('.draggable-element');
    if (!tarea) return;

    const id = tarea.getAttribute('id-post');

    if (ev.ctrlKey) {
        if (tareasSeleccionadas.includes(id)) {
            tareasSeleccionadas = tareasSeleccionadas.filter(selId => selId !== id);
            tarea.classList.remove('seleccionado');
        } else {
            tareasSeleccionadas.push(id);
            tarea.classList.add('seleccionado');
        }
    }
}

function deseleccionarTareas() {
    tareasSeleccionadas.forEach(id => {
        const tarea = document.querySelector(`.draggable-element[id-post="${id}"]`);
        if (tarea) tarea.classList.remove('seleccionado');
    });
    tareasSeleccionadas = [];
}

function moverTarea() {
    listaMov = document.querySelector('.clase-tarea');
    if (!listaMov || listaMov.listenersAdded) return;

    listaMov.listenersAdded = true;

    const iniciarArrastre = ev => {
        if (inicializarVars(ev)) {
            listaMov.addEventListener('mousemove', manejarMov);
            listaMov.addEventListener('mouseup', finalizarArrastre);
        }
    };

    listaMov.addEventListener('mousedown', ev => {
        if (ev.target.closest('.draggable-element')) {
            if (!ev.ctrlKey) deseleccionarTareas();
            iniciarArrastre(ev);
        } else {
            deseleccionarTareas();
        }
    });

    listaMov.addEventListener('click', manejarSeleccionTarea);
    listaMov.addEventListener('dragstart', ev => ev.preventDefault());
    document.addEventListener('click', ev => {
        if (!listaMov.contains(ev.target)) deseleccionarTareas();
    });
}

let listaMov,
    arrastrandoElem,
    ordenViejo,
    idTarea,
    posInicialY,
    movRealizado,
    subtareasArrastradas,
    esSubtarea,
    tareasSeleccionadas = [];
const tolerancia = 10;

function inicializarVars(ev) {
    arrastrandoElem = ev.target.closest('.draggable-element');
    if (!arrastrandoElem) return false;

    esSubtarea = arrastrandoElem.getAttribute('subtarea') === 'true';
    idTarea = arrastrandoElem.getAttribute('id-post');
    ordenViejo = Array.from(listaMov.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
    posInicialY = ev.clientY;
    movRealizado = false;
    subtareasArrastradas = !esSubtarea ? Array.from(listaMov.querySelectorAll(`.draggable-element[padre="${idTarea}"]`)) : [];

    arrastrandoElem.classList.add('dragging');
    subtareasArrastradas.forEach(subtarea => subtarea.classList.add('dragging'));
    document.body.classList.add('dragging-active');
    return true;
}

function manejarMov(ev) {
    if (!arrastrandoElem) return;
    ev.preventDefault();

    const mouseY = ev.clientY;
    const rectLista = listaMov.getBoundingClientRect();

    if (!movRealizado && Math.abs(mouseY - posInicialY) > tolerancia) {
        movRealizado = true;
    }

    if (mouseY < rectLista.top || mouseY > rectLista.bottom) return;

    const elemsVisibles = Array.from(listaMov.children).filter(child => child.style.display !== 'none' && child !== arrastrandoElem && !subtareasArrastradas.includes(child));
    let insertado = false;

    for (let i = 0; i < elemsVisibles.length; i++) {
        const elem = elemsVisibles[i];
        const rectElem = elem.getBoundingClientRect();
        const elemMedio = rectElem.top + rectElem.height / 2;

        if (mouseY < elemMedio) {
            if (!esSubtarea || (esSubtarea && (elem.getAttribute('subtarea') !== 'true' || elem.getAttribute('padre') === arrastrandoElem.getAttribute('padre')))) {
                listaMov.insertBefore(arrastrandoElem, elem);
                if (!esSubtarea) {
                    let ultimoElem = arrastrandoElem;
                    subtareasArrastradas.forEach(subtarea => {
                        listaMov.insertBefore(subtarea, ultimoElem.nextSibling);
                        ultimoElem = subtarea;
                    });
                }
                insertado = true;
            }
            break;
        }
    }

    if (!insertado && elemsVisibles.length > 0) {
        if (!esSubtarea || (esSubtarea && (elemsVisibles[elemsVisibles.length - 1].getAttribute('subtarea') !== 'true' || elemsVisibles[elemsVisibles.length - 1].getAttribute('padre') === arrastrandoElem.getAttribute('padre')))) {
            listaMov.appendChild(arrastrandoElem);
            if (!esSubtarea) {
                let ultimoElem = arrastrandoElem;
                subtareasArrastradas.forEach(subtarea => {
                    listaMov.insertBefore(subtarea, ultimoElem.nextSibling);
                    ultimoElem = subtarea;
                });
            }
        }
    }
}

function obtenerSesionYData() {
    let sesionArriba = null;
    let dataArriba = null;
    let anterior = arrastrandoElem.previousElementSibling;

    while (anterior) {
        if (anterior.classList.contains('POST-tarea')) {
            sesionArriba = anterior.getAttribute('sesion');
            dataArriba = anterior.getAttribute('data-seccion');
        } else if (anterior.classList.contains('divisorTarea')) {
            sesionArriba = sesionArriba || anterior.getAttribute('data-valor');
            dataArriba = dataArriba || anterior.getAttribute('data-valor');
        }

        if (sesionArriba !== null && dataArriba !== null) break;
        anterior = anterior.previousElementSibling;
    }

    return {sesionArriba, dataArriba};
}

function esSubtareaNueva() {
    let esSubtareaNueva = false;
    let siguiente = arrastrandoElem.nextElementSibling;

    if (siguiente) {
        const siguienteEsSubtarea = siguiente.getAttribute('subtarea') === 'true';
        const siguienteEsPadre = siguiente.getAttribute('id-post') === arrastrandoElem.getAttribute('padre');
        const siguienteEsPadreDeActual = siguiente.getAttribute('id-post') === arrastrandoElem.getAttribute('id-post');
        const actualEsSubtareaDeSiguiente = arrastrandoElem.getAttribute('padre') === siguiente.getAttribute('id-post');
        esSubtareaNueva = (siguienteEsSubtarea || siguienteEsPadre) && !siguienteEsPadreDeActual && !actualEsSubtareaDeSiguiente;
    }

    return esSubtareaNueva;
}

function cambioASubtarea() {
    const nuevaEsSubtarea = esSubtareaNueva();
    const cambioSubtarea = nuevaEsSubtarea !== esSubtarea;

    if (cambioSubtarea) {
        window.reiniciarPost(idTarea, 'tarea');
    }

    return {nuevaEsSubtarea};
}

function finalizarArrastre() {
    if (!arrastrandoElem) return;

    const ordenNuevo = Array.from(listaMov.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
    const nuevaPos = ordenNuevo.indexOf(idTarea);
    const {sesionArriba, dataArriba} = obtenerSesionYData();

    if (movRealizado) {
        const {nuevaEsSubtarea} = cambioASubtarea();
        let idsActualizadas = [];
        let padre = '';

        if (nuevaEsSubtarea) {
            const tareaPadre = arrastrandoElem.nextElementSibling;
            padre = tareaPadre ? tareaPadre.getAttribute('id-post') : '';

            if (padre) {
                arrastrandoElem.setAttribute('padre', padre);
                arrastrandoElem.setAttribute('subtarea', 'true');
            } else {
                padre = '';
            }

            idsActualizadas = [idTarea];
            arrastrandoElem.setAttribute('data-seccion', dataArriba);
            arrastrandoElem.setAttribute('sesion', sesionArriba);
        } else {
            padre = '';
            arrastrandoElem.removeAttribute('padre');
            arrastrandoElem.setAttribute('subtarea', 'false');

            idsActualizadas = [idTarea, ...subtareasArrastradas.map(subtarea => subtarea.getAttribute('id-post'))];
            arrastrandoElem.setAttribute('data-seccion', dataArriba);
            subtareasArrastradas.forEach(subtarea => subtarea.setAttribute('data-seccion', dataArriba));
            arrastrandoElem.setAttribute('sesion', sesionArriba);
            subtareasArrastradas.forEach(subtarea => subtarea.setAttribute('sesion', sesionArriba));
        }

        guardarOrdenTareas({
            idTarea,
            nuevaPos,
            ordenNuevo,
            sesionArriba,
            dataArriba,
            subtarea: nuevaEsSubtarea,
            padre
        });
    }

    arrastrandoElem.classList.remove('dragging');
    subtareasArrastradas.forEach(subtarea => subtarea.classList.remove('dragging'));
    document.body.classList.remove('dragging-active');
    listaMov.removeEventListener('mousemove', manejarMov);
    listaMov.removeEventListener('mouseup', finalizarArrastre);

    arrastrandoElem = null;
    idTarea = null;
    ordenViejo = [];
    posInicialY = null;
    movRealizado = false;
    subtareasArrastradas = [];
    esSubtarea = false;
}

function guardarOrdenTareas({idTarea, nuevaPos, ordenNuevo, sesionArriba, dataArriba, subtarea, padre}) {
    let data = {
        tareaMovida: idTarea,
        nuevaPos,
        ordenNuevo,
        sesionArriba,
        dataArriba,
        subtarea,
        padre: subtarea ? padre : null
    };
    enviarAjax('actualizarOrdenTareas', data)
        .then(res => {
            if (res && res.success) {
                if (subtarea) {
                    window.reiniciarPost(idTarea, 'tarea');
                } else {
                    window.reiniciarPost(idTarea, 'tarea');
                }
            } else {
                console.error('Hubo un error en la respuesta del servidor:', res);
            }
        })
        .catch(err => {
            console.error('Error en la petición AJAX:', err);
        });
}