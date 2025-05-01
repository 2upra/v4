let importancia = {
    selector: null,
    valor: 'media'
};

let tipoTarea = {
    selector: null,
    valor: 'una vez'
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
        .split('')
        .map(linea => linea.trim())
        .filter(linea => linea);
    const maxTareas = 30;
    const lineasProcesadas = lineas.slice(0, maxTareas);
    const tit = document.getElementById('tituloTarea');
    const listaTareas = document.querySelector('.tab.active .social-post-list.clase-tarea');

    if (lineasProcesadas.some(linea => linea.length > 140)) {
        alert('Ningun titulo puede superar los 140 caracteres.');
        return;
    }

    const promesas = lineasProcesadas.map(titulo => {
        return enviarAjax('crearTarea', {
            titulo: titulo,
            importancia: importancia.valor,
            tipo: tipoTarea.valor
        });
    });

    Promise.all(promesas)
        .then(async respuestas => {
            let todasExitosas = respuestas.every(rta => rta.success);
            let log = 'pegarTareaHandler: ';

            if (todasExitosas) {
                log += `Tareas creadas exitosamente. Se procesaron ${lineasProcesadas.length} tareas.`;
                tit.value = '';

                for (const rta of respuestas) {
                    if (rta.data && rta.data.tareaId) {
                        const tareaNueva = await window.reiniciarPost(rta.data.tareaId, 'tarea');

                        if (tareaNueva && listaTareas) {
                            const primerDivisor = listaTareas.querySelector('.divisorTarea');

                            if (primerDivisor) {
                                primerDivisor.insertAdjacentHTML('afterend', tareaNueva);
                            } else {
                                listaTareas.insertAdjacentHTML('afterbegin', tareaNueva);
                            }
                        } else {
                            log += `
 No se pudo agregar la tarea con ID ${rta.data.tareaId} a la lista.`;
                        }
                    }
                }

                initTareas();
                window.guardarOrden();
            } else {
                respuestas.forEach((rta, index) => {
                    if (!rta.success) {
                        log += `
 Error al crear la tarea "${lineasProcesadas[index]}". Detalles: ${rta.data || 'Sin detalles'}`;
                    }
                });
            }
            console.log(log);
        })
        .catch(err => {
            console.error('pegarTareaHandler: Error al crear tareas:', err);
            alert('Error al crear tareas. Revisa la consola.');
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

            if (tit.value.length > 140) {
                alert('El titulo no puede superar los 140 caracteres.');
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
        if (tarea.textContent.trim().length > 180) {
            alert('El titulo no puede superar los 180 caracteres.');
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
        const nuevoTexto = texto.substring(0, 180 - tarea.textContent.trim().length);
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

/*
las tareas tienen un dif, y es importante tomarlo en cuenta cuando el tipo-tarea es habito o habito rigido, 
<li class="POST-tarea EDYQHV 472  draggable-element pendiente " filtro="tarea" tipo-tarea="habito rigido" id-post="472" autor="1" draggable="true" sesion="general" estado="pendiente" impnum="4" importancia="importante" subtarea="false" padre="" dif="-1" data-submenu-initialized="true" data-seccion="General">

prioridadTarea funciona muy bien pero tiene que tener en cuenta el dif, el dif simplemente es el tiempo que se tiene para completar una tarea, mientras menor sea el numero (tambien hay negativo), mas arriba, asi que, si, tiene que mantenerse la prioridad por el impnum pero dentro de los impnum, si es un habito, primero van los que tienen menor dif
*/

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
        div.addEventListener('click', async function () {
            const divClicado = this;
            const tarea = divClicado.closest('.draggable-element');
            const tareaId = divClicado.dataset.tarea;
            const ul = document.querySelector('.social-post-list.clase-tarea');
            const pGeneral = document.querySelector('p.divisorTarea.General');
            let data = {id: tareaId};
            let logs = '';

            if (tarea.classList.contains('archivado')) {
                data.desarchivar = true;
                logs += `Tarea ${tareaId} estaba archivada. Se va a desarchivar. `;
            }

            try {
                const respuesta = await enviarAjax('archivarTarea', data);
                if (respuesta.success) {
                    if (data.desarchivar) {
                        tarea.classList.remove('archivado');
                        tarea.setAttribute('estado', '');
                        if (pGeneral) {
                            pGeneral.after(tarea);
                        }
                        logs += `Tarea ${tareaId} desarchivada y movida. `;
                    } else {
                        tarea.classList.add('archivado');
                        tarea.setAttribute('estado', 'archivado');
                        if (ul) {
                            ul.appendChild(tarea);
                        }
                        logs += `Tarea ${tareaId} archivada y movida al final del ul. `;
                    }
                    console.log(logs);
                } else {
                    let mensaje = 'Error al archivar la tarea.';
                    if (respuesta.data) mensaje += ' Detalles: ' + respuesta.data;
                    console.error(mensaje);
                    alert(mensaje);
                }
            } catch (error) {
                console.error('Error al archivar la tarea:', error);
                alert('Error al archivar la tarea.');
            }
        });
    });
}

//se que esto oculta la tarea cuando el filtro esta activado pero, no la tiene que ocultar, sino eliminar del dom
function completarTarea() {
    document.querySelectorAll('.completaTarea').forEach(boton => {
        if (!boton.dataset.eventoAgregado) {
            boton.addEventListener('click', manejarClicCompletar);
            boton.dataset.eventoAgregado = 'true';
        }
    });
}

function manejarClicCompletar() {
    let log = '';
    const botonClicado = this;
    const tarea = botonClicado.closest('.draggable-element');
    const tareaId = botonClicado.dataset.tarea;
    const dat = {id: tareaId};
    const estado = tarea.classList.contains('completada') ? 'pendiente' : 'completada';
    dat.estado = estado;
    const esHabito = botonClicado.classList.contains('habito');
    const esHabitoFlexible = botonClicado.classList.contains('habitoFlexible');

    enviarAjax('completarTarea', dat)
        .then(rta => {
            if (rta.success) {
                if (estado === 'completada') {
                    if (!esHabito && !esHabitoFlexible) {
                        tarea.classList.add('completada');
                        tarea.style.textDecoration = 'line-through';
                    }

                    if (window.filtrosGlobales && window.filtrosGlobales.includes('ocultarCompletadas') && !esHabito) {
                        tarea.remove();
                    } else if (esHabito || esHabitoFlexible) {
                        log += 'Reinicio de tarea porque es habito o habito flexible';
                        window.reiniciarPost(tareaId, 'tarea');
                    }
                } else {
                    tarea.classList.remove('completada');
                    tarea.style.textDecoration = 'none';
                    tarea.style.display = '';
                }
            } else {
                let m = 'Error al cambiar el estado de la tarea.';
                if (rta.data) m += ' Detalles: ' + rta.data;
                alert(m);
            }
        })
        .catch(err => {
            alert('Error al completar la tarea.');
        });
}

function cambiarFrecuencia() {
    const divs = document.querySelectorAll('.divFrecuencia');
    divs.forEach(div => {
        div.addEventListener('click', () => {
            const id = div.dataset.tarea;
            const li = document.querySelector(`.POST-tarea[id-post="${id}"]`);
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
            const menu = li.nextElementSibling;
            if (menu && menu.classList.contains('opcionesFrecuencia')) {
                menu.remove();
            } else {
                li.after(ops);
            }

            const ps = ops.querySelectorAll('p:not([data-frecuencia="personalizada"])');
            ps.forEach(p => {
                p.addEventListener('click', () => {
                    const frec = p.dataset.frecuencia;
                    const data = {
                        tareaId: id,
                        frecuencia: parseInt(frec)
                    };
                    actualizarFrecuencia(data, div);
                    ops.remove();
                });
            });

            const btn = ops.querySelector('#btnPersonalizar');
            btn.addEventListener('click', () => {
                const input = ops.querySelector('#diasPersonalizados');
                const dias = parseInt(input.value);
                if (dias >= 2 && dias <= 365) {
                    const data = {
                        tareaId: id,
                        frecuencia: dias
                    };
                    actualizarFrecuencia(data, div);
                    ops.remove();
                }
            });
        });
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
    const divs = document.querySelectorAll('.divImportancia');
    divs.forEach(div => {
        // Verifica si el div ya tiene un event listener agregado
        if (!div.dataset.eventoAgregado) {
            div.addEventListener('click', manejarClicPrioridad);
            div.dataset.eventoAgregado = 'true';
        }
    });
}

function manejarClicPrioridad() {
    const div = this;
    const id = div.dataset.tarea;
    const li = document.querySelector(`.POST-tarea[id-post="${id}"]`);
    const ops = document.createElement('div');
    ops.classList.add('opcionesPrioridad');
    ops.innerHTML = `
    <p data-prioridad="baja">${window.iconbaja} baja</p>
    <p data-prioridad="media">${window.iconMedia} media</p>
    <p data-prioridad="alta">${window.iconAlta} alta</p>
    <p data-prioridad="importante">${window.iconimportante} importante</p>
  `;
    const menu = li.nextElementSibling;
    if (menu && menu.classList.contains('opcionesPrioridad')) {
        menu.remove();
    } else {
        li.after(ops);
    }

    const ps = ops.querySelectorAll('p');
    ps.forEach(p => {
        p.addEventListener('click', () => {
            const prio = p.dataset.prioridad;
            const data = {
                tareaId: id,
                prioridad: prio
            };

            enviarAjax('cambiarPrioridad', data)
                .then(rta => {
                    if (rta.success) {
                        ops.remove();
                        window.reiniciarPost(id, 'tarea');
                    } else {
                        let m = 'Error al cambiar la prioridad de la tarea.';
                        if (rta.data) m += ' Detalles: ' + rta.data;
                        alert(m);
                    }
                })
                .catch(err => {
                    alert('Error al cambiar la prioridad.');
                });
        });
    });
}

async function borrarTareasCompletadas() {
    const boton = document.querySelector('.borrarTareasCompletadas');
    let limpiar = true;

    async function handleClick() {
        const confirmado = await confirm('Â¿Estas seguro de que quieres borrar todas las tareas completadas?');

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

function subTarea() {
    const listaTareas = document.querySelector('.clase-tarea');

    listaTareas.addEventListener('keydown', event => {
        const elementoActual = document.activeElement;

        if (elementoActual.classList.contains('tituloTarea') && elementoActual.isContentEditable) {
            const tareaActual = elementoActual.closest('.POST-tarea');
            const idTareaActual = tareaActual.getAttribute('id-post');

            if (event.shiftKey && event.key === 'Tab') {
                event.preventDefault();
                if (tareaActual.classList.contains('subtarea')) {
                    tareaActual.classList.remove('subtarea');

                    const data = {
                        id: idTareaActual,
                        subtarea: false
                    };

                    enviarAjax('crearSubtarea', data)
                        .then(res => {
                            console.log('Subtarea eliminada exitosamente');
                        })
                        .catch(error => {
                            console.error('Error al eliminar subtarea:', error);
                        });
                }
            } else if (event.key === 'Tab') {
                event.preventDefault();

                const tareaAnterior = tareaActual.previousElementSibling;
                if (tareaAnterior && tareaAnterior.classList.contains('POST-tarea')) {
                    tareaActual.classList.add('subtarea');

                    const idTareaAnterior = tareaAnterior.getAttribute('id-post');

                    const data = {
                        id: idTareaActual,
                        padre: idTareaAnterior,
                        subtarea: true
                    };

                    enviarAjax('crearSubtarea', data)
                        .then(res => {
                            console.log('Subtarea creada exitosamente');
                        })
                        .catch(error => {
                            console.error('Error al crear subtarea:', error);
                        });
                }
            }
        }
    });
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
