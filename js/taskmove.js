//taskmove.js

window.initMoverTarea = () => {
    const tit = document.getElementById('tituloTarea');
    if (tit) moverTarea();
};

function manejarSeleccionTarea(ev) {
    const tareaElem = ev.target.closest('.draggable-element');
    if (!tareaElem) return;

    // Si el clic fue en un control DENTRO de la tarea (ej: icono de prioridad, archivar, completar)
    // y NO se usó Ctrl, NO queremos modificar la selección actual.
    // La acción del control específico se encargará, y podría necesitar la selección múltiple.
    if (!ev.ctrlKey && ev.target.closest('.divImportancia, .divArchivado, .completaTarea, .divFrecuencia')) {
        //Añade aquí otros selectores de controles internos si los tienes
        return; // No modificar la selección, dejar que el control específico actúe.
    }

    const id = tareaElem.getAttribute('id-post');

    if (ev.ctrlKey) {
        // Lógica de selección/deselección con Ctrl (sin cambios)
        if (tareasSeleccionadas.includes(id)) {
            tareasSeleccionadas = tareasSeleccionadas.filter(selId => selId !== id);
            tareaElem.classList.remove('seleccionado');
        } else {
            tareasSeleccionadas.push(id);
            tareaElem.classList.add('seleccionado');
        }
    } else {
        // Clic simple (sin Ctrl) directamente en una tarea (no en un control específico dentro de ella)
        // Deseleccionar otras y seleccionar solo esta.
        if (!tareasSeleccionadas.includes(id) || tareasSeleccionadas.length > 1) {
            deseleccionarTareas(); // Limpia selecciones previas
            tareasSeleccionadas.push(id); // Selecciona la actual
            tareaElem.classList.add('seleccionado');
        }
        // Si se hace clic en una tarea que ya es la única seleccionada, no hace nada.
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

    // La función 'inicializarVars' y otras relacionadas con el arrastre no cambian para esta solución.

    listaMov.addEventListener('mousedown', ev => {
        // ****** INICIO DE LA MODIFICACIÓN CLAVE ******
        // Verificar si el mousedown ocurrió dentro de un menú de opciones.
        const esEnMenuOpciones = ev.target.closest('.opcionesPrioridad, .opcionesFrecuencia');

        if (esEnMenuOpciones) {
            // Si el mousedown es en un menú, no hacer nada aquí.
            // Específicamente, NO deseleccionar y NO intentar iniciar un arrastre.
            // La interacción la manejará el listener de 'click' del propio menú.
            return;
        }
        // ****** FIN DE LA MODIFICACIÓN CLAVE ******

        const elem = ev.target.closest('.draggable-element');
        if (elem) {
            // Si el mousedown fue en un control DENTRO de la tarea que tiene su propia acción
            // (como el icono de prioridad, archivar, etc.), no queremos iniciar un arrastre.
            // Dejamos que el evento 'click' en ese control se maneje.
            // La función 'inicializarVars' se encargará de esto también.
            if (inicializarVars(ev)) {
                // inicializarVars ahora también debería verificar esto
                listaMov.addEventListener('mousemove', manejarMov);
                listaMov.addEventListener('mouseup', finalizarArrastre);
            }
        } else {
            // Mousedown ocurrió FUERA de un draggable-element Y NO en un menú de opciones (ya cubierto arriba).
            // Esto implica un clic en el espacio vacío de la lista. Deseleccionar todo.
            deseleccionarTareas();
        }
    });

    listaMov.addEventListener('click', manejarSeleccionTarea);
    listaMov.addEventListener('dragstart', ev => ev.preventDefault());

    document.addEventListener('click', ev => {
        if (ev.target.closest('.opcionesPrioridad, .opcionesFrecuencia')) {
            return;
        }
        if (listaMov && !listaMov.contains(ev.target)) {
            deseleccionarTareas();
        }
    });
}

/* VARIABLES GLOBALES */
let listaMov,
    // Para el modo individual se usan estas variables:
    arrastrandoElem = null,
    idTarea = null,
    subtareasArrastradas = [],
    esSubtarea = false,
    // Para ambos modos (individual o grupal) se usará:
    arrastrandoElems = [],
    ordenViejo = [],
    posInicialY = null,
    movRealizado = false,
    tareasSeleccionadas = [];

const tolerancia = 10;

// Ajuste sugerido para inicializarVars para que no inicie arrastre
// si el clic es en un control interno que tiene su propia acción.
function inicializarVars(ev) {
    const targetOriginal = ev.target; // El elemento exacto donde ocurrió el mousedown
    const elemArrastrable = targetOriginal.closest('.draggable-element');

    if (!elemArrastrable) return false; // No es un elemento arrastrable

    // Si el mousedown fue directamente en un control DENTRO de la tarea que NO debe iniciar arrastre
    if (targetOriginal.closest('.divImportancia, .divArchivado, .completaTarea, .divFrecuencia')) {
        // Estos elementos tienen sus propios listeners de 'click' para acciones.
        // No queremos que un mousedown en ellos inicie un arrastre.
        return false; // No inicializar para arrastre
    }

    // Lógica original de inicializarVars para determinar el grupo a arrastrar
    let grupo;
    if (tareasSeleccionadas.includes(elemArrastrable.getAttribute('id-post'))) {
        grupo = Array.from(listaMov.querySelectorAll('.draggable-element')).filter(el => tareasSeleccionadas.includes(el.getAttribute('id-post')));
        if (grupo.length === 0) {
            // Fallback si la tarea seleccionada no se encontró bien
            grupo = [elemArrastrable];
        }
    } else {
        grupo = [elemArrastrable];
    }
    arrastrandoElems = grupo;

    // Resto de la lógica de inicializarVars (asignación a arrastrandoElem, esSubtarea, etc.)
    if (grupo.length === 1) {
        arrastrandoElem = grupo[0];
        esSubtarea = arrastrandoElem.getAttribute('subtarea') === 'true';
        idTarea = arrastrandoElem.getAttribute('id-post');
        ordenViejo = Array.from(listaMov.querySelectorAll('.draggable-element')).map(t => t.getAttribute('id-post'));
        if (!esSubtarea) {
            subtareasArrastradas = Array.from(listaMov.querySelectorAll(`.draggable-element[padre="${idTarea}"]`));
        } else {
            subtareasArrastradas = [];
        }
    } else {
        arrastrandoElem = null;
        subtareasArrastradas = [];
        esSubtarea = false;
        idTarea = null;
        ordenViejo = [];
    }

    posInicialY = ev.clientY;
    movRealizado = false;

    arrastrandoElems.forEach(el => el.classList.add('dragging'));
    document.body.classList.add('dragging-active');
    return true; // Inicialización exitosa, se puede proceder a añadir listeners de mousemove/mouseup
}

/* MANEJO DEL MOVIMIENTO */
function manejarMov(ev) {
    if (arrastrandoElems.length === 0) return;
    ev.preventDefault();
    const mouseY = ev.clientY;
    const rectLista = listaMov.getBoundingClientRect();

    if (!movRealizado && Math.abs(mouseY - posInicialY) > tolerancia) {
        movRealizado = true;
    }
    if (mouseY < rectLista.top || mouseY > rectLista.bottom) return;

    // Se obtienen los elementos visibles que NO forman parte del grupo arrastrado
    const elemsVisibles = Array.from(listaMov.children).filter(child => child.style.display !== 'none' && !arrastrandoElems.includes(child));
    let insertado = false;

    // Se recorre la lista para determinar dónde insertar el grupo
    for (let i = 0; i < elemsVisibles.length; i++) {
        const elem = elemsVisibles[i];
        const rectElem = elem.getBoundingClientRect();
        const elemMedio = rectElem.top + rectElem.height / 2;
        if (mouseY < elemMedio) {
            // Se inserta cada elemento del grupo antes del elemento actual
            arrastrandoElems.forEach(el => {
                listaMov.insertBefore(el, elem);
            });
            insertado = true;
            break;
        }
    }
    // Si no se insertó en medio, se agregan al final
    if (!insertado && elemsVisibles.length > 0) {
        arrastrandoElems.forEach(el => {
            listaMov.appendChild(el);
        });
    }

    // En modo individual y si la tarea no es subtarea, se reposicionan también sus subtareas justo detrás
    if (arrastrandoElems.length === 1 && !esSubtarea) {
        let current = arrastrandoElem;
        subtareasArrastradas.forEach(subtarea => {
            listaMov.insertBefore(subtarea, current.nextSibling);
            current = subtarea;
        });
    }
}

/* FINALIZAR ARRASTRE */
function finalizarArrastre() {
    if (arrastrandoElems.length === 0) return;
    const ordenNuevo = Array.from(listaMov.querySelectorAll('.draggable-element')).map(t => t.getAttribute('id-post'));

    if (movRealizado) {
        // MODO INDIVIDUAL: se conserva la lógica original (con manejo de “subtarea”)
        if (arrastrandoElems.length === 1) {
            const nuevaPos = ordenNuevo.indexOf(idTarea);
            const {sesionArriba, dataArriba} = obtenerSesionYData();
            const {nuevaEsSubtarea} = cambioASubtarea();
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
                arrastrandoElem.setAttribute('data-seccion', dataArriba);
                arrastrandoElem.setAttribute('sesion', sesionArriba);
            } else {
                padre = '';
                arrastrandoElem.removeAttribute('padre');
                arrastrandoElem.setAttribute('subtarea', 'false');
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
        } else {
            // MODO GRUPAL: se toma el array de ids de las tareas arrastradas y se determina la posición
            const draggedIds = arrastrandoElems.map(el => el.getAttribute('id-post'));
            // Se toma la menor posición (la del primer elemento en el nuevo orden)
            const primeraPos = Math.min(...draggedIds.map(id => ordenNuevo.indexOf(id)));
            guardarOrdenTareasGrupo({
                tareasMovidas: draggedIds,
                nuevaPos: primeraPos,
                ordenNuevo
            });
        }
    }

    // Se quitan las clases de “arrastre” y se limpian las variables
    arrastrandoElems.forEach(el => el.classList.remove('dragging'));
    document.body.classList.remove('dragging-active');
    listaMov.removeEventListener('mousemove', manejarMov);
    listaMov.removeEventListener('mouseup', finalizarArrastre);

    arrastrandoElem = null;
    arrastrandoElems = [];
    idTarea = null;
    ordenViejo = [];
    posInicialY = null;
    movRealizado = false;
    subtareasArrastradas = [];
    esSubtarea = false;
}

/* Función para obtener datos de la tarea de referencia (sin cambios respecto a la versión original) */
function obtenerSesionYData() {
    let sesionArriba = null;
    let dataArriba = null;
    let anterior = (arrastrandoElem || arrastrandoElems[0]).previousElementSibling;
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

/* Función que determina si la tarea cambia a subtarea (se usa en modo individual) */
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

/* Función para guardar el nuevo orden cuando se mueve una sola tarea (modo individual) */
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
                window.reiniciarPost(idTarea, 'tarea');
            } else {
                console.error('Hubo un error en la respuesta del servidor:', res);
            }
        })
        .catch(err => {
            console.error('Error en la petición AJAX:', err);
        });
}

/* Función para guardar el nuevo orden cuando se mueven varias tareas (modo grupal) */
function guardarOrdenTareasGrupo({tareasMovidas, nuevaPos, ordenNuevo}) {
    let data = {
        tareasMovidas, // array de ids de las tareas arrastradas
        nuevaPos, // posición de inserción (la del primer elemento del grupo)
        ordenNuevo
    };
    enviarAjax('actualizarOrdenTareasGrupo', data)
        .then(res => {
            if (res && res.success) {
                // Opcional: reiniciar cada tarea del grupo
                tareasMovidas.forEach(id => window.reiniciarPost(id, 'tarea'));
            } else {
                console.error('Hubo un error en la respuesta del servidor:', res);
            }
        })
        .catch(err => {
            console.error('Error en la petición AJAX:', err);
        });
}
