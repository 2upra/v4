let importancia = {
    selector: null,
    valor: 'poca'
};

let tipoTarea = {
    selector: null,
    valor: 'una vez'
};

function initTareas() {
    selectorTipoTarea();
    enviarTarea();
    editarTarea();
}

function enviarTarea() {
    const tit = document.getElementById('tituloTarea');

    tit.addEventListener('keyup', ev => {
        if (ev.key === 'Enter') {
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
                });
        }
    });
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

//casi funciona, falla solo cuando se da click, el cursor de editar texto o la raya o como se llame, se va hacia el inicio, se arreglo lo de los espacios 
function editarTarea() {
    const tareas = document.querySelectorAll('.tituloTarea');

    tareas.forEach(tarea => {
        tarea.addEventListener('click', event => {
            event.preventDefault(); // Prevent default behavior
            const id = tarea.dataset.tarea;
            const valorAnterior = tarea.textContent;
            tarea.contentEditable = true;
            tarea.spellcheck = false;

            // Set cursor to click position
            const offset = getOffset(event, tarea);
            const range = document.createRange();
            range.setStart(tarea.firstChild, offset);
            range.collapse(true);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);

            tarea.focus();

            tarea.addEventListener('keydown', ev => {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    tarea.contentEditable = false;
                    const valorNuevo = tarea.textContent.trimEnd();
                    if (valorAnterior !== valorNuevo) {
                        const datos = {id, titulo: valorNuevo};
                        console.log('Modificando tarea:', datos);
                        enviarAjax('modificarTarea', datos)
                            .then(rta => {
                                if (!rta.success) {
                                    tarea.textContent = valorAnterior;
                                    let m = 'Error al modificar.';
                                    if (rta.data) m += ' Detalles: ' + rta.data;
                                    alert(m);
                                    console.log(rta.data);
                                }
                            })
                            .catch(err => {
                                tarea.textContent = valorAnterior;
                                alert('Error al modificar. Revisa la consola.');
                                console.error(err);
                            });
                    }
                }
            });

            tarea.addEventListener('paste', ev => {
                ev.preventDefault();
                const texto = ev.clipboardData.getData('text/plain');
                document.execCommand('insertText', false, texto);
            });

            tarea.addEventListener('input', () => {
                const sel = window.getSelection();
                const pos = sel.focusOffset;
                let texto = tarea.textContent;
                // Only replace multiple spaces with single space
                texto = texto.replace(/ {2,}/g, ' ');
                tarea.textContent = texto;
                if (sel.rangeCount) {
                    const rango = sel.getRangeAt(0);
                    rango.collapse(true);
                    const textoNodo = tarea.firstChild;
                    rango.setStart(textoNodo, Math.min(pos, texto.length));
                    rango.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(rango);
                }
            });

            tarea.style.outline = 'none';
            tarea.style.border = 'none';
            tarea.style.boxShadow = 'none';
        });

        tarea.addEventListener('blur', () => {
            tarea.contentEditable = false;
            tarea.style.outline = 'none';
            tarea.style.border = 'none';
            tarea.style.boxShadow = 'none';
        });
    });
}


function getOffset(event, element) {
    const range = document.createRange();
    range.selectNodeContents(element);
    const pos = range.getBoundingClientRect();
    const x = event.clientX - pos.left;
    const y = event.clientY - pos.top;
    const point = {x, y};
    const caretPos = document.caretPositionFromPoint(x, y);
    let offset = 0;
    if (caretPos.offsetNode === element) {
        offset = caretPos.offset;
    } else {
        // Handle cases where the click is on child nodes
        const childNodes = element.childNodes;
        for (let i = 0; i < childNodes.length; i++) {
            if (childNodes[i] === caretPos.offsetNode) {
                offset = caretPos.offset;
                for (let j = 0; j < i; j++) {
                    if (childNodes[j].nodeType === Node.TEXT_NODE) {
                        offset += childNodes[j].length;
                    }
                }
                break;
            }
        }
    }
    return offset;
}
