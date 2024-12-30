let importancia = {
    selector: null,
    valor: 'media'
};

let tipoTarea = {
    selector: null,
    valor: 'una vez'
};

function enviarTareaHandler(ev) {
    const tit = document.getElementById('tituloTarea');
    let enviando = false;

    if (ev.key === 'Enter' && !enviando) {
        if (tit.value.length > 140) {
            alert('El título no puede superar los 140 caracteres.');
            return;
        }

        enviando = true;
        const data = {
            titulo: tit.value,
            importancia: importancia.valor,
            tipo: tipoTarea.valor
        };

        enviarAjax('crearTarea', data)
            .then(rta => {
                if (rta.success) {
                    alert('Tarea creada.');
                    tit.value = '';
                    limpiar = false;
                    arriba = true;
                    window.reiniciarContenido(limpiar, arriba, 'tarea');
                } else {
                    let m = 'Error al crear tarea.';
                    if (rta.data) {
                        m += ' Detalles: ' + rta.data;
                    }
                    alert(m);
                }
            })
            .catch(err => {
                alert('Error al crear. Revisa la consola.');
                console.error(err);
            })
            .finally(() => {
                enviando = false;
            });
    }
}

function initTareas() {
    const tit = document.getElementById('tituloTarea');

    if (tit) {
        selectorTipoTarea();
        enviarTarea();
        editarTarea();
        moverTarea();
        completarTarea();
        cambiarPrioridad();
        prioridadTarea();
        borrarTareasCompletadas();
        cambiarFrecuencia();
        archivarTarea();
    }
}
function enviarTarea() {
    const tit = document.getElementById('tituloTarea');
    tit.removeEventListener('keyup', enviarTareaHandler);
    tit.addEventListener('keyup', enviarTareaHandler);
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

    tareas.forEach(t => {
        t.addEventListener('click', ev => {
            ev.preventDefault();
            const id = t.dataset.tarea;
            let valorAnt = t.textContent.trim();
            t.contentEditable = true;
            t.spellcheck = false;

            const off = calcularPosicionCursor(ev, t);
            setCursorPos(t, off);

            const presionarEnter = ev => {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    if (t.textContent.trim().length > 140) {
                        alert('El título no puede superar los 140 caracteres.');
                        t.textContent = valorAnt;
                        return;
                    }
                    guardarEdicion(t, id, valorAnt);
                }
            };

            const salirEdicion = () => {
                if (t.textContent.trim().length > 140) {
                    alert('El título no puede superar los 140 caracteres.');
                    t.textContent = valorAnt;
                } else {
                    guardarEdicion(t, id, valorAnt);
                }
            };

            t.addEventListener('keydown', presionarEnter);
            t.addEventListener('blur', salirEdicion);

            t.addEventListener('paste', ev => {
                ev.preventDefault();
                const texto = ev.clipboardData.getData('text/plain').trim();
                const nuevoTexto = texto.substring(0, 140 - t.textContent.trim().length);
                document.execCommand('insertText', false, nuevoTexto);
            });
        });
    });
}

function guardarEdicion(t, id, valorAnt) {
    const valorNuevo = t.textContent.trim();

    if (valorAnt !== valorNuevo) {
        t.contentEditable = false;
        t.style.outline = 'none';
        t.style.border = 'none';
        t.style.boxShadow = 'none';
        const dat = {id, titulo: valorNuevo};
        enviarAjax('modificarTarea', dat)
            .then(rta => {
                if (!rta.success) {
                    t.textContent = valorAnt;
                    let m = 'Error al modificar.';
                    if (rta.data) m += ' Detalles: ' + rta.data;
                    alert(m);
                } else {
                    alert('Título modificado con éxito.');
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

function moverTarea() {
    const lista = document.querySelector('.clase-tarea');
    if (!lista) return;

    if (lista.listenersAdded) return;
    lista.listenersAdded = true;

    let arrastrandoElem = null;
    let ordenViejo = [];
    let idTareaArrastrada = null;
    let posInicialY = null;
    const tolerancia = 10; // Ajusta este valor según sea necesario, es la sensibilidad
    let movimientoRealizado = false; // para verificar si se realizó un movimiento real

    const iniciarArrastre = ev => {
        const elem = ev.target.closest('.draggable-element');
        if (!elem) return;

        arrastrandoElem = elem;
        idTareaArrastrada = elem.getAttribute('id-post');
        ordenViejo = Array.from(lista.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
        posInicialY = ev.clientY;
        movimientoRealizado = false;

        arrastrandoElem.classList.add('dragging');
        document.body.classList.add('dragging-active');

        lista.addEventListener('mousemove', duranteArrastre);
        lista.addEventListener('mouseup', terminarArrastre);
    };

    const duranteArrastre = ev => {
        if (!arrastrandoElem) return;
        ev.preventDefault();

        const mouseY = ev.clientY;

        // Verificar si el movimiento supera la tolerancia
        if (!movimientoRealizado && Math.abs(mouseY - posInicialY) > tolerancia) {
            movimientoRealizado = true;
        }

        let anterior = arrastrandoElem.previousElementSibling;
        let siguiente = arrastrandoElem.nextElementSibling;

        while (anterior && !anterior.classList.contains('draggable-element')) {
            anterior = anterior.previousElementSibling;
        }

        while (siguiente && !siguiente.classList.contains('draggable-element')) {
            siguiente = siguiente.nextElementSibling;
        }

        if (anterior) {
            const rectAnt = anterior.getBoundingClientRect();
            if (mouseY < rectAnt.top + rectAnt.height / 2) {
                lista.insertBefore(arrastrandoElem, anterior);
                return;
            }
        }

        if (siguiente) {
            const rectSig = siguiente.getBoundingClientRect();
            if (mouseY > rectSig.top + rectSig.height / 2) {
                lista.insertBefore(arrastrandoElem, siguiente.nextSibling);
                return;
            }
        }
    };

    const terminarArrastre = () => {
        if (!arrastrandoElem) return;

        const ordenNuevo = Array.from(lista.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
        const nuevaPosicion = ordenNuevo.indexOf(idTareaArrastrada);

        // Guardar el orden solo si se realizó un movimiento real
        if (movimientoRealizado && ordenViejo.join(',') !== ordenNuevo.join(',')) {
            let log = 'Guardando orden:';
            log += `\n  Tarea movida: ${idTareaArrastrada}`;
            log += `\n  Nueva posición: ${nuevaPosicion}`;
            log += `\n  Orden nuevo: ${ordenNuevo}`;
            console.log(log);

            guardarOrdenTareas({
                idTareaMovida: idTareaArrastrada,
                nuevaPosicion: nuevaPosicion,
                ordenNuevo: ordenNuevo
            });
        }

        arrastrandoElem.classList.remove('dragging');
        document.body.classList.remove('dragging-active');
        lista.removeEventListener('mousemove', duranteArrastre);
        lista.removeEventListener('mouseup', terminarArrastre);

        arrastrandoElem = null;
        idTareaArrastrada = null;
        ordenViejo = [];
        posInicialY = null;
        movimientoRealizado = false;
    };

    lista.addEventListener('mousedown', ev => {
        if (ev.target.closest('.draggable-element')) iniciarArrastre(ev);
    });

    lista.addEventListener('dragstart', ev => {
        ev.preventDefault();
    });
}

function guardarOrdenTareas({idTareaMovida, nuevaPosicion, ordenNuevo}) {
    const log = `Guardando orden:\n  Tarea movida: ${idTareaMovida}\n  Nueva posición: ${nuevaPosicion}\n  Orden nuevo: ${ordenNuevo}`;
    console.log(log);

    enviarAjax('actualizarOrdenTareas', {tareaMovida: idTareaMovida, nuevaPosicion, ordenNuevo})
        .then(res => {
            if (res && res.success) {
                console.log('Orden de tareas actualizado exitosamente.');
            } else {
                console.error('Error al actualizar el orden de tareas:', res ? res.data.error : 'Respuesta vacía o success: false');
            }
        })
        .catch(err => {
            console.error('Error en la petición AJAX:', err);
        });
}

function prioridadTarea() {
    const botonPrioridad = document.querySelector('.prioridadTareas');

    botonPrioridad.addEventListener('click', () => {
        const limpiar = true;
        const arriba = false;
        const clase = 'tarea';
        const prioridad = true;
        window.reiniciarContenido(limpiar, arriba, clase, prioridad);
    });
}

function archivarTarea() {
    document.querySelectorAll('.divArchivado').forEach(div => {
        div.addEventListener('click', async function() {
            const divClicado = this;
            const tareaId = divClicado.dataset.tarea;
            const data = { id: tareaId };

            try {
                const respuesta = await enviarAjax('archivarTarea', data);
                if (respuesta.success) {
                    const tarea = divClicado.closest('.draggable-element'); 
                    tarea.style.display = 'none';
                    console.log(`Tarea ${tareaId} archivada y eliminada del DOM.`);
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

function completarTarea() {
    document.querySelectorAll('.completaTarea').forEach(boton => {
        boton.addEventListener('click', function () {
            let log = '';
            const botonClicado = this;
            const tarea = botonClicado.closest('.draggable-element');
            const tareaId = botonClicado.dataset.tarea;
            const dat = { id: tareaId };
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
                                tarea.style.display = 'none';
                            }

                            if (esHabito || esHabitoFlexible) {
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
        });
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
                    <input type="number" id="diasPersonalizados" min="2" max="365" placeholder="Cada X días">
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
        div.addEventListener('click', () => {
            const id = div.dataset.tarea;
            const li = document.querySelector(`.POST-tarea[id-post="${id}"]`);
            const ops = document.createElement('div');
            ops.classList.add('opcionesPrioridad');
            ops.innerHTML = `
          <p data-prioridad="baja">${window.iconbaja} baja</p>
          <p data-prioridad="media">${window.iconMedia} Media</p>
          <p data-prioridad="alta">${window.iconAlta} Alta</p>
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
                    enviarAjax('cambiarPrioridad', data);
                    ops.remove();
                    const padre = div.querySelector('.importanciaTarea');
                    let span = padre.querySelector('.tituloImportancia');
                    const svg = padre.querySelector('svg');
                    if (svg) {
                        if (prio === 'baja') {
                            if (!span) {
                                span = document.createElement('span');
                                span.classList.add('tituloImportancia');
                                padre.appendChild(span);
                            }
                            span.textContent = 'baja';
                            svg.outerHTML = window.iconbaja;
                        } else if (prio === 'media') {
                            if (!span) {
                                span = document.createElement('span');
                                span.classList.add('tituloImportancia');
                                padre.appendChild(span);
                            }
                            span.textContent = 'media';
                            svg.outerHTML = window.iconMedia;
                        } else if (prio === 'alta') {
                            if (!span) {
                                span = document.createElement('span');
                                span.classList.add('tituloImportancia');
                                padre.appendChild(span);
                            }
                            span.textContent = 'alta';
                            svg.outerHTML = window.iconAlta;
                        } else if (prio === 'importante') {
                            if (!span) {
                                span = document.createElement('span');
                                span.classList.add('tituloImportancia');
                                padre.appendChild(span);
                            }
                            span.textContent = 'importante';
                            svg.outerHTML = window.iconimportante;
                        }
                    } else {
                        console.error('No se encontraron los elementos .tituloImportancia o svg dentro del div');
                    }
                });
            });
        });
    });
}

async function borrarTareasCompletadas() {
    const boton = document.querySelector('.borrarTareasCompletadas');
    let limpiar = true;

    async function handleClick() {
        const confirmado = await confirm('¿Estás seguro de que quieres borrar todas las tareas completadas?');

        if (confirmado) {
            const data = {
                limpiar: true
            };

            try {
                await enviarAjax('borrarTareasCompletadas', data);
                console.log('Tareas completadas borradas exitosamente');
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


