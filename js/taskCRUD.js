// js/taskCRUD.js

window.enviarTarea = function() {
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
window.pegarTareaHandler = function(ev) {
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

//te dejo un ejemplo correcto
window.enviarTareaHandler = function(ev) {
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

window.editarTarea = function() {
    const tareas = document.querySelectorAll('.tituloTarea');

    tareas.forEach(tarea => {
        // Verifica si la tarea ya tiene un event listener agregado
        if (!tarea.dataset.eventoAgregado) {
            tarea.addEventListener('click', manejarEditarTarea);
            tarea.dataset.eventoAgregado = 'true';
        }
    });
}

window.manejarEditarTarea = function(ev) {
   ev.preventDefault();
    const tarea = this; // 'this' se refiere al elemento .tituloTarea que disparó el evento
    const id = tarea.dataset.tarea; // Asegúrate que el dataset 'tarea' tenga el ID
    let valorAnt = tarea.textContent.trim();
    tarea.contentEditable = true;
    tarea.spellcheck = false; // Ya lo haces, pero bueno reconfirmar
    tarea.focus();

    // Aquí puedes capturar el valor anterior si lo necesitas para 'guardarEdicion'
    // tarea.dataset.valorAnterior = valorAnt; // Si guardarEdicion lo fuera a usar del dataset

    const off = calcularPosicionCursor(ev, tarea);
    setCursorPos(tarea, off);

    const salirEdicion = () => {
        // CAMBIO: Si hay un temporizador de input pendiente para este elemento, cancelarlo.
        if (tarea._temporizadorGuardado) {
            clearTimeout(tarea._temporizadorGuardado);
            delete tarea._temporizadorGuardado; // Limpiar la propiedad del elemento
        }

        const textoActual = tarea.textContent.trim(); // Obtener el texto actual una vez

        if (textoActual.length > 300) {
            alert('El titulo no puede superar los 300 caracteres.');
            tarea.textContent = valorAnt; // Revertir al valor anterior
        } else if (textoActual !== '' && textoActual !== valorAnt) {
            // Solo guardar si el texto no está vacío Y es diferente del valor anterior.
            guardarEdicion(tarea, id, valorAnt); // valorAnt es el que tenía al iniciar la edición
        }
        // Si textoActual === '', no se guarda, y la tarea queda vacía (hasta que 'borrarTareaVacia' actúe en Backspace)
        // Si textoActual === valorAnt, no se guarda porque no hubo cambios.

        tarea.contentEditable = false;
        // Remover los event listeners después de usarlos
        tarea.removeEventListener('blur', salirEdicion);
        // 'paste' listener también se añade aquí, ¿debería removerse aquí o es persistente?
        // Si se añade cada vez que se hace clic, debe removerse.
        // La lógica actual de `manejarPegado` parece independiente del ciclo de `salirEdicion`.
        // Si `manejarPegado` se añade solo una vez por `editarTarea` y `editarTarea` se llama múltiples veces,
        // entonces también necesitaría lógica para no duplicarse o removerse.
        // Por ahora, sigo tu código original para `paste`.
        // Si `manejarPegado` se añade en `manejarEditarTarea` como `tarea.addEventListener('paste', manejarPegado);`
        // entonces sí, debería removerse: `tarea.removeEventListener('paste', manejarPegado);`
    };

    const manejarPegado = ev => { // Asumo que esta función se define aquí dentro o es accesible.
        ev.preventDefault();
        const texto = (ev.clipboardData || window.clipboardData).getData('text/plain').trim();
        // Prevenir que el pegado exceda los 300 caracteres totales
        const textoActualEnCampo = tarea.textContent.trim();
        const caracteresRestantes = 300 - textoActualEnCampo.length;
        const textoAPegar = texto.substring(0, Math.max(0, caracteresRestantes)); // Tomar solo lo que cabe

        if (textoAPegar.length > 0) {
            document.execCommand('insertText', false, textoAPegar);
        }
        // Si se pegó más de lo que cabía, se podría notificar al usuario.
    };

    // Asegurarse de no duplicar listeners si manejarEditarTarea se llamara múltiples veces en el mismo elemento sin limpiar.
    // Tu código original en editarTarea() ya verifica !tarea.dataset.eventoAgregado,
    // lo que previene añadir el listener de 'click' múltiples veces.
    // Los listeners de 'blur' y 'paste' se añaden *dentro* del handler de 'click',
    // y 'blur' se remueve a sí mismo, lo cual está bien.
    tarea.addEventListener('blur', salirEdicion);
    tarea.addEventListener('paste', manejarPegado); // Si este listener es temporal para esta sesión de edición
}


window.guardarEdicion = function(t, id, valorAnt) {
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

//se que esto oculta la tarea cuando el filtro esta activado pero, no la tiene que ocultar, sino eliminar del dom
window.completarTarea = function() {
    document.querySelectorAll('.completaTarea').forEach(boton => {
        // Remover listener anterior para evitar duplicados si se llama multiples veces
        boton.removeEventListener('click', manejarClicCompletar);
        // Agregar el nuevo listener
        boton.addEventListener('click', manejarClicCompletar);
        // No es necesario el dataset de eventoAgregado si siempre removemos y agregamos
    });
}

window.manejarClicCompletar = function() {
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

window.archivarTarea = function() {
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

// This is correcting the subTarea function which was missed in the previous bulk update.
// The following function 'borrarTareasCompletadas' is already correctly window prefixed.
// The actual function to modify is 'subTarea' which was previously located before 'window.borrarTareasCompletadas'
// and was not prefixed.
// However, the previous read_file output for js/taskCRUD.js was not perfectly reflecting the state
// after the subTarea duplication and fix.
// The goal is to ensure 'subTarea' becomes 'window.subTarea'.
// Based on the latest read_files output, 'subTarea' is not present.
// This means the previous fix for subTarea duplication actually removed it instead of prefixing one.
// I will add window.subTarea now.
// The following SEARCH block is targeting the line before where window.borrarTareasCompletadas starts
// to insert window.subTarea before it.

window.subTarea = function() {
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
};

window.borrarTareasCompletadas = async function() {
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

window.borrarTareaVacia = function() {
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

window.borrarTareasCompletadas = async function() {
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
