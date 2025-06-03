// js/taskCore.js

let importancia = {
    selector: null,
    valor: 'media'
};

let tipoTarea = {
    selector: null,
    valor: 'una vez'
};

let fechaLimite = {
    selector: null,
    valor: null
};

let subTareaListenerAgregado = false;

function initTareas() {
    const tit = document.getElementById('tituloTarea');

    if (tit) {
        selectorTipoTarea();
        enviarTarea();
        editarTarea();
        completarTarea();
        cambiarPrioridad();
        prioridadTarea();
        borrarTareasCompletadas();
        cambiarFrecuencia();
        archivarTarea();
        ocultarBotones();
        borrarTareaVacia();

        iniciarManejadoresFechaLimiteMeta();
        iniciarManejadoresFechaProximaHabito();

        subTarea();
        window.initCal();
        window.initNotas();
        window.initEnter();
        window.initMoverTarea();
        window.dividirTarea();
        window.initAsignarSeccionModal();
        initMarcarDiaHabito(); // Nueva función para los días de hábito
    }
}

window.hideAllOpenTaskMenus = function () {
    document.querySelectorAll('.opcionesPrioridad, .opcionesFrecuencia').forEach(menu => {
        if (menu) menu.remove();
    });

    const cal = document.getElementById('calCont');
    if (cal && cal.style.display === 'block') {
        ocultarCal(); // Llama a tu función para ocultar el calendario
    }

    if (window.cerrarMenuSiClicFueraPrioridadHandler) {
        document.removeEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
        window.cerrarMenuSiClicFueraPrioridadHandler = null;
    }
    if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
        document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
        window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
    }
    // El listener para cerrar el calendario si se hace clic fuera ya se maneja en initCal y se limpia en ocultarCal.
};

window.guardarOrden = function () {
    const lista = document.querySelector('.clase-tarea');
    if (!lista) return;

    const tareas = Array.from(lista.querySelectorAll('.draggable-element'));
    if (tareas.length < 2) return;

    const tareaMovida = tareas[0];
    const segundaTarea = tareas[0];
    lista.insertBefore(tareaMovida, segundaTarea.nextSibling);

    const ordenNuevo = Array.from(lista.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
    const nuevaPosicion = ordenNuevo.indexOf(tareaMovida.getAttribute('id-post'));

    let sesionArriba = null;
    let dataSeccionArriba = null;
    let anterior = tareaMovida.previousElementSibling;
    while (anterior) {
        if (anterior.classList.contains('POST-tarea')) {
            sesionArriba = anterior.getAttribute('sesion');
            if (dataSeccionArriba === null) {
                dataSeccionArriba = anterior.getAttribute('data-sesion');
            }
        } else if (anterior.classList.contains('divisorTarea')) {
            if (sesionArriba === null) {
                sesionArriba = anterior.getAttribute('data-valor');
            }
            if (dataSeccionArriba === null) {
                dataSeccionArriba = anterior.getAttribute('data-valor');
            }
        }
        if (sesionArriba !== null && dataSeccionArriba !== null) break;
        anterior = anterior.previousElementSibling;
    }

    guardarOrdenTareas({
        idTareaMovida: tareaMovida.getAttribute('id-post'),
        nuevaPosicion: nuevaPosicion,
        ordenNuevo: ordenNuevo,
        sesionArriba: sesionArriba,
        dataSeccionArriba: dataSeccionArriba
    });
};

window.reiniciarTareaYSubtareas = function (idTareaPrincipal) {
    const tareaElem = document.querySelector(`.POST-tarea[id-post="${idTareaPrincipal}"]`);
    let log = `reiniciarTareaYSubtareas: TareaID ${idTareaPrincipal}. `;

    if (tareaElem) {
        log += `Principal reiniciando. `;
        window.reiniciarPost(idTareaPrincipal, 'tarea');

        // Buscar subtareas directas de esta tarea
        // La clase 'tarea-padre' es un buen indicador, pero buscar por atributo 'padre' es más directo.
        const subtareasElems = document.querySelectorAll(`.POST-tarea[padre="${idTareaPrincipal}"]`);

        if (subtareasElems.length > 0) {
            log += `${subtareasElems.length} subtareas encontradas. `;
            subtareasElems.forEach(subElem => {
                const idSub = subElem.getAttribute('id-post');
                if (idSub) {
                    log += `SubID ${idSub} reiniciando. `;
                    window.reiniciarPost(idSub, 'tarea');
                }
            });
        } else {
            log += `No se encontraron subtareas en DOM. `;
        }
    } else {
        log += `Elemento principal no encontrado en DOM. `;
    }
    console.log(log); // Descomenta si necesitas depurar esta función específicamente
};
