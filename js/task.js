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
        moverTarea();
        completarTarea();
        cambiarPrioridad();
        prioridadTarea();
        borrarTareasCompletadas();
        cambiarFrecuencia();
        archivarTarea();
        ocultarBotones();
        borrarTareaVacia();
        crearTareaEnter();
        organizarSecciones();
        crearSesionFront();
        hacerDivisoresEditables();
        window.dividirTareas();
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
    const tit = document.getElementById('tituloTarea');
    tit.removeEventListener('keyup', enviarTareaHandler);
    tit.addEventListener('keyup', enviarTareaHandler);
    tit.removeEventListener('paste', pegarTareaHandler);
    tit.addEventListener('paste', pegarTareaHandler);
    tit.addEventListener('input', () => {
        tit.value = tit.value.replace(/[\r\n\v]+/g, '');
    });
}

function pegarTareaHandler(ev) {
    ev.preventDefault();
    const textoPegado = (ev.clipboardData || window.clipboardData).getData('text');
    const lineas = textoPegado.split('\n');
    const maxTareas = 30;
    const lineasProcesadas = lineas.slice(0, maxTareas);
    const tit = document.getElementById('tituloTarea');
    let enviando = false;

    const promesas = lineasProcesadas.map(linea => {
        const titulo = linea.trim();
        if (titulo.length > 140) {
            alert('El título no puede superar los 140 caracteres.');
            return;
        }
        if (titulo) {
            return enviarAjax('crearTarea', {
                titulo: titulo,
                importancia: importancia.valor,
                tipo: tipoTarea.valor
            });
        }
        return Promise.resolve(null);
    });

    enviando = true;
    Promise.all(promesas)
        .then(respuestas => {
            let todasExitosas = true;
            let mensajeError = '';

            respuestas.forEach(rta => {
                if (rta && !rta.success) {
                    todasExitosas = false;
                    mensajeError += rta.data ? ' Detalles: ' + rta.data : '';
                }
            });

            if (todasExitosas) {
                alert(`Tareas creadas. Se procesaron ${lineasProcesadas.length} tareas.`);
                tit.value = '';
                limpiar = false;
                arriba = true;
                window.reiniciarContenido(limpiar, arriba, 'tarea');
                initTareas();
            } else {
                alert('Error al crear algunas tareas.' + mensajeError);
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
                    initTareas();
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

/*
esto funciona bien pero, cada vez que se crea una tarea, supongo que se reinicia, cosa que no esta mal, pero, la tarea se duplica visualmente por cada reinicio o mejor dichi por cada tarea creada
*/

function crearTareaEnter() {
    const contenedor = document.querySelector('.clase-tarea'); // Asumiendo que hay un contenedor para tus tareas

    contenedor.addEventListener('keydown', ev => {
        if (ev.target.classList.contains('tituloTarea') && ev.key === 'Enter' && ev.target.contentEditable === 'true') {
            ev.preventDefault();
            const tarea = ev.target.closest('.POST-tarea');
            const nuevaTarea = tarea.cloneNode(true);
            const nuevoTitulo = nuevaTarea.querySelector('.tituloTarea');
            nuevoTitulo.textContent = '';
            nuevoTitulo.dataset.tarea = 0;
            nuevaTarea.id = '';
            tarea.after(nuevaTarea);

            let valorAntiguo = '';
            let seCancelo = false;

            nuevoTitulo.contentEditable = true;
            nuevoTitulo.spellcheck = false;
            nuevoTitulo.focus();
            nuevoTitulo.setAttribute('placeholder', 'Nueva tarea');

            const rango = document.createRange();
            const seleccion = window.getSelection();
            rango.selectNodeContents(nuevoTitulo);
            rango.collapse(false);
            seleccion.removeAllRanges();
            seleccion.addRange(rango);

            const borrarTarea = () => {
                if (nuevaTarea && nuevaTarea.parentNode) {
                    nuevaTarea.remove();
                    seCancelo = true;
                }
            };

            nuevoTitulo.addEventListener('keydown', ev => {
                if (ev.key === 'Backspace' && nuevoTitulo.textContent === '') {
                    borrarTarea();
                }
            });

            const guardarNuevaTarea = titulo => {
                if (seCancelo) return;

                const valorNuevo = titulo.textContent.trim();

                if (valorAntiguo !== valorNuevo && valorNuevo !== '') {
                    titulo.contentEditable = false;
                    titulo.removeAttribute('placeholder');
                    const dat = { id: 0, titulo: valorNuevo };

                    enviarAjax('modificarTarea', dat)
                        .then(rta => {
                            let log = '';
                            if (rta.success) {
                                log = 'Tarea creada con éxito. ID: ' + rta.data.id;
                                valorAntiguo = valorNuevo;

                                const nuevaTareaCreada = titulo.closest('.POST-tarea');
                                nuevaTareaCreada.id = 'tarea-' + rta.data.id;
                                titulo.dataset.tarea = rta.data.id;

                                editarTarea();
                            } else {
                                titulo.textContent = valorAntiguo;
                                log = 'Error al crear tarea.';
                                if (rta.data) log += ' Detalles: ' + rta.data;
                            }
                            console.log(log);
                        })
                        .catch(err => {
                            titulo.textContent = valorAntiguo;
                            console.log('Error al crear tarea.');
                        });
                } else if (valorNuevo === '') {
                    borrarTarea();
                }
            };

            nuevoTitulo.addEventListener('blur', () => {
                setTimeout(() => {
                    if (!nuevoTitulo.matches(':focus')) {
                        guardarNuevaTarea(nuevoTitulo);
                    }
                }, 100);
            });
        }
    });
}

function borrarTareaVacia() {
    const tareas = document.querySelectorAll('.tituloTarea');

    tareas.forEach(tarea => {
        let borrar = false;

        tarea.addEventListener('keydown', ev => {
            if (ev.key === 'Backspace' && tarea.textContent.trim() === '') {
                if (borrar) {
                    const id = tarea.dataset.tarea;
                    const tareaCompleta = tarea.closest('.POST-tarea');

                    // Eliminar event listeners antes de remover la tarea
                    tarea.removeEventListener('input', tarea.onInput);
                    tarea.removeEventListener('blur', tarea.onBlur);
                    tarea.removeEventListener('paste', tarea.onPaste);

                    tareaCompleta.remove();

                    let log = 'Se borró la tarea con ID: ' + id;

                    const data = {
                        id: id
                    };
                    enviarAjax('borrarTarea', data)
                        .then(resp => {
                            log += ', \n  Respuesta recibida: ' + resp;
                            console.log(log);
                        })
                        .catch(error => {
                            log += ', \n  Error: ' + error;
                            console.error(log);
                        });
                } else {
                    borrar = true;
                }
            } else {
                borrar = false;
            }
        });
    });
}

function editarTarea() {
    const tareas = document.querySelectorAll('.tituloTarea');
    let timeoutId = null;

    tareas.forEach(t => {
        t.addEventListener('click', ev => {
            ev.preventDefault();
            const id = t.dataset.tarea;
            let valorAnt = t.textContent.trim();
            t.contentEditable = true;
            t.spellcheck = false;
            t.focus();

            const off = calcularPosicionCursor(ev, t);
            setCursorPos(t, off);

            const salirEdicion = () => {
                if (t.textContent.trim().length > 140) {
                    alert('El título no puede superar los 140 caracteres.');
                    t.textContent = valorAnt;
                    t.contentEditable = false;
                    t.removeEventListener('input', t.onInput);
                    t.removeEventListener('blur', t.onBlur);
                } else if (t.textContent.trim() !== '' && t.textContent.trim() !== valorAnt) {
                    // Condición adicional
                    guardarEdicion(t, id, valorAnt);
                    t.contentEditable = false;
                    t.removeEventListener('input', t.onInput);
                    t.removeEventListener('blur', t.onBlur);
                } else {
                    t.contentEditable = false;
                    t.removeEventListener('input', t.onInput);
                    t.removeEventListener('blur', t.onBlur);
                }
            };

            t.onInput = () => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    salirEdicion();
                }, 6000);
            };

            t.onBlur = () => {
                setTimeout(() => {
                    if (!t.matches(':focus')) {
                        salirEdicion();
                    }
                }, 100);
            };

            t.onPaste = ev => {
                ev.preventDefault();
                const texto = ev.clipboardData.getData('text/plain').trim();
                const nuevoTexto = texto.substring(0, 140 - t.textContent.trim().length);
                document.execCommand('insertText', false, nuevoTexto);
            };

            t.addEventListener('input', t.onInput);
            t.addEventListener('blur', t.onBlur);
            t.addEventListener('paste', t.onPaste);
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

//necesito que cuando se archive una tarea, no elimine del dom, simplemente se mueva al final del ul donde se encuentra, que suele ser <ul class="social-post-list clase-tarea" data-filtro="tarea" data-posttype="tarea" data-tab-id="tareas">, y cuando se desarchiva, la coloca abajo siempre de <p data-valor="General" class="divisorTarea General" style="font-weight: bold; cursor: pointer; padding: 5px 20px; margin-right: auto; display: flex; width: 100%; align-items: center;">
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

function completarTarea() {
    document.querySelectorAll('.completaTarea').forEach(boton => {
        boton.addEventListener('click', function () {
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

function moverTarea() {
    const lista = document.querySelector('.clase-tarea');
    if (!lista) return;

    if (lista.listenersAdded) return;
    lista.listenersAdded = true;

    let arrastrandoElem = null;
    let ordenViejo = [];
    let idTareaArrastrada = null;
    let posInicialY = null;
    const tolerancia = 10;
    let movimientoRealizado = false;

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

        if (!movimientoRealizado && Math.abs(mouseY - posInicialY) > tolerancia) {
            movimientoRealizado = true;
        }

        let anterior = arrastrandoElem.previousElementSibling;
        let siguiente = arrastrandoElem.nextElementSibling;
        let encontradoAnterior = false;
        let encontradoSiguiente = false;

        while (anterior || siguiente) {
            if (anterior && !encontradoAnterior) {
                const rectAnt = anterior.getBoundingClientRect();
                if (mouseY < rectAnt.top + rectAnt.height / 2) {
                    lista.insertBefore(arrastrandoElem, anterior);
                    encontradoAnterior = true;
                } else {
                    anterior = anterior.previousElementSibling;
                }
            }

            if (siguiente && !encontradoSiguiente) {
                const rectSig = siguiente.getBoundingClientRect();
                if (mouseY > rectSig.top + rectSig.height / 2) {
                    lista.insertBefore(arrastrandoElem, siguiente.nextSibling);
                    encontradoSiguiente = true;
                } else {
                    siguiente = siguiente.nextElementSibling;
                }
            }

            if (encontradoAnterior || encontradoSiguiente) {
                return;
            }
        }
    };

    const obtenerSesionYDataSeccionArriba = () => {
        let sesionArriba = null;
        let dataSeccionArriba = null;
        let anterior = arrastrandoElem.previousElementSibling;
        while (anterior) {
            if (anterior.classList.contains('POST-tarea')) {
                if (sesionArriba === null) {
                    sesionArriba = anterior.getAttribute('sesion');
                }
                if (dataSeccionArriba === null) {
                    dataSeccionArriba = anterior.getAttribute('data-seccion');
                }
                if (sesionArriba !== null && dataSeccionArriba !== null) break;
            } else if (anterior.classList.contains('divisorTarea')) {
                if (dataSeccionArriba === null) {
                    dataSeccionArriba = anterior.getAttribute('data-valor');
                }
                if (sesionArriba !== null && dataSeccionArriba !== null) break;
            }
            anterior = anterior.previousElementSibling;
        }
        return {sesionArriba, dataSeccionArriba};
    };

    const terminarArrastre = () => {
        if (!arrastrandoElem) return;

        const ordenNuevo = Array.from(lista.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
        const nuevaPosicion = ordenNuevo.indexOf(idTareaArrastrada);
        const {sesionArriba, dataSeccionArriba} = obtenerSesionYDataSeccionArriba();

        if (movimientoRealizado) {
            // Actualizar atributos en el elemento HTML
            if (dataSeccionArriba) {
                arrastrandoElem.setAttribute('data-seccion', dataSeccionArriba);
            }
            if (sesionArriba) {
                arrastrandoElem.setAttribute('sesion', sesionArriba);
            }

            let log = 'Guardando orden:';
            log += `\n  Tarea movida: ${idTareaArrastrada}`;
            log += `\n  Nueva posición: ${nuevaPosicion}`;
            log += `\n  Orden nuevo: ${ordenNuevo}`;
            log += `\n  Sesión de la tarea de arriba: ${sesionArriba}`;
            log += `\n  Data-seccion de la tarea de arriba: ${dataSeccionArriba}`;
            console.log(log);

            guardarOrdenTareas({
                idTareaMovida: idTareaArrastrada,
                nuevaPosicion: nuevaPosicion,
                ordenNuevo: ordenNuevo,
                sesionArriba: sesionArriba,
                dataSeccionArriba: dataSeccionArriba
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

function guardarOrdenTareas({idTareaMovida, nuevaPosicion, ordenNuevo, sesionArriba}) {
    let log = `Guardando orden:\n  Tarea movida: ${idTareaMovida}\n  Nueva posición: ${nuevaPosicion}\n  Orden nuevo: ${ordenNuevo}\n SesionArriba: ${sesionArriba}`;
    console.log(log);

    enviarAjax('actualizarOrdenTareas', {tareaMovida: idTareaMovida, nuevaPosicion, ordenNuevo, sesionArriba})
        .then(res => {
            if (res && res.success) {
                //console.log('Orden de tareas actualizado exitosamente.');
            } else {
                console.error('Error al actualizar el orden de tareas:', res ? res.data.error : 'Respuesta vacía o success: false');
            }
        })
        .catch(err => {
            console.error('Error en la petición AJAX:', err);
        });
}
