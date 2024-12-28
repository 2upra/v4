let importancia = {
    selector: null,
    valor: 'poca'
};

let tipoTarea = {
    selector: null,
    valor: 'una vez'
};

function enviarTareaHandler(ev) {
    const tit = document.getElementById('tituloTarea');
    let enviando = false;

    if (ev.key === 'Enter' && !enviando) {
        enviando = true;
        const data = {
            titulo: tit.value,
            importancia: importancia.valor,
            tipo: tipoTarea.valor
        };
        console.log('Enviando tarea:', data);

        enviarAjax('crearTarea', data)
            .then(rta => {
                if (rta.success) {
                    alert('Tarea creada.');
                    tit.value = '';
                    limpiar = false;
                    arriba = true;
                    window.limpiarBusqueda(limpiar, arriba);
                } else {
                    let m = 'Error al crear tarea.';
                    if (rta.data) {
                        m += ' Detalles: ' + rta.data;
                    }
                    alert(m);
                    console.log(rta.data);
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
        guardarOrdenTareas();
        selectorTipoTarea();
        enviarTarea();
        editarTarea();
        moverTarea();
        completarTarea();
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
    const impBtns = document.querySelectorAll('#sImportancia-sImportancia .A1806242 button');
    const tipoBtns = document.querySelectorAll('#sTipo-sTipo .A1806242 button');

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

    impBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            actSel(importancia, btn.value);
        });
    });

    tipoBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            actSel(tipoTarea, btn.value);
        });
    });
    actSel(importancia, 'poca');
    actSel(tipoTarea, 'una vez');
}

function editarTarea() {
    const tareas = document.querySelectorAll('.tituloTarea');

    tareas.forEach(t => {
        t.addEventListener('click', ev => {
            ev.preventDefault();
            const id = t.dataset.tarea;
            let valorAnt = t.textContent;
            t.contentEditable = true;
            t.spellcheck = false;

            const off = calcularPosicionCursor(ev, t);
            setCursorPos(t, off);

            const presionarEnter = ev => {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    guardarEdicion(t, id, valorAnt);
                }
            };

            const salirEdicion = () => {
                guardarEdicion(t, id, valorAnt);
            };

            t.addEventListener('keydown', presionarEnter);
            t.addEventListener('blur', salirEdicion);

            t.addEventListener('paste', ev => {
                ev.preventDefault();
                const texto = ev.clipboardData.getData('text/plain').trim();
                document.execCommand('insertText', false, texto);
            });
        });
    });
}

function guardarEdicion(t, id, valorAnt) {
    t.contentEditable = false;
    t.style.outline = 'none';
    t.style.border = 'none';
    t.style.boxShadow = 'none';

    const valorNuevo = t.textContent.trim();
    if (valorAnt !== valorNuevo) {
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

    let arrastrandoElem;

    const iniciarArrastre = ev => {
        let target = ev.target;
        if (!target.classList.contains('draggable-element')) {
            target = target.closest('.draggable-element');
            if (!target) return;
        }
        arrastrandoElem = target;
        arrastrandoElem.classList.add('dragging');
        document.body.classList.add('dragging-active');

        lista.addEventListener('mousemove', duranteArrastre);
        lista.addEventListener('mouseup', terminarArrastre);
    };

    const duranteArrastre = ev => {
        if (!arrastrandoElem) return;
        ev.preventDefault();

        const anterior = arrastrandoElem.previousElementSibling;
        const siguiente = arrastrandoElem.nextElementSibling;

        if (anterior && ev.clientY < anterior.offsetTop + anterior.offsetHeight / 2) {
            lista.insertBefore(arrastrandoElem, anterior);
        } else if (siguiente && ev.clientY > siguiente.offsetTop + siguiente.offsetHeight / 2) {
            lista.insertBefore(arrastrandoElem, siguiente.nextSibling);
        }
    };

    const terminarArrastre = () => {
        if (!arrastrandoElem) return;

        arrastrandoElem.classList.remove('dragging');
        document.body.classList.remove('dragging-active');

        lista.removeEventListener('mousemove', duranteArrastre);
        lista.removeEventListener('mouseup', terminarArrastre);
        arrastrandoElem = null;

        guardarOrdenTareas();
    };

    lista.addEventListener('mousedown', ev => {
        if (ev.target.closest('.draggable-element')) iniciarArrastre(ev);
    });

    lista.addEventListener('dragstart', ev => {
        ev.preventDefault();
    });

    cargarOrdenTareas();
}

function guardarOrdenTareas() {
    const lista = document.querySelector('.clase-tarea');
    if (!lista) return;
    const tareas = Array.from(lista.querySelectorAll('.draggable-element')).map(tarea => tarea.getAttribute('id-post'));
    localStorage.setItem('ordenTareas', JSON.stringify(tareas));
}

function cargarOrdenTareas() {
    const lista = document.querySelector('.clase-tarea');
    if (!lista) return;
    const orden = JSON.parse(localStorage.getItem('ordenTareas'));
    if (orden) {
        orden.forEach(postId => {
            const tarea = lista.querySelector(`.draggable-element[id-post="${postId}"]`);
            if (tarea) lista.appendChild(tarea);
        });
    }
}

function completarTarea() {
    document.querySelectorAll('.completaTarea').forEach(boton => {
        boton.addEventListener('click', function () {
            const botonClicado = this;
            const tarea = botonClicado.closest('.draggable-element');
            const tareaId = botonClicado.dataset.tarea;
            const dat = {id: tareaId};
            const estado = tarea.classList.contains('completada') ? 'pendiente' : 'completada';
            dat.estado = estado;

            enviarAjax('completarTarea', dat)
                .then(rta => {
                    if (rta.success) {
                        if (estado === 'completada') {
                            tarea.classList.add('completada');
                            tarea.style.textDecoration = 'line-through';

                            if (window.filtrosGlobales && window.filtrosGlobales.includes('ocultarCompletadas')) {
                                tarea.style.display = 'none';
                            }
                        } else {
                            tarea.classList.remove('completada');
                            tarea.style.textDecoration = 'none';
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

function cambiarPrioridad() {
    /*
    <div class="divImportancia" data-tarea="<? echo $tareaId; ?>">
        <p class="importanciaTarea svgtask">
            <? echo $impIcono; ?>
            <span class="tituloImportancia"><? echo $importancia; ?></span>
        </p>
    </div>

    necesito una funcion que cambie la Prioridad de la tarea, son 4: poca, media, alta, urgente

    cuando de click a divImportancia, aparecera debajo de la tarea, o sea un div o otro li debajo de la tarea 
     <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo get_the_ID(); ?> <? echo $claseCompletada; ?> draggable-element"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($autorId); ?>"
        draggable="true" <? echo $estiloTachado; ?>>

    un div y dentro las opciones de prioridad 
    y si se da click, se envia al servidor enviarAjax('cambiarPrioridad', data) 
    */
}
