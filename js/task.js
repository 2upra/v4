// task.js

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

let calMes;
let calAnio;
const calNombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
const calDiasSemanaCabecera = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'];

let contextoCalendario = {
    esParaTareaEspecifica: false,
    idTarea: null,
    elementoSpanTexto: null,
    elementoLiTarea: null,
    elementoDisparador: null,
    tipoFecha: null // Nuevo: 'limite' o 'proxima'
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

        iniciarManejadoresFechaLimiteMeta();
        iniciarManejadoresFechaProximaHabito();

        subTarea();
        window.initCal();
        window.initNotas();
        window.initEnter();
        window.initMoverTarea();
        window.dividirTarea();
        window.initAsignarSeccionModal();
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

function ocultarBotones() {
    const elementosLi = document.querySelectorAll('.draggable-element'); // Asumo que esta es la clase de tus <li> o contenedores de tarea

    elementosLi.forEach(li => {
        // Evita añadir listeners múltiples veces al mismo elemento li
        if (li.dataset.botonesOcultosInicializados) {
            // Si los elementos ocultos pudieran cambiar dinámicamente DESPUÉS de esta inicialización,
            // se necesitaría una lógica más compleja para actualizar los listeners o los elementos cacheados.
            // Por ahora, asumimos que una vez que un li es procesado, sus hijos 'ocultadoAutomatico' no cambian.
            return;
        }

        const elementosOcultos = li.querySelectorAll('.ocultadoAutomatico'); // Clave: seleccionar TODOS

        if (elementosOcultos.length > 0) {
            const manejadorMouseOver = () => {
                elementosOcultos.forEach(eo => {
                    // La condición "solo aparecera cuando la tarea no tenga fecha limite"
                    // la maneja tu PHP al no generar el div si ya hay fecha, o no dándole la clase 'ocultadoAutomatico'.
                    // Por lo tanto, si el elemento está aquí y tiene 'ocultadoAutomatico', debe mostrarse.
                    eo.style.display = 'block';
                });
            };

            const manejadorMouseOut = () => {
                elementosOcultos.forEach(eo => {
                    eo.style.display = 'none';
                });
            };

            li.addEventListener('mouseover', manejadorMouseOver);
            li.addEventListener('mouseout', manejadorMouseOut);

            // Guardar referencias a los manejadores si necesitaras removerlos específicamente después
            // li._manejadorMouseOverBotonesOcultos = manejadorMouseOver;
            // li._manejadorMouseOutBotonesOcultos = manejadorMouseOut;

            li.dataset.botonesOcultosInicializados = 'true'; // Marcar como inicializado
        }
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
        .map(l => l.trim())
        .filter(l => l);
    if (lineas.length === 0) return;

    const maxTareas = 30;
    const lineasProcesadas = lineas.slice(0, maxTareas);
    if (lineasProcesadas.some(l => l.length > 300)) {
        alert('Ningun titulo puede superar los 300 caracteres.');
        return;
    }

    const tit = document.getElementById('tituloTarea');
    const listaTareas = document.querySelector('.tab.active .social-post-list.clase-tarea');
    const promesas = lineasProcesadas.map(titulo => {
        return enviarAjax('crearTarea', {
            titulo: titulo,
            importancia: importancia.valor,
            tipo: tipoTarea.valor,
            // MODIFICACIÓN AQUÍ:
            fechaLimite: tipoTarea.valor === 'meta' ? fechaLimite.valor : null
        });
    });

    Promise.all(promesas)
        .then(async respuestas => {
            let creadasAPI = 0,
                agregadasUI = 0,
                errs = [];
            if (tit) tit.value = '';

            for (let i = 0; i < respuestas.length; i++) {
                const rta = respuestas[i],
                    titOrig = lineasProcesadas[i];
                if (rta.success && rta.data?.tareaId) {
                    creadasAPI++;
                    try {
                        const html = await window.reiniciarPost(rta.data.tareaId, 'tarea');
                        if (html && listaTareas) {
                            const div = listaTareas.querySelector('.divisorTarea');
                            div ? div.insertAdjacentHTML('afterend', html) : listaTareas.insertAdjacentHTML('afterbegin', html);
                            agregadasUI++;
                        } else errs.push(`UI(ID:${rta.data.tareaId},NoHTMLoLista)`);
                    } catch (e) {
                        errs.push(`UI(ID:${rta.data.tareaId},Excep:${e.message || e})`);
                    }
                } else errs.push(`API(Tit:${titOrig},${rta.data || 'Fallo'})`);
            }
            let log = `pegarTareaHandler: Proc ${lineasProcesadas.length}. API OK:${creadasAPI}. UI OK:${agregadasUI}.`;
            if (errs.length) log += ` Errs:[${errs.join('; ')}]`;
            console.log(log);
            if (agregadasUI > 0) {
                initTareas();
                window.guardarOrden();
            }
        })
        .catch(err => console.error(`pegarTareaHandler: Error crítico: ${err.message || err}`));
}

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
                tipo: tipoTarea.valor,
                // MODIFICACIÓN AQUÍ:
                fechaLimite: tipoTarea.valor === 'meta' ? fechaLimite.valor : null
            };
            const tituloParaEnviar = tit.value;
            tit.value = '';

            enviarAjax('crearTarea', {...data, titulo: tituloParaEnviar})
                .then(async rta => {
                    if (rta.success && rta.data?.tareaId) {
                        // alert('Tarea creada.'); // Opcional
                        const tareaNueva = await window.reiniciarPost(rta.data.tareaId, 'tarea');
                        if (tareaNueva && listaTareas) {
                            const primerDivisor = listaTareas.querySelector('.divisorTarea');
                            primerDivisor ? primerDivisor.insertAdjacentHTML('afterend', tareaNueva) : listaTareas.insertAdjacentHTML('afterbegin', tareaNueva);
                            initTareas();
                            window.guardarOrden();
                        } else {
                            console.error(`enviarTareaHandler: No HTML o lista. tareaNueva=${tareaNueva}, listaTareas=${listaTareas}`);
                        }
                    } else {
                        alert(`enviarTareaHandler: Error al crear. ${rta.data ? 'Detalles: ' + rta.data : ''}`);
                    }
                })
                .catch(err => {
                    console.error('enviarTareaHandler: Error al crear tarea.', err);
                    alert('Error al crear. Revisa la consola.');
                });
        }, 0);
    }
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
                tipo: tipoTarea.valor,
                fechaLimite: fechaLimite.valor // Añadir fechaLimite
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

function actSel(obj, val, txtPredeterminado = '') {
    // Por defecto, no hay texto predeterminado
    let ico = obj.selector.querySelector('span.icono');

    // Limpiar contenido previo (texto) del span.icono, dejando el SVG/ícono base
    while (ico.childNodes.length > 1 && (ico.lastChild.nodeType === Node.TEXT_NODE || ico.lastChild.tagName === 'P')) {
        ico.removeChild(ico.lastChild);
    }

    let textoAMostrar = '';
    if (val) {
        if (obj === fechaLimite) {
            // Formatear fecha si es el selector de fecha
            const partesFecha = val.split('-'); // val es YYYY-MM-DD
            // Formato corto: DD/MM
            textoAMostrar = `${partesFecha[2]}/${partesFecha[1]}`;
            // Formato más completo: DD NombreMesCorto (ej: 25 Jul)
            // const fechaObj = new Date(parseInt(partesFecha[0]), parseInt(partesFecha[1]) - 1, parseInt(partesFecha[2]));
            // textoAMostrar = `${partesFecha[2]} ${calNombresMeses[fechaObj.getMonth()]}`;
        } else {
            textoAMostrar = val;
        }
    } else if (txtPredeterminado) {
        // Solo si se proporciona explícitamente un texto predeterminado
        textoAMostrar = txtPredeterminado;
    }

    if (textoAMostrar) {
        // Solo añadir el <p> si hay algo que mostrar
        let txtElem = document.createElement('p');
        txtElem.textContent = textoAMostrar;
        ico.appendChild(txtElem);
    }
    obj.valor = val;
}

function selectorTipoTarea() {
    importancia.selector = document.getElementById('sImportancia');
    tipoTarea.selector = document.getElementById('sTipo');
    fechaLimite.selector = document.getElementById('sFechaLimite');
    // La función actSel ya está definida globalmente

    const impContenedor = document.querySelector('#sImportancia-sImportancia .A1806242');
    const tipoContenedor = document.querySelector('#sTipo-sTipo .A1806242');

    if (impContenedor) {
        impContenedor.addEventListener('click', event => {
            if (event.target.tagName === 'BUTTON') {
                actSel(importancia, event.target.value);
                window.hideAllSubmenus();
            }
        });
    }

    if (tipoContenedor) {
        tipoContenedor.addEventListener('click', event => {
            if (event.target.tagName === 'BUTTON') {
                actSel(tipoTarea, event.target.value);
                window.hideAllSubmenus();
            }
        });
    }

    // Valores iniciales
    actSel(importancia, 'media');
    actSel(tipoTarea, 'una vez');
    actSel(fechaLimite, null); // No pasamos 'Sin fecha', actSel lo maneja
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

        const nuevaFuncionListener = async function (event) {
            event.stopPropagation(); // Evita que el listener del documento cierre el menú inmediatamente

            const divClicado = this;
            const tareaId = divClicado.dataset.tarea;
            const li = document.querySelector(`.POST-tarea[id-post="${tareaId}"]`);

            if (!li) return;

            // Verificar si ya hay un menú de frecuencia abierto PARA ESTA TAREA
            const menuExistente = li.nextElementSibling;
            if (menuExistente && menuExistente.classList.contains('opcionesFrecuencia') && menuExistente.dataset.tareaMenuId === tareaId) {
                menuExistente.remove();
                if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
                    document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
                    window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
                }
                return; // Menú estaba abierto, ahora cerrado (toggle)
            }

            // Si no hay menú para esta tarea, o si hay otro menú abierto, cerrar todos los menús primero
            window.hideAllOpenTaskMenus();

            const ops = document.createElement('div');
            ops.classList.add('opcionesFrecuencia');
            ops.dataset.tareaMenuId = tareaId; // Marcar el menú con el ID de la tarea
            ops.innerHTML = `
                <p data-frecuencia="1">diaria</p>
                <p data-frecuencia="7">semanal</p>
                <p data-frecuencia="30">mensual</p>
                <div class="frecuenciaPersonalizada">
                    <input type="number" id="diasPersonalizados" min="2" max="365" placeholder="Cada X dias">
                    <button id="btnPersonalizar">${window.enviarMensaje || 'Enviar'}</button>
                </div>
            `;

            li.after(ops);

            // Definir y guardar el manejador para poder removerlo
            window.cerrarMenuSiClicFueraFrecuenciaHandler = e => {
                if (!ops.contains(e.target) && !divClicado.contains(e.target)) {
                    ops.remove();
                    if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
                        document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
                        window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
                    }
                }
            };

            setTimeout(() => {
                // Añadir listener después del ciclo de evento actual
                document.addEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
            }, 0);

            const ps = ops.querySelectorAll('p:not([data-frecuencia="personalizada"])');
            ps.forEach(p => {
                p.addEventListener('click', evP => {
                    evP.stopPropagation();
                    const frec = p.dataset.frecuencia;
                    const data = {
                        tareaId: tareaId,
                        frecuencia: parseInt(frec)
                    };
                    actualizarFrecuencia(data, divClicado); // Asumiendo que actualizarFrecuencia existe
                    ops.remove();
                    if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
                        document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
                        window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
                    }
                });
            });

            const btn = ops.querySelector('#btnPersonalizar');
            btn.addEventListener('click', evBtn => {
                evBtn.stopPropagation();
                const input = ops.querySelector('#diasPersonalizados');
                const dias = parseInt(input.value);
                if (dias >= 2 && dias <= 365) {
                    const data = {
                        tareaId: tareaId,
                        frecuencia: dias
                    };
                    actualizarFrecuencia(data, divClicado); // Asumiendo que actualizarFrecuencia existe
                    ops.remove();
                    if (window.cerrarMenuSiClicFueraFrecuenciaHandler) {
                        document.removeEventListener('click', window.cerrarMenuSiClicFueraFrecuenciaHandler);
                        window.cerrarMenuSiClicFueraFrecuenciaHandler = null;
                    }
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

async function manejarClicPrioridad(event) {
    // Este es el listener para el click en .divImportancia
    event.stopPropagation(); //Añadido para que el listener global no lo cierre al instante

    const divPrioridadOriginal = this;
    const idOriginal = divPrioridadOriginal.dataset.tarea;
    const liOriginal = document.querySelector(`.POST-tarea[id-post="${idOriginal}"]`);

    if (!liOriginal) return;

    // Lógica de Toggle: Buscar si ya hay un menú de prioridad abierto PARA ESTA TAREA
    const menuExistente = liOriginal.nextElementSibling;
    if (menuExistente && menuExistente.classList.contains('opcionesPrioridad') && menuExistente.dataset.tareaMenuId === idOriginal) {
        menuExistente.remove();
        if (window.cerrarMenuSiClicFueraPrioridadHandler) {
            document.removeEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
            window.cerrarMenuSiClicFueraPrioridadHandler = null;
        }
        return; // Menú estaba abierto, ahora cerrado (toggle)
    }

    // Si no hay menú para esta tarea, o si hay otro menú abierto, cerramos cualquier otro menú de prioridad/frecuencia que esté abierto
    window.hideAllOpenTaskMenus();

    const ops = document.createElement('div');
    ops.classList.add('opcionesPrioridad');
    ops.dataset.tareaMenuId = idOriginal; // Marcar el menú con el ID de la tarea
    ops.innerHTML = `
        <p data-prioridad="baja">${window.iconbaja || 'B'} baja</p>
        <p data-prioridad="media">${window.iconMedia || 'M'} media</p>
        <p data-prioridad="alta">${window.iconAlta || 'A'} alta</p>
        <p data-prioridad="importante">${window.iconimportante || 'I'} importante</p>
      `;
    liOriginal.after(ops); // Insertar el menú después del elemento de la tarea

    // Definir y guardar el manejador para poder removerlo
    window.cerrarMenuSiClicFueraPrioridadHandler = e => {
        // Si el clic NO es dentro del menú Y NO es en el botón que lo abrió
        if (!ops.contains(e.target) && !divPrioridadOriginal.contains(e.target)) {
            ops.remove();
            if (window.cerrarMenuSiClicFueraPrioridadHandler) {
                document.removeEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
                window.cerrarMenuSiClicFueraPrioridadHandler = null;
            }
        }
    };

    setTimeout(() => {
        // Añadir listener después del ciclo de evento actual
        document.addEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
    }, 0);

    const ps = ops.querySelectorAll('p');
    ps.forEach(p => {
        p.addEventListener('click', async evP => {
            // Renombrado event a evP
            evP.stopPropagation(); // Detener la propagación para clics en items del menú

            const prioSeleccionada = p.dataset.prioridad;
            const tareasSelActuales = tareasSeleccionadas || []; // Asegurarse que tareasSeleccionadas existe

            // console.log(`DEBUG cambiarPrioridad: idOriginal: "${idOriginal}", Prio: ${prioSeleccionada}, tareasSelActuales: ${JSON.stringify(tareasSelActuales)}, incluyeOriginal: ${tareasSelActuales.includes(idOriginal)}, longitud > 1: ${tareasSelActuales.length > 1}`);

            let logs = `cambiarPrioridad: Opción '${prioSeleccionada}' seleccionada para tarea original ${idOriginal}. `;

            let idsParaProcesar = [idOriginal];
            if (tareasSelActuales.length > 1 && tareasSelActuales.includes(idOriginal)) {
                idsParaProcesar = [...tareasSelActuales];
                logs += `Detectada seleccion multiple (${idsParaProcesar.length} tareas). `;
            } else {
                logs += `Accion individual. `;
            }

            // Cerrar menú y remover listener de clic fuera
            ops.remove();
            if (window.cerrarMenuSiClicFueraPrioridadHandler) {
                document.removeEventListener('click', window.cerrarMenuSiClicFueraPrioridadHandler);
                window.cerrarMenuSiClicFueraPrioridadHandler = null;
            }

            let logsFinales = logs;

            async function procesarUnaTarea(id, prio) {
                const data = {tareaId: id, prioridad: prio};
                try {
                    const rta = await enviarAjax('cambiarPrioridad', data);
                    if (rta.success) {
                        logsFinales += `Éxito AJAX para ${id}. Reiniciando post. `;
                        window.reiniciarPost(id, 'tarea');
                    } else {
                        let m = `Error AJAX para ${id}.`;
                        if (rta.data) m += ' Detalles: ' + rta.data;
                        logsFinales += m + ' ';
                    }
                } catch (err) {
                    logsFinales += `Excepcion AJAX para ${id}: ${err}. `;
                }
            }

            for (let i = 0; i < idsParaProcesar.length; i++) {
                const id = idsParaProcesar[i];
                await procesarUnaTarea(id, prioSeleccionada);
                if (idsParaProcesar.length > 1 && i < idsParaProcesar.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 300));
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
                                window.reiniciarPost(idActual, 'tarea');
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
                                window.reiniciarPost(idActual, 'tarea');
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

function iniciarManejadoresFechaLimiteMeta() {
    document.querySelectorAll('.divFechaLimite[data-tarea]').forEach(div => {
        const listenerExistente = div._manejadorClicFechaLimiteMeta;
        if (listenerExistente) div.removeEventListener('click', listenerExistente);

        div._manejadorClicFechaLimiteMeta = function (event) {
            event.stopPropagation();
            window.hideAllOpenTaskMenus();

            const tareaId = this.dataset.tarea;
            const liTarea = this.closest('.POST-tarea');
            const fechaActual = liTarea ? liTarea.dataset.fechalimite : null;
            const spanTexto = this.querySelector('.textoFechaLimite');

            contextoCalendario = {
                esParaTareaEspecifica: true,
                idTarea: tareaId,
                elementoSpanTexto: spanTexto,
                elementoLiTarea: liTarea,
                elementoDisparador: this,
                tipoFecha: 'limite' // Especificamos que es para fechaLimite
            };

            mostrarCal(this, fechaActual || null);
        };

        div.addEventListener('click', div._manejadorClicFechaLimiteMeta);
    });
}

async function actualizarFechaLimiteTareaServidorUI(idTarea, nuevaFechaISO, spanDelIconoDisparador, liTarea) {
    // spanDelIconoDisparador y liTarea no se usarán activamente si reinicias el post,
    // pero los mantenemos por si alguna lógica futura los necesita o para consistencia.
    const datos = {tareaId: idTarea, fechaLimite: nuevaFechaISO};
    let logBase = `actualizarFechaLimiteTareaServidorUI: Tarea ${idTarea}, `;
    logBase += nuevaFechaISO ? `FechaNueva "${nuevaFechaISO}"` : 'Fecha Borrada';

    try {
        const rta = await enviarAjax('modificarFechaLimiteTarea', datos);
        let logDetalles = '';

        if (rta.success) {
            logDetalles += 'Servidor OK. ';

            // No necesitamos actualizar el dataset del liTarea o el atributo 'dif' manualmente aquí,
            // porque reiniciarPost() obtendrá la información más reciente del servidor.
            // Tampoco necesitamos tocar el spanDelIconoDisparador ni el display de fecha real.

            // Llamamos a reiniciarPost para actualizar toda la tarea.
            // Asumimos que 'idTarea' es el mismo que se necesita para reiniciarPost.
            // Si window.reiniciarPost es asíncrono, puedes usar await.
            // Si es síncrono o no devuelve una promesa que necesitemos esperar, no hace falta await.
            await window.reiniciarPost(idTarea, 'tarea');
            logDetalles += `Se llamó a reiniciarPost(${idTarea}, 'tarea') para actualizar UI.`;

            console.log(logBase + '. ' + logDetalles);
        } else {
            logDetalles = `Error Servidor: ${rta.data || 'Desconocido'}`;
            console.error(logBase + '. ' + logDetalles);
            // Considera si quieres mostrar un alert aquí, ya que reiniciarPost no se llamará.
            alert('Error al actualizar fecha límite en servidor: ' + (rta.data || 'Error desconocido'));
        }
    } catch (error) {
        const logError = `Excepción AJAX. Error: ${error.message || error}`;
        console.error(logBase + '. ' + logError);
        alert('Error de conexión al actualizar fecha límite.');
    }
}

// NUEVA FUNCIÓN (JS): Equivalente a tu calcularTextoTiempo de PHP
function calcularTextoTiempoJS(fechaReferenciaISO) {
    // YYYY-MM-DD o null
    if (!fechaReferenciaISO) return {txt: '', simbolo: '', claseNeg: ''};

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0); // Normalizar a medianoche

    // Crear fechaReferencia también a medianoche para comparación correcta de días
    const [anio, mes, dia] = fechaReferenciaISO.split('-').map(Number);
    const fechaRef = new Date(anio, mes - 1, dia, 0, 0, 0, 0);

    const difMs = fechaRef.getTime() - hoy.getTime();
    const difDias = Math.round(difMs / (1000 * 60 * 60 * 24));

    let txt = '',
        simbolo = '',
        claseNeg = '';
    if (difDias === 0) txt = 'Hoy';
    else if (difDias === 1) txt = 'Mañana';
    else if (difDias === -1) {
        txt = 'Ayer';
        claseNeg = 'diaNegativo';
    } else if (difDias > 1) txt = difDias + 'd';
    else if (difDias < -1) {
        txt = Math.abs(difDias) + 'd';
        simbolo = '-';
        claseNeg = 'diaNegativo';
    }

    return {txt: txt, simbolo: simbolo, claseNeg: claseNeg};
}

function iniciarManejadoresFechaProximaHabito() {
    document.querySelectorAll('.divProxima[data-tarea]').forEach(div => {
        const listenerExistente = div._manejadorClicFechaProximaHabito;
        if (listenerExistente) div.removeEventListener('click', listenerExistente);

        div._manejadorClicFechaProximaHabito = function (event) {
            event.stopPropagation();
            window.hideAllOpenTaskMenus();

            const tareaId = this.dataset.tarea;
            const liTarea = this.closest('.POST-tarea');
            const fechaActual = liTarea ? liTarea.dataset.proxima : null;
            const spanTexto = this.querySelector('.textoProxima');

            contextoCalendario = {
                esParaTareaEspecifica: true,
                idTarea: tareaId,
                elementoSpanTexto: spanTexto,
                elementoLiTarea: liTarea,
                elementoDisparador: this,
                tipoFecha: 'proxima'
            };

            mostrarCal(this, fechaActual || null);
        };

        div.addEventListener('click', div._manejadorClicFechaProximaHabito);
    });
}

async function actualizarFechaProximaHabitoServidorUI(idTarea, nuevaFechaISO, spanTexto, liTarea) {
    const datos = {tareaId: idTarea, fechaProxima: nuevaFechaISO};
    console.log(`actualizarFechaProximaHabitoServidorUI: Enviando AJAX para tarea ${idTarea}, fecha próxima: ${nuevaFechaISO}`);

    try {
        // Asumimos que tendrás un endpoint PHP llamado 'modificarFechaProximaHabito'
        const rta = await enviarAjax('modificarFechaProximaHabito', datos);
        if (rta.success) {
            const tiempo = calcularTextoTiempoJS(nuevaFechaISO);
            if (spanTexto) {
                spanTexto.textContent = tiempo.simbolo + tiempo.txt;
                spanTexto.className = 'textoProxima ' + tiempo.claseNeg; // Asegúrate que la clase base es correcta
            }
            if (liTarea) {
                liTarea.dataset.proxima = nuevaFechaISO || '';
                const difDias = nuevaFechaISO ? Math.round((new Date(nuevaFechaISO + 'T00:00:00').getTime() - new Date(new Date().setHours(0, 0, 0, 0)).getTime()) / (1000 * 60 * 60 * 24)) : 0;
                liTarea.setAttribute('dif', difDias);
            }
            console.log(`actualizarFechaProximaHabitoServidorUI: Tarea ${idTarea} (próxima) actualizada a ${nuevaFechaISO || 'ninguna'}.`);
        } else {
            alert('Error al actualizar fecha próxima en servidor: ' + (rta.data || 'Error desconocido'));
            console.error(`actualizarFechaProximaHabitoServidorUI: Error AJAX para ${idTarea}`, rta);
        }
    } catch (error) {
        alert('Error de conexión al actualizar fecha próxima.');
        console.error(`actualizarFechaProximaHabitoServidorUI: Excepción AJAX para ${idTarea}`, error);
    }
}

/**
 * Función auxiliar para reiniciar una tarea y sus subtareas.
 * Asume que window.reiniciarPost(id, tipo) está disponible.
 * @param {string} idTareaPrincipal El ID de la tarea a reiniciar (potencialmente padre).
 */
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
