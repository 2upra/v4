// task.js

let importancia = {
    selector: null,
    valor: 'media'
};

let tipoTarea = {
    selector: null,
    valor: 'una vez'
};

// NUEVA FUNCIÓN GLOBAL
window.hideAllOpenTaskMenus = function() {
    document.querySelectorAll('.opcionesPrioridad, .opcionesFrecuencia').forEach(menu => {
        if (menu) {
            menu.remove();
        }
    });
};

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

        subTarea();
        window.initNotas();
        window.initEnter();
        window.initMoverTarea();
        window.dividirTarea();
    }
}

function ocultarBotones() {
    const elementosLi = document.querySelectorAll('.draggable-element');

    elementosLi.forEach(li => {
        const elementOculto = li.querySelector('.ocultadoAutomatico');

        li.addEventListener('mouseover', () => {
            elementOculto.style.display = 'block';
        });

        li.addEventListener('mouseout', () => {
            elementOculto.style.display = 'none';
        });
    });
}

function enviarTarea() {
    // Asumo que enviarTareaHandler y pegarTareaHandler son funciones ya definidas en otro lugar.
    const tit = document.getElementById('tituloTarea');

    // Si estas seguro que esta funcion 'enviarTarea' solo se llama una vez
    // para inicializar, podrias quitar los removeEventListener para simplificar.
    // Si se puede llamar multiples veces, dejarlos previene duplicados.
    tit.removeEventListener('keyup', enviarTareaHandler);
    tit.addEventListener('keyup', enviarTareaHandler);
    tit.removeEventListener('paste', pegarTareaHandler);
    tit.addEventListener('paste', pegarTareaHandler);

    tit.addEventListener('input', () => {
        // Reemplaza uno o mas saltos de linea (\n) globalmente (g) por nada ('').
        tit.value = tit.value.replace(/\n+/g, '');
    });
}

//necesito ajustar lo de pegar tareas, porque ya no se usa reiniciarContenido sino reiniciarPost
function pegarTareaHandler(ev) {
    ev.preventDefault();
    const textoPegado = (ev.clipboardData || window.clipboardData).getData('text');
    const lineas = textoPegado
        .split('\n')
        .map(linea => linea.trim())
        .filter(linea => linea);

    if (lineas.length === 0) {
        return;
    }

    const maxTareas = 30;
    const lineasProcesadas = lineas.slice(0, maxTareas);

    if (lineasProcesadas.some(linea => linea.length > 300)) {
        alert('Ningun titulo puede superar los 300 caracteres.');
        return;
    }

    const tit = document.getElementById('tituloTarea');
    const listaTareas = document.querySelector('.tab.active .social-post-list.clase-tarea');

    const promesas = lineasProcesadas.map(titulo => {
        return enviarAjax('crearTarea', {
            titulo: titulo,
            importancia: importancia.valor,
            tipo: tipoTarea.valor
        });
    });

    Promise.all(promesas)
        .then(async respuestas => {
            let tareasCreadasAPI = 0;
            let tareasAgregadasUI = 0;
            let erroresDetallados = [];

            if (tit) {
                tit.value = '';
            }

            for (let i = 0; i < respuestas.length; i++) {
                const rta = respuestas[i];
                const tituloOriginal = lineasProcesadas[i];

                if (rta.success) {
                    tareasCreadasAPI++;
                    if (rta.data && rta.data.tareaId) {
                        try {
                            const tareaNuevaHtml = await window.reiniciarPost(rta.data.tareaId, 'tarea');
                            if (tareaNuevaHtml && listaTareas) {
                                const primerDivisor = listaTareas.querySelector('.divisorTarea');
                                if (primerDivisor) {
                                    primerDivisor.insertAdjacentHTML('afterend', tareaNuevaHtml);
                                } else {
                                    listaTareas.insertAdjacentHTML('afterbegin', tareaNuevaHtml);
                                }
                                tareasAgregadasUI++;
                            } else {
                                erroresDetallados.push(`UI(ID:${rta.data.tareaId},NoHTMLoLista)`);
                            }
                        } catch (e) {
                            erroresDetallados.push(`UI(ID:${rta.data.tareaId},ExcepReiniciarPost:${e.message || e})`);
                        }
                    } else {
                        erroresDetallados.push(`API(Titulo:${tituloOriginal},NoID)`);
                    }
                } else {
                    erroresDetallados.push(`API(Titulo:${tituloOriginal},${rta.data || 'Fallo'})`);
                }
            }

            let logMsg = `pegarTareaHandler: Procesadas ${lineasProcesadas.length}. API OK: ${tareasCreadasAPI}. UI OK: ${tareasAgregadasUI}.`;
            if (erroresDetallados.length > 0) {
                logMsg += ` Errores: [${erroresDetallados.join('; ')}]`;
            }
            console.log(logMsg);

            if (tareasAgregadasUI > 0) {
                initTareas();
                window.guardarOrden();
            }
        })
        .catch(err => {
            console.error(`pegarTareaHandler: Error crítico: ${err.message || err}`);
        });
}

//te dejo un ejemplo correcto
function enviarTareaHandler(ev) {
    const tit = document.getElementById('tituloTarea');
    const listaTareas = document.querySelector('.tab.active .social-post-list.clase-tarea');

    if (ev.key === 'Enter') {
        ev.preventDefault();

        setTimeout(() => {
            if (tit.value.trim().length === 0) return;

            if (tit.value.length > 300) {
                alert('El titulo no puede superar los 300 caracteres.');
                return;
            }

            const data = {
                titulo: tit.value,
                importancia: importancia.valor,
                tipo: tipoTarea.valor
            };

            const tituloParaEnviar = tit.value;
            tit.value = '';

            enviarAjax('crearTarea', {...data, titulo: tituloParaEnviar})
                .then(async rta => {
                    if (rta.success) {
                        alert('Tarea creada.');

                        const tareaNueva = await window.reiniciarPost(rta.data.tareaId, 'tarea');

                        if (tareaNueva && listaTareas) {
                            const primerDivisor = listaTareas.querySelector('.divisorTarea');

                            if (primerDivisor) {
                                primerDivisor.insertAdjacentHTML('afterend', tareaNueva);
                            } else {
                                listaTareas.insertAdjacentHTML('afterbegin', tareaNueva);
                            }

                            initTareas();
                            window.guardarOrden();
                        } else {
                            console.error('enviarTareaHandler: No se recibio respuesta o no se encontro la lista de tareas.');
                            console.error(`enviarTareaHandler: tareaNueva=${tareaNueva}, listaTareas=${listaTareas}`);
                        }
                    } else {
                        let m = 'enviarTareaHandler: Error al crear tarea.';
                        if (rta.data) {
                            m += ' Detalles: ' + rta.data;
                        }
                        alert(m);
                    }
                })
                .catch(err => {
                    console.error('enviarTareaHandler: Error al crear tarea.');
                    alert('Error al crear. Revisa la consola.');
                    console.error(err);
                });
        }, 0);
    }
}

function selectorTipoTarea() {
    importancia.selector = document.getElementById('sImportancia');
    tipoTarea.selector = document.getElementById('sTipo');
    const impContenedor = document.querySelector('#sImportancia-sImportancia .A1806242');
    const tipoContenedor = document.querySelector('#sTipo-sTipo .A1806242');

    function actSel(obj, val) {
        let ico = obj.selector.querySelector('span.icono');
        let txt = document.createElement('p');
        txt.textContent = val;
        if (ico.childNodes.length > 1) {
            ico.removeChild(ico.lastChild);
        }
        ico.appendChild(txt);
        obj.valor = val;
    }

    impContenedor.addEventListener('click', event => {
        if (event.target.tagName === 'BUTTON') {
            actSel(importancia, event.target.value);
            window.hideAllSubmenus();
        }
    });

    tipoContenedor.addEventListener('click', event => {
        if (event.target.tagName === 'BUTTON') {
            actSel(tipoTarea, event.target.value);
            window.hideAllSubmenus();
        }
    });

    actSel(importancia, 'media');
    actSel(tipoTarea, 'una vez');
}

function editarTarea() {
    const tareas = document.querySelectorAll('.tituloTarea');

    tareas.forEach(tarea => {
        // Verifica si la tarea ya tiene un event listener agregado
        if (!tarea.dataset.eventoAgregado) {
            tarea.addEventListener('click', manejarEditarTarea);
            tarea.dataset.eventoAgregado = 'true';
        }
    });
}

function manejarEditarTarea(ev) {
    ev.preventDefault();
    const tarea = this; // 'this' se refiere al elemento que disparo el evento
    const id = tarea.dataset.tarea;
    let valorAnt = tarea.textContent.trim();
    tarea.contentEditable = true;
    tarea.spellcheck = false;
    tarea.focus();

    const off = calcularPosicionCursor(ev, tarea);
    setCursorPos(tarea, off);

    const salirEdicion = () => {
        if (tarea.textContent.trim().length > 300) {
            alert('El titulo no puede superar los 300 caracteres.');
            tarea.textContent = valorAnt;
        } else if (tarea.textContent.trim() !== '' && tarea.textContent.trim() !== valorAnt) {
            guardarEdicion(tarea, id, valorAnt);
        }
        tarea.contentEditable = false;
        // Remover los event listeners despues de usarlos
        tarea.removeEventListener('blur', salirEdicion);
        tarea.removeEventListener('paste', manejarPegado);
    };

    const manejarPegado = ev => {
        ev.preventDefault();
        const texto = ev.clipboardData.getData('text/plain').trim();
        const nuevoTexto = texto.substring(0, 300 - tarea.textContent.trim().length);
        document.execCommand('insertText', false, nuevoTexto);
    };

    // Usar una funcion con nombre para poder removerla despues
    tarea.addEventListener('blur', salirEdicion);
    tarea.addEventListener('paste', manejarPegado);
}

function guardarEdicion(t, id, valorAnt) {
    const valorNuevo = t.textContent.trim();

    if (valorAnt !== valorNuevo) {
        t.contentEditable = false;
        t.style.outline = 'none';
        t.style.border = 'none';
        t.style.boxShadow = 'none';
        const dat = {id, titulo: valorNuevo};
        console.log('Llamando modificarTarea desde guardarEdicion');
        enviarAjax('modificarTarea', dat)
            .then(rta => {
                if (!rta.success) {
                    t.textContent = valorAnt;
                    let m = 'Error al modificar.';
                    if (rta.data) m += ' Detalles: ' + rta.data;
                } else {
                    valorAnt = valorNuevo;
                }
            })
            .catch(err => {
                t.textContent = valorAnt;
                alert('Error al modificar.');
            });
    } else {
        t.contentEditable = false;
        t.style.outline = 'none';
        t.style.border = 'none';
        t.style.boxShadow = 'none';
    }
}

function calcularPosicionCursor(ev, el) {
    const sel = window.getSelection();
    sel.removeAllRanges();

    const rango = document.createRange();
    rango.selectNodeContents(el);
    rango.collapse(true);

    const puntoClic = document.caretPositionFromPoint(ev.clientX, ev.clientY);
    if (puntoClic) {
        sel.setBaseAndExtent(puntoClic.offsetNode, puntoClic.offset, puntoClic.offsetNode, puntoClic.offset);
        return puntoClic.offset;
    }

    return 0;
}

function setCursorPos(el, off) {
    const sel = window.getSelection();
    const rango = document.createRange();

    if (el.firstChild) {
        rango.setStart(el.firstChild, off);
        rango.setEnd(el.firstChild, off);
    } else {
        rango.setStart(el, 0);
        rango.setEnd(el, 0);
    }

    rango.collapse(true);
    sel.removeAllRanges();
    sel.addRange(rango);
}

function prioridadTarea() {
    const boton = document.querySelector('.prioridadTareas');

    if (boton.dataset.eventoAgregado) return;

    boton.addEventListener('click', async () => {
        const lista = document.querySelector('.social-post-list.clase-tarea');
        const divisores = Array.from(lista.querySelectorAll('.divisorTarea'));
        let log = '';

        for (const divisor of divisores) {
            const seccion = divisor.dataset.valor;
            let tarea = divisor.nextElementSibling;
            const tareasSeccion = [];

            while (tarea && tarea.classList.contains('POST-tarea') && tarea.dataset.seccion === seccion) {
                tareasSeccion.push({
                    tarea: tarea,
                    id: tarea.getAttribute('id-post'),
                    impnum: parseInt(tarea.getAttribute('impnum')),
                    padre: tarea.getAttribute('padre'),
                    dif: parseInt(tarea.getAttribute('dif')),
                    tipo: tarea.getAttribute('tipo-tarea')
                });
                tarea = tarea.nextElementSibling;
            }

            const tareasConPadre = tareasSeccion.filter(t => t.padre);
            const tareasSinPadre = tareasSeccion.filter(t => !t.padre);

            tareasSinPadre.sort((a, b) => {
                if (b.impnum !== a.impnum) {
                    return b.impnum - a.impnum;
                } else if ((a.tipo === 'habito' || a.tipo === 'habito rigido') && (b.tipo === 'habito' || b.tipo === 'habito rigido')) {
                    return a.dif - b.dif;
                } else {
                    return 0;
                }
            });

            const tareasOrdenadas = [];

            tareasSinPadre.forEach(tareaSinPadre => {
                tareasOrdenadas.push(tareaSinPadre);
                const subtareas = tareasConPadre.filter(t => t.padre === tareaSinPadre.id);
                subtareas.sort((a, b) => {
                    if (b.impnum !== a.impnum) {
                        return b.impnum - a.impnum;
                    } else if ((a.tipo === 'habito' || a.tipo === 'habito rigido') && (b.tipo === 'habito' || b.tipo === 'habito rigido')) {
                        return a.dif - b.dif;
                    } else {
                        return 0;
                    }
                });

                tareasOrdenadas.push(...subtareas);
            });

            const tablaTareas = [];
            tareasOrdenadas.forEach((t, i) => {
                const indiceDeseado = Array.from(lista.children).indexOf(divisor) + 1 + i;
                const indiceActual = Array.from(lista.children).indexOf(t.tarea);

                if (indiceActual !== indiceDeseado) {
                    const tareaReferencia = lista.children[indiceDeseado];
                    if (tareaReferencia) {
                        lista.insertBefore(t.tarea, tareaReferencia);
                    } else {
                        lista.appendChild(t.tarea);
                    }
                }

                tablaTareas.push({
                    ID: t.id,
                    Imp: t.impnum,
                    Padre: t.padre,
                    Dif: t.dif,
                    Tipo: t.tipo,
                    'Indice Actual': indiceActual,
                    'Indice Deseado': indiceDeseado
                });
            });

            if (tablaTareas.length > 0) {
                //console.table(tablaTareas);
            }
            log += `Se ordenaron ${tareasOrdenadas.length} tareas en la seccion "${seccion}". 
`;
        }
        log += `Se ejecuto prioridadTareas. 
`;

        //console.log(log);
        try {
            window.guardarOrden();
            console.log(log);
        } catch (error) {
            console.error('Error al guardar el orden:', error);
        }
    });

    boton.dataset.eventoAgregado = 'true';
}

function archivarTarea() {
    document.querySelectorAll('.divArchivado').forEach(div => {
        // Remover listener anterior si existe para evitar duplicados
        const listenerExistente = div.funcionListenerArchivo; // Necesitamos guardar una referencia
        if (listenerExistente) {
            div.removeEventListener('click', listenerExistente);
        }

        // Definimos la función listener para poder referenciarla y removerla
        const nuevaFuncionListener = async function () {
            const divClicado = this;
            const tareaElementoOriginal = divClicado.closest('.draggable-element');
            const tareaIdOriginal = divClicado.dataset.tarea;
            const desarchivarOriginal = tareaElementoOriginal.classList.contains('archivado'); // true si YA está archivado (queremos desarchivar)
            let logs = `archivarTarea: Iniciando para tarea ${tareaIdOriginal}. Accion deseada: ${desarchivarOriginal ? 'desarchivar' : 'archivar'}. `;

            // Determinar a qué tareas aplicar la acción
            let idsParaProcesar = [tareaIdOriginal];
            if (tareasSeleccionadas.length > 1 && tareasSeleccionadas.includes(tareaIdOriginal)) {
                idsParaProcesar = [...tareasSeleccionadas];
                logs += `Detectada seleccion multiple (${idsParaProcesar.length} tareas). `;
            } else {
                logs += `Accion individual. `;
            }

            const ul = document.querySelector('.social-post-list.clase-tarea');
            const pGeneral = document.querySelector('p.divisorTarea.General');

            for (const id of idsParaProcesar) {
                const tareaActualElem = document.querySelector(`.draggable-element[id-post="${id}"]`);
                if (!tareaActualElem) {
                    logs += `Tarea ${id} no encontrada en DOM, omitiendo. `;
                    continue; // Saltar al siguiente id
                }
                // La acción (archivar/desarchivar) es la misma para todas, basada en el estado original clicado
                const data = {id: id, desarchivar: desarchivarOriginal};
                logs += `Procesando ${id}. `;

                try {
                    const respuesta = await enviarAjax('archivarTarea', data);
                    if (respuesta.success) {
                        logs += `Éxito AJAX para ${id}. `;
                        if (data.desarchivar) {
                            tareaActualElem.classList.remove('archivado');
                            tareaActualElem.setAttribute('estado', '');
                            if (pGeneral) {
                                pGeneral.after(tareaActualElem); // Mover después del divisor General
                            } else {
                                ul.prepend(tareaActualElem); // O mover al principio si no hay General
                            }
                            logs += `Tarea ${id} desarchivada y movida. `;
                        } else {
                            // Archivando
                            tareaActualElem.classList.add('archivado');
                            tareaActualElem.setAttribute('estado', 'archivado');
                            if (ul) {
                                ul.appendChild(tareaActualElem); // Mover al final
                            }
                            logs += `Tarea ${id} archivada y movida al final. `;
                        }
                    } else {
                        let mensaje = `Error AJAX para ${id}.`;
                        if (respuesta.data) mensaje += ' Detalles: ' + respuesta.data;
                        logs += mensaje;
                        // Considera no mostrar alert() para cada error en un lote
                        // alert(mensaje);
                    }
                } catch (error) {
                    logs += `Excepcion AJAX para ${id}: ${error}. `;
                    // alert('Error al archivar la tarea ' + id);
                }
            }
            // Log general después del bucle
            console.log(logs + 'Fin archivarTarea.');
            // Opcional: guardar orden si el movimiento afecta
            // window.guardarOrden();
        };

        // Asignar la nueva función y guardar referencia
        div.addEventListener('click', nuevaFuncionListener);
        div.funcionListenerArchivo = nuevaFuncionListener;
    });
}

//se que esto oculta la tarea cuando el filtro esta activado pero, no la tiene que ocultar, sino eliminar del dom
function completarTarea() {
    document.querySelectorAll('.completaTarea').forEach(boton => {
        // Remover listener anterior para evitar duplicados si se llama multiples veces
        boton.removeEventListener('click', manejarClicCompletar);
        // Agregar el nuevo listener
        boton.addEventListener('click', manejarClicCompletar);
        // No es necesario el dataset de eventoAgregado si siempre removemos y agregamos
    });
}

function manejarClicCompletar() {
    const botonClicado = this;
    const tareaElemento = botonClicado.closest('.draggable-element');
    const tareaIdOriginal = botonClicado.dataset.tarea;
    const estadoOriginal = tareaElemento.classList.contains('completada') ? 'pendiente' : 'completada';
    const esHabitoOriginal = botonClicado.classList.contains('habito');
    const esHabitoFlexibleOriginal = botonClicado.classList.contains('habitoFlexible');
    let log = `manejarClicCompletar: Iniciando para tarea ${tareaIdOriginal}. Estado deseado: ${estadoOriginal}. `;

    // Determinar a qué tareas aplicar la acción
    let idsParaProcesar = [tareaIdOriginal];
    if (tareasSeleccionadas.length > 1 && tareasSeleccionadas.includes(tareaIdOriginal)) {
        idsParaProcesar = [...tareasSeleccionadas]; // Clonar para no modificar el original accidentalmente
        log += `Detectada seleccion multiple (${idsParaProcesar.length} tareas). `;
    } else {
        log += `Accion individual. `;
    }

    // Procesar cada tarea necesaria
    idsParaProcesar.forEach(id => {
        const tareaActualElem = document.querySelector(`.draggable-element[id-post="${id}"]`);
        // Es posible que algún elemento seleccionado ya no exista si se eliminó previamente
        if (!tareaActualElem) {
            log += `Tarea ${id} no encontrada en el DOM, omitiendo. `;
            return; // Saltar a la siguiente iteración
        }
        // Necesitamos obtener las propiedades específicas de CADA tarea en el bucle
        const botonActual = tareaActualElem.querySelector('.completaTarea');
        const esHabitoActual = botonActual ? botonActual.classList.contains('habito') : false;
        const esHabitoFlexibleActual = botonActual ? botonActual.classList.contains('habitoFlexible') : false;
        const estadoActual = tareaActualElem.classList.contains('completada') ? 'pendiente' : 'completada';
        // Importante: Usar el estado deseado consistentemente para todas las tareas del grupo
        const estadoDeseado = estadoOriginal;

        const dat = {id: id, estado: estadoDeseado};
        log += `Procesando ${id}. `;

        enviarAjax('completarTarea', dat)
            .then(rta => {
                if (rta.success) {
                    log += `Éxito AJAX para ${id}. `;
                    if (estadoDeseado === 'completada') {
                        // Aplicar estilos solo si no es hábito (los hábitos se reinician)
                        if (!esHabitoActual && !esHabitoFlexibleActual) {
                            tareaActualElem.classList.add('completada');
                            tareaActualElem.style.textDecoration = 'line-through';
                        }

                        // Ocultar/Eliminar si filtro activo y no es hábito
                        if (window.filtrosGlobales && window.filtrosGlobales.includes('ocultarCompletadas') && !esHabitoActual && !esHabitoFlexibleActual) {
                            tareaActualElem.remove();
                            log += `Tarea ${id} eliminada del DOM (filtro activo). `;
                        } else if (esHabitoActual || esHabitoFlexibleActual) {
                            // Reiniciar hábito individualmente
                            log += `Tarea ${id} es habito/flexible, reiniciando post. `;
                            window.reiniciarPost(id, 'tarea');
                        }
                    } else {
                        // estado deseado es 'pendiente'
                        tareaActualElem.classList.remove('completada');
                        tareaActualElem.style.textDecoration = 'none';
                        tareaActualElem.style.display = ''; // Asegurar que sea visible
                        log += `Tarea ${id} marcada como pendiente. `;
                    }
                } else {
                    let m = `Error AJAX para ${id}.`;
                    if (rta.data) m += ' Detalles: ' + rta.data;
                    log += m;
                    // Considera no mostrar alert() para cada error en un lote
                    // alert(m);
                }
                // Imprimir log al final del procesamiento de esta tarea específica
                // console.log(log); // Opcional: log por tarea
            })
            .catch(err => {
                log += `Excepcion AJAX para ${id}: ${err}. `;
                // alert('Error al completar la tarea ' + id);
            });
    });
    // Imprimir log general después de intentar procesar todas
    console.log(log + 'Fin manejarClicCompletar.');
}
function cambiarFrecuencia() {
    document.querySelectorAll('.divFrecuencia').forEach(div => {
        const listenerExistente = div.funcionListenerFrecuencia;
        if (listenerExistente) {
            div.removeEventListener('click', listenerExistente);
        }

        const nuevaFuncionListener = async function() { // Este es el manejador de clics
            const divClicado = this;
            const tareaId = divClicado.dataset.tarea;
            const li = document.querySelector(`.POST-tarea[id-post="${tareaId}"]`);

            if (!li) return;

            window.hideAllOpenTaskMenus(); // MODIFICACIÓN: Ocultar todos los menús abiertos

            const ops = document.createElement('div');
            ops.classList.add('opcionesFrecuencia');
            ops.innerHTML = `
                <p data-frecuencia="1">diaria</p>
                <p data-frecuencia="7">semanal</p>
                <p data-frecuencia="30">mensual</p>
                <div class="frecuenciaPersonalizada">
                    <input type="number" id="diasPersonalizados" min="2" max="365" placeholder="Cada X dias">
                    <button id="btnPersonalizar">${window.enviarMensaje}</button>
                </div>
            `;
            
            li.after(ops); // Add the new menu after closing all others

            const ps = ops.querySelectorAll('p:not([data-frecuencia="personalizada"])');
            ps.forEach(p => {
                p.addEventListener('click', () => {
                    const frec = p.dataset.frecuencia;
                    const data = {
                        tareaId: tareaId,
                        frecuencia: parseInt(frec)
                    };
                    actualizarFrecuencia(data, divClicado);
                    ops.remove();
                });
            });

            const btn = ops.querySelector('#btnPersonalizar');
            btn.addEventListener('click', () => {
                const input = ops.querySelector('#diasPersonalizados');
                const dias = parseInt(input.value);
                if (dias >= 2 && dias <= 365) {
                    const data = {
                        tareaId: tareaId,
                        frecuencia: dias
                    };
                    actualizarFrecuencia(data, divClicado);
                    ops.remove();
                }
            });
        };

        div.addEventListener('click', nuevaFuncionListener);
        div.funcionListenerFrecuencia = nuevaFuncionListener;
    });
}

function actualizarFrecuencia(data, div) {
    enviarAjax('cambiarFrecuencia', data);
    const padre = div.querySelector('.frecuenciaTarea');
    let span = padre.querySelector('.tituloFrecuencia');
    if (!span) {
        span = document.createElement('span');
        span.classList.add('tituloFrecuencia');
        padre.appendChild(span);
    }
    if (data.frecuencia === 1) {
        span.textContent = 'diaria';
    } else if (data.frecuencia === 7) {
        span.textContent = 'semanal';
    } else if (data.frecuencia === 30) {
        span.textContent = 'mensual';
    } else {
        span.textContent = `${data.frecuencia}d`;
    }
}

function cambiarPrioridad() {
    document.querySelectorAll('.divImportancia').forEach(div => {
        const listenerExistente = div.funcionListenerPrioridad;
        if (listenerExistente) {
            div.removeEventListener('click', listenerExistente);
        }
        const nuevaFuncionListener = manejarClicPrioridad;
        div.addEventListener('click', nuevaFuncionListener);
        div.funcionListenerPrioridad = nuevaFuncionListener;
    });
}

function manejarClicPrioridad() {
    const divPrioridadOriginal = this;
    const idOriginal = divPrioridadOriginal.dataset.tarea;
    const liOriginal = document.querySelector(`.POST-tarea[id-post="${idOriginal}"]`);

    if (!liOriginal) return;

    // document.querySelectorAll('.opcionesPrioridad').forEach(menu => menu.remove()); // Línea original eliminada
    window.hideAllOpenTaskMenus(); // MODIFICACIÓN: Ocultar todos los menús abiertos

    const ops = document.createElement('div');
    ops.classList.add('opcionesPrioridad');
    ops.innerHTML = `
        <p data-prioridad="baja">${window.iconbaja || 'B'} baja</p>
        <p data-prioridad="media">${window.iconMedia || 'M'} media</p>
        <p data-prioridad="alta">${window.iconAlta || 'A'} alta</p>
        <p data-prioridad="importante">${window.iconimportante || 'I'} importante</p>
      `;
    liOriginal.after(ops);

    const cerrarMenuSiClicFuera = event => {
        if (ops.contains(event.target) || (divPrioridadOriginal && divPrioridadOriginal.contains(event.target))) {
            return;
        }
        ops.remove();
        document.removeEventListener('click', cerrarMenuSiClicFuera);
    };

    setTimeout(() => {
        document.addEventListener('click', cerrarMenuSiClicFuera);
    }, 0);

    const ps = ops.querySelectorAll('p');
    ps.forEach(p => {
        p.addEventListener('click', async event => {
            // <--- **Añadido 'async' aquí**
            event.stopPropagation();

            const prioSeleccionada = p.dataset.prioridad;
            const tareasSelActuales = tareasSeleccionadas || [];

            console.log(`DEBUG cambiarPrioridad (CON stopPropagation): idOriginal: "${idOriginal}", Prio: ${prioSeleccionada}, tareasSelActuales: ${JSON.stringify(tareasSelActuales)}, incluyeOriginal: ${tareasSelActuales.includes(idOriginal)}, longitud > 1: ${tareasSelActuales.length > 1}`);

            let logs = `cambiarPrioridad: Opción '${prioSeleccionada}' seleccionada para tarea original ${idOriginal}. `;

            let idsParaProcesar = [idOriginal];
            if (tareasSelActuales.length > 1 && tareasSelActuales.includes(idOriginal)) {
                idsParaProcesar = [...tareasSelActuales];
                logs += `Detectada seleccion multiple (${idsParaProcesar.length} tareas). `;
            } else {
                logs += `Accion individual. `;
            }

            ops.remove();
            document.removeEventListener('click', cerrarMenuSiClicFuera);

            let logsFinales = logs;

            // Función asíncrona para procesar cada tarea secuencialmente
            async function procesarUnaTarea(id, prio) {
                const data = {tareaId: id, prioridad: prio};
                try {
                    const rta = await enviarAjax('cambiarPrioridad', data);
                    if (rta.success) {
                        logsFinales += `Éxito AJAX para ${id}. Reiniciando post. `;
                        // Llamar a reiniciarPost
                        window.reiniciarPost(id, 'tarea'); // Asumiendo que reiniciarPost no devuelve una promesa que necesitemos 'await'
                    } else {
                        let m = `Error AJAX para ${id}.`;
                        if (rta.data) m += ' Detalles: ' + rta.data;
                        logsFinales += m + ' ';
                    }
                } catch (err) {
                    logsFinales += `Excepcion AJAX para ${id}: ${err}. `;
                }
            }

            // Bucle para procesar todas las tareas seleccionadas
            for (let i = 0; i < idsParaProcesar.length; i++) {
                const id = idsParaProcesar[i];
                await procesarUnaTarea(id, prioSeleccionada);

                // Esperar un corto tiempo entre reinicios si hay múltiples tareas,
                // para evitar el mensaje "La función ya está en ejecución."
                // Ajusta el tiempo (en milisegundos) si es necesario.
                if (idsParaProcesar.length > 1 && i < idsParaProcesar.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 300)); // Espera 300ms
                }
            }
            console.log(logsFinales + 'Fin cambiarPrioridad.');
        });
    });
}

async function borrarTareasCompletadas() {
    const boton = document.querySelector('.borrarTareasCompletadas');
    let limpiar = true;

    async function handleClick() {
        const confirmado = await confirm('¿Estas seguro de que quieres borrar todas las tareas completadas?');

        if (confirmado) {
            const data = {
                limpiar: true
            };

            try {
                await enviarAjax('borrarTareasCompletadas', data);
                //console.log('Tareas completadas borradas exitosamente');
                window.reiniciarContenido(limpiar, '', 'tarea');
            } catch (error) {
                console.error('Error al borrar tareas:', error);
            }
        }
    }

    if (boton.listener) {
        boton.removeEventListener('click', boton.listener);
    }

    boton.addEventListener('click', handleClick);
    boton.listener = handleClick;
}

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
                dataSeccionArriba = anterior.getAttribute('data-seccion');
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

let subTareaListenerAgregado = false;

function subTarea() {
    const lista = document.querySelector('.clase-tarea');
    if (!lista) {
        // console.log('subTarea: Lista de tareas no encontrada.'); // Log opcional para desarrollo
        return;
    }

    if (subTareaListenerAgregado) {
        return;
    }

    lista.addEventListener('keydown', ev => {
        const elActual = document.activeElement;

        if (elActual.classList.contains('tituloTarea') && elActual.isContentEditable) {
            const tareaActual = elActual.closest('.POST-tarea');
            if (!tareaActual) return;

            const idActual = tareaActual.getAttribute('id-post');

            if (ev.shiftKey && ev.key === 'Tab') {
                ev.preventDefault();
                if (tareaActual.classList.contains('subtarea')) {
                    tareaActual.classList.remove('subtarea');
                    tareaActual.removeAttribute('padre');

                    const datos = {id: idActual, subtarea: false};
                    enviarAjax('crearSubtarea', datos)
                        .then(rta => {
                            let log = `subTarea Shift+Tab: ID ${idActual} -> ya no es subtarea. RTA ${rta.success}`;
                            if (!rta.success && rta.data) log += `. Error: ${rta.data}`;
                            console.log(log);
                            if (!rta.success) {
                                tareaActual.classList.add('subtarea');
                                // Si guardabas el id del padre anterior, deberías restaurarlo aquí.
                            }
                        })
                        .catch(err => {
                            console.error(`subTarea Shift+Tab: ID ${idActual}. Excepcion: ${err}`);
                            tareaActual.classList.add('subtarea');
                        });
                }
            } else if (ev.key === 'Tab' && !ev.shiftKey && !ev.ctrlKey && !ev.altKey) {
                ev.preventDefault();
                const tareaAnterior = tareaActual.previousElementSibling;

                if (tareaAnterior && tareaAnterior.classList.contains('POST-tarea') && tareaAnterior !== tareaActual) {
                    tareaActual.classList.add('subtarea');
                    const idAnterior = tareaAnterior.getAttribute('id-post');
                    tareaActual.setAttribute('padre', idAnterior);

                    const datos = {id: idActual, padre: idAnterior, subtarea: true};
                    enviarAjax('crearSubtarea', datos)
                        .then(rta => {
                            let log = `subTarea Tab: ID ${idActual} -> subtarea de ${idAnterior}. RTA ${rta.success}`;
                            if (!rta.success && rta.data) log += `. Error: ${rta.data}`;
                            console.log(log);
                            if (!rta.success) {
                                tareaActual.classList.remove('subtarea');
                                tareaActual.removeAttribute('padre');
                            }
                        })
                        .catch(err => {
                            console.error(`subTarea Tab: ID ${idActual} subtarea de ${idAnterior}. Excepcion: ${err}`);
                            tareaActual.classList.remove('subtarea');
                            tareaActual.removeAttribute('padre');
                        });
                }
            }
        }
    });

    subTareaListenerAgregado = true;
    // console.log('subTarea: Listener inicializado.'); // Log opcional para desarrollo
}

function borrarTareaVacia() {
    const tareas = document.querySelectorAll('.tituloTarea');

    tareas.forEach(tarea => {
        let borrar = false; // Bandera especifica para cada tarea

        tarea.addEventListener('keydown', ev => {
            // Usar trim() para ignorar espacios en blanco al verificar si esta vacio
            const estaVacio = tarea.textContent.trim() === '';

            if (ev.key === 'Backspace' && estaVacio) {
                if (borrar) {
                    // Segunda vez consecutiva con Backspace en campo vacio: Borrar
                    const id = tarea.dataset.tarea;
                    const tareaCompleta = tarea.closest('.POST-tarea'); // Mas robusto para encontrar el padre

                    if (!tareaCompleta) {
                        console.error(`borrarTareaVacia: No se encontró .POST-tarea para id ${id}`);
                        return; // Evita errores si el contenedor no existe
                    }

                    // Intentar remover listeners (asumiendo que estan como propiedades directas)
                    // Considera si necesitas una forma mas robusta de guardar/remover listeners
                    try {
                        if (typeof tarea.onInput === 'function') tarea.removeEventListener('input', tarea.onInput);
                        if (typeof tarea.onBlur === 'function') tarea.removeEventListener('blur', tarea.onBlur);
                        if (typeof tarea.onPaste === 'function') tarea.removeEventListener('paste', tarea.onPaste);
                    } catch (e) {
                        console.warn(`borrarTareaVacia: Problema al remover listeners para id ${id}`, e);
                    }

                    tareaCompleta.remove(); // Eliminar elemento del DOM

                    // Construir log inicial (una sola linea)
                    let log = `borrarTareaVacia: Tarea ${id} borrada localmente, enviando AJAX.`;

                    const datos = {
                        id: id,
                        nonce: task_vars.borrar_tarea_nonce // Asegurate que task_vars este definido globalmente
                    };

                    // Asumo que enviarAjax devuelve una Promesa
                    enviarAjax('borrarTarea', datos)
                        .then(resp => {
                            // Añadir al log existente, manteniendo una sola linea
                            log += ` Respuesta AJAX: ${resp}`;
                            console.log(log);
                        })
                        .catch(error => {
                            // Añadir al log existente, manteniendo una sola linea
                            log += ` Error AJAX: ${error}`;
                            console.error(log);
                        });

                    borrar = false; // Resetear bandera aunque el elemento ya no deberia recibir eventos
                } else {
                    // Primera vez con Backspace en campo vacio: Activar bandera
                    borrar = true;
                }
            } else {
                // Cualquier otra tecla, o si no esta vacio: Resetear bandera
                borrar = false;
            }
        });
    });
}
