window.initMoverTarea = function () {
    const tit = document.getElementById('tituloTarea');
    if (tit) {
        moverTarea();
    }
};

//aqui hay un pequeño bug, cuando muevo una subtarea padre, esta se vuelve subtarea de la hija que esta abajo, las taraeas se vuelven subtareas si tienen un subtarea debajo pero eso no debería pasar si la tarea padre se mueve porque obviamente tendra una subtarea debajo que es su hija
let listaMov = null;
let arrastrandoElem = null;
let ordenViejo = [];
let idTarea = null;
let posInicialY = null;
const tolerancia = 10;
let movRealizado = false;
let subtareasArrastradas = [];
let esSubtarea = false;

function inicializarVars(ev) {
    let log = '';
    arrastrandoElem = ev.target.closest('.draggable-element');
    if (!arrastrandoElem) {
        log += '\n  No se encontró elemento arrastrable.';
        return false;
    }

    esSubtarea = arrastrandoElem.getAttribute('subtarea') === 'true';
    idTarea = arrastrandoElem.getAttribute('id-post');
    ordenViejo = Array.from(listaMov.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
    posInicialY = ev.clientY;
    movRealizado = false;
    subtareasArrastradas = !esSubtarea ? Array.from(listaMov.querySelectorAll(`.draggable-element[padre="${idTarea}"]`)) : [];

    arrastrandoElem.classList.add('dragging');
    subtareasArrastradas.forEach(subtarea => subtarea.classList.add('dragging'));
    document.body.classList.add('dragging-active');

    log += `\n  Iniciando arrastre de tarea ${idTarea}. Es subtarea: ${esSubtarea}.`;
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
            
            if (!esSubtarea || (esSubtarea && elem.getAttribute('subtarea') === 'true' ) ) {
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
        if (!esSubtarea || (esSubtarea && elemsVisibles[elemsVisibles.length-1].getAttribute('subtarea') === 'true' ) ) {
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
    let log = '';
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

        if (sesionArriba !== null && dataArriba !== null) {
            log += `\n  Sesión y data encontrados: ${sesionArriba}, ${dataArriba}`;
            break;
        }

        anterior = anterior.previousElementSibling;
    }

    if (sesionArriba === null || dataArriba === null) {
        log += `\n  No se encontraron sesión o data arriba.`;
    }

    return {sesionArriba, dataArriba, log};
}

function esSubtareaNueva() {
    let esSubtareaNueva = false;
    let anterior = arrastrandoElem.previousElementSibling;
    
    if (anterior) {
        const anteriorEsSubtarea = anterior.getAttribute('subtarea') === 'true';
        const anteriorEsPadre = anterior.getAttribute('id-post') === arrastrandoElem.getAttribute('padre');
        const anteriorEsPadreDeActual = anterior.getAttribute('id-post') === arrastrandoElem.getAttribute('id-post');
        
        esSubtareaNueva = (anteriorEsSubtarea || anteriorEsPadre) && !anteriorEsPadreDeActual;
    }

    return esSubtareaNueva;
}

function cambioASubtarea() {
    let log = '';
    const nuevaEsSubtarea = esSubtareaNueva();
    const cambioSubtarea = nuevaEsSubtarea !== esSubtarea;
    
    if (cambioSubtarea) {
        log += `\n  La tarea ${idTarea} cambió su estado de subtarea a ${nuevaEsSubtarea}.`;
        window.reiniciarPost(idTarea, 'tarea');
    }

    return {nuevaEsSubtarea, log};
}

function finalizarArrastre() {
    if (!arrastrandoElem) return;
    let log = '';

    const ordenNuevo = Array.from(listaMov.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
    const nuevaPos = ordenNuevo.indexOf(idTarea);
    const res = obtenerSesionYData();
    const sesionArriba = res.sesionArriba
    const dataArriba = res.dataArriba;
    log += res.log;

    if (movRealizado) {
        const res = cambioASubtarea();
        const nuevaEsSubtarea = res.nuevaEsSubtarea;
        log += res.log;
        let idsActualizadas = [];
        let padre = '';
        
        if (nuevaEsSubtarea) {
            const tareaPadre = arrastrandoElem.previousElementSibling;
            padre = tareaPadre ? tareaPadre.getAttribute('id-post') : '';

            if (padre) {
                arrastrandoElem.setAttribute('padre', padre);
                arrastrandoElem.setAttribute('subtarea', 'true');
            } else {
                padre = '';
            }

            idsActualizadas = [idTarea];
            log += `\n  La tarea ${idTarea} se convirtió en una subtarea con padre ${padre}.`;
            arrastrandoElem.setAttribute('data-seccion', dataArriba);
            arrastrandoElem.setAttribute('sesion', sesionArriba);
        } else {
            padre = '';
            
            arrastrandoElem.removeAttribute('padre');
            arrastrandoElem.setAttribute('subtarea', 'false');
            
            idsActualizadas = [idTarea, ...subtareasArrastradas.map(subtarea => subtarea.getAttribute('id-post'))];
            log += `\n  La tarea ${idTarea} y sus subtareas se movieron a la posición ${nuevaPos} dentro de la misma sección.`;
            arrastrandoElem.setAttribute('data-seccion', dataArriba);
            subtareasArrastradas.forEach(subtarea => subtarea.setAttribute('data-seccion', dataArriba));
            arrastrandoElem.setAttribute('sesion', sesionArriba);
            subtareasArrastradas.forEach(subtarea => subtarea.setAttribute('sesion', sesionArriba));
        }

        guardarOrdenTareas({
            idTarea: idTarea,
            nuevaPos: nuevaPos,
            ordenNuevo,
            sesionArriba,
            dataArriba,
            subtarea: nuevaEsSubtarea,
            padre: padre,
            log: log
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
        if (ev.target.closest('.draggable-element')) iniciarArrastre(ev);
    });

    listaMov.addEventListener('dragstart', ev => ev.preventDefault());
}


function guardarOrdenTareas({ idTarea, nuevaPos, ordenNuevo, sesionArriba, dataArriba, subtarea, padre }) {
    let log = `guardarOrdenTareas: Inicia el proceso para tarea ${idTarea}.`;
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
                log += `\n  Orden de tarea actualizado correctamente.`;
                if (subtarea) {
                    log += `\n  La tarea ${idTarea} se convirtió en una subtarea con padre ${padre}.`;
                    // Reiniciar solo si es una subtarea
                    window.reiniciarPost(idTarea, 'tarea');
                } else {

                    log += `\n  La tarea ${idTarea} dejó de ser una subtarea.`;
                    window.reiniciarPost(idTarea, 'tarea');
                }
                console.log(log);
            } else {
                log += `\n  Error al actualizar el orden: ${res ? res.data : 'Respuesta vacía o success: false'}`;
                console.error(log);
            }
        })
        .catch(err => {
            log += `\n  Error en la petición AJAX: ${err}`;
            console.error(log);
        });
}
