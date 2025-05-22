// js/taskSesiones.js

let mapa = {general: [], archivado: []};

//No borrar este comentario: Se escribio mal "seccion", cuando se dice "sesion" se refiere a "seccion", es decir, grupo de tareas.

window.dividirTarea = async function () {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    if (!listaSec) return;
    organizarSecciones();
    crearSeccionFront();
    hacerDivisoresEditables();
    window.addEventListener('reiniciar', organizarSecciones);
};

function actualizarMapa() {
    let log = '';
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    mapa = {general: [], archivado: []};
    const items = Array.from(listaSec.children).filter(item => item.tagName === 'LI');

    log = `actualizarMapa: Tareas encontradas: ${items.length}. `;
    items.forEach(item => {
        const est = item.getAttribute('estado')?.toLowerCase();
        const sesion = item.getAttribute('data-sesion')?.toLowerCase(); // MODIFICADO AQUÍ
        const idPost = item.getAttribute('id-post');
        log += `Tarea ID: ${idPost}, Estado: ${est}, Sesión: ${sesion}. `;

        if (est === 'archivado') {
            mapa['archivado'].push(item);
        } else if (est === 'pendiente') {
            if (!sesion || sesion === '' || sesion === 'pendiente') {
                mapa['general'].push(item);
            } else {
                if (!mapa[sesion]) {
                    mapa[sesion] = [];
                }
                mapa[sesion].push(item);
            }
        }
    });
    //console.log(log + `Mapa actualizado: ${JSON.stringify(mapa)}`);
}

function alternarVisibilidadSeccion(divisor) {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    const valorDivisorCodificado = divisor.dataset.valor;
    const valorDivisor = decodeURIComponent(valorDivisorCodificado); // Decodificar el nombre
    const items = Array.from(listaSec.children).filter(item => item.tagName === 'LI');
    let visible = localStorage.getItem(`seccion-${valorDivisorCodificado}`) !== 'oculto';
    visible = !visible;
    let log = `alternarVisibilidadSeccion: Alternando visibilidad de la sección ${valorDivisor}. `;

    items.forEach(item => {
        if (item.dataset.seccion === valorDivisorCodificado) {
            // Usar el nombre codificado
            item.style.display = visible ? '' : 'none';
            log += `Tarea ID: ${item.getAttribute('id-post')}, Visibilidad: ${visible ? 'visible' : 'oculta'}. `;
        }
    });

    const flecha = divisor.querySelector('span:last-child');
    flecha.innerHTML = visible ? window.fechaabajo || '↓' : window.fechaallado || '↑';
    localStorage.setItem(`seccion-${valorDivisorCodificado}`, visible ? 'visible' : 'oculto');
    //console.log(log);
}

function configurarInteraccionSeccion(divisor, nomCodificado, items) {
    const nom = decodeURIComponent(nomCodificado); // Decodificar el nombre
    const flecha = divisor.querySelector('span:last-child');
    let visible = localStorage.getItem(`seccion-${nomCodificado}`) !== 'oculto'; // Usar el nombre codificado
    items.forEach(item => (item.style.display = visible ? '' : 'none'));
    flecha.innerHTML = visible ? window.fechaabajo || '↓' : window.fechaallado || '↑';

    if (nom === 'General') {
        const iconoAgregar = document.createElement('span');
        iconoAgregar.innerHTML = window.iconoPlus;
        iconoAgregar.style.marginLeft = 'auto';
        iconoAgregar.classList.add('iconoPlus');
        divisor.insertBefore(iconoAgregar, flecha.nextSibling);

        iconoAgregar.onclick = event => {
            event.stopPropagation();
        };
    }

    divisor.onclick = event => {
        event.stopPropagation();
        alternarVisibilidadSeccion(divisor);
    };
}

function crearSeccion(nom, items) {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    let log = `crearSeccion: Creando sección: ${nom}. `;
    const nomCodificado = encodeURIComponent(nom); // Codificar el nombre de la sesión
    let divisor = document.querySelector(`[data-valor="${nomCodificado}"]`);

    if (items.length === 0) {
        if (divisor) {
            divisor.textContent = `No hay tareas en la sección ${nom}`;
            divisor.style.color = 'gray';
        }
        log += `Sección ${nom} vacía, se omite.`;
        //console.log(log);
        return;
    }

    if (!divisor) {
        divisor = document.createElement('p');
        divisor.style.fontWeight = 'bold';
        divisor.style.cursor = 'pointer';
        divisor.style.padding = '5px 20px';
        divisor.style.marginRight = 'auto';
        divisor.style.display = 'flex';
        divisor.style.width = '100%';
        divisor.style.alignItems = 'center';
        divisor.textContent = nom; // Mostrar el nombre original
        divisor.dataset.valor = nomCodificado; // Usar el nombre codificado en data-valor
        divisor.classList.add('divisorTarea', nomCodificado); // Usar el nombre codificado aquí

        const flecha = document.createElement('span');
        flecha.style.marginLeft = '5px';
        divisor.appendChild(flecha);
        listaSec.appendChild(divisor);
    }

    configurarInteraccionSeccion(divisor, nomCodificado, items); // Usar el nombre codificado

    log += `Insertando ${items.length} tareas en la sección ${nom}. `;
    let anterior = divisor;
    items.forEach(item => {
        item.setAttribute('data-seccion', nomCodificado); // Usar el nombre codificado
        if (item.parentNode) item.parentNode.removeChild(item);
        listaSec.insertBefore(item, anterior.nextSibling);
        anterior = item;
    });

    //console.log(log);
}
function eliminarSeparadoresExistentes() {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    const separadores = Array.from(listaSec.children).filter(item => item.tagName === 'P' && item.classList.contains('divisorTarea'));
    separadores.forEach(separador => separador.remove());
}

function organizarSecciones() {
    let log = 'organizarSecciones: Reorganizando tareas... ';
    actualizarMapa();
    eliminarSeparadoresExistentes();
    crearSeccion('General', mapa.general);

    const otrasSecciones = Object.keys(mapa).filter(seccion => seccion !== 'general' && seccion !== 'archivado');
    otrasSecciones.forEach(seccion => crearSeccion(seccion, mapa[seccion]));

    crearSeccion('Archivado', mapa.archivado);

    log += `Secciones reorganizadas: General (${mapa.general.length}), `;
    if (otrasSecciones.length > 0) {
        log += `${otrasSecciones.map(s => `${s} (${mapa[s].length})`).join(', ')}, `;
    }
    log += `Archivado (${mapa.archivado.length}). `;
    //console.log(log);
    generarLogFinal();
}

function generarLogFinal() {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    let log = '';
    const final = [];
    Array.from(listaSec.children).forEach(item => {
        if (item.tagName === 'LI') {
            const idPost = item.getAttribute('id-post');
            final.push(`${item.getAttribute('data-sesion') || 'Sin sección'} - ${idPost || 'sin ID'}`);
        } else if (item.tagName === 'P') {
            final.push(`${item.textContent} - Divisor`);
        }
    });
    log = `generarLogFinal: Orden final: ${final.join(', ')}`;
    //console.log(log);
}

/*

*/

function crearSeccionFront() {
    const botonPlus = document.querySelector('.iconoPlus');
    const listaSecTareas = document.querySelector('.clase-tarea');

    botonPlus.addEventListener('click', () => {
        const textoInicial = 'Nueva sesión';
        const nuevaSesion = document.createElement('p');
        nuevaSesion.dataset.valor = textoInicial;
        nuevaSesion.classList.add('divisorTarea');
        nuevaSesion.contentEditable = true;
        nuevaSesion.textContent = textoInicial;

        const spanFlecha = document.createElement('span');
        spanFlecha.style.marginLeft = '5px';
        spanFlecha.innerHTML = window.fechaabajo;

        nuevaSesion.appendChild(spanFlecha);
        listaSecTareas.prepend(nuevaSesion);

        nuevaSesion.focus();

        nuevaSesion.addEventListener('blur', () => {
            let textoEditado = nuevaSesion.textContent;
            if (textoEditado == '') {
                textoEditado = 'Nueva sesión';
                nuevaSesion.textContent = textoEditado;
            }
            nuevaSesion.dataset.valor = textoEditado;
            //console.log('Nombre de la sesión actualizado:', nuevaSesion.dataset.valor);
        });
    });
}

function hacerDivisoresEditables() {
    const divisores = document.querySelectorAll('.divisorTarea');

    divisores.forEach(divisor => {
        const nombreOriginalDecodificado = decodeURIComponent(divisor.dataset.valor);

        if (nombreOriginalDecodificado !== 'General' && nombreOriginalDecodificado !== 'Archivado') {
            divisor.contentEditable = true;

            let valorAlEnfocar = nombreOriginalDecodificado; // Guardar el valor al obtener el foco

            divisor.addEventListener('focus', () => {
                valorAlEnfocar = decodeURIComponent(divisor.dataset.valor); // Actualizar por si cambió externamente
                // Opcional: remover temporalmente la flecha para una edición más limpia
                const flecha = divisor.querySelector('span:last-child');
                if (flecha) flecha.style.display = 'none';
                divisor.textContent = valorAlEnfocar; // Mostrar solo el nombre para editar
            });

            divisor.addEventListener('blur', async () => {
                const flecha = divisor.querySelector('span:last-child');
                if (flecha) flecha.style.display = ''; // Restaurar flecha

                let textoEditadoDecodificado = divisor.textContent.trim();

                if (textoEditadoDecodificado === '') {
                    textoEditadoDecodificado = valorAlEnfocar; // Restaurar si está vacío
                }

                // Actualizar visualmente el divisor con el nombre y la flecha
                divisor.textContent = textoEditadoDecodificado;
                if (flecha) divisor.appendChild(flecha); // Re-adjuntar la flecha al final

                const valorCodificadoOriginalEditor = encodeURIComponent(valorAlEnfocar);
                const nuevoValorCodificadoEditor = encodeURIComponent(textoEditadoDecodificado);

                if (textoEditadoDecodificado !== valorAlEnfocar) {
                    let conflicto = false;
                    document.querySelectorAll('.divisorTarea').forEach(d => {
                        if (d !== divisor && decodeURIComponent(d.dataset.valor).toLowerCase() === textoEditadoDecodificado.toLowerCase()) {
                            conflicto = true;
                        }
                    });

                    if (conflicto) {
                        alert(`La sección "${textoEditadoDecodificado}" ya existe.`);
                        divisor.textContent = valorAlEnfocar; // Restaurar texto visual
                        if (flecha) divisor.appendChild(flecha);
                        // No es necesario tocar dataset.valor si no se envió AJAX
                        return;
                    }

                    let datos = {
                        valorOriginal: valorAlEnfocar,
                        valorNuevo: textoEditadoDecodificado
                    };
                    try {
                        await enviarAjax('actualizarSeccion', datos);

                        divisor.dataset.valor = nuevoValorCodificadoEditor;
                        // Actualizar clases si se usan para estilizar basado en el nombre codificado
                        // divisor.classList.remove(valorCodificadoOriginalEditor);
                        // divisor.classList.add(nuevoValorCodificadoEditor);

                        const tareasAfectadas = document.querySelectorAll(`.POST-tarea[data-sesion="${valorCodificadoOriginalEditor}"]`);
                        tareasAfectadas.forEach(tarea => {
                            tarea.setAttribute('data-sesion', nuevoValorCodificadoEditor);
                        });

                        // Opcional: Forzar reorganización visual si es necesario inmediatamente
                        // if (window.dividirTarea) await window.dividirTarea();

                        console.log('Sesión actualizada y tareas reasignadas en el frontend.');
                    } catch (error) {
                        console.error('Error al actualizar sesión:', error);
                        divisor.textContent = valorAlEnfocar; // Restaurar texto visual
                        if (flecha) divisor.appendChild(flecha);
                        // dataset.valor no se cambió, así que no necesita restauración
                    }
                } else {
                    // Aunque el texto no haya cambiado, asegurar que el dataset.valor es el correcto (codificado)
                    divisor.dataset.valor = nuevoValorCodificadoEditor;
                }
                // Re-asegurar contentEditable después del blur
                if (decodeURIComponent(divisor.dataset.valor) !== 'General' && decodeURIComponent(divisor.dataset.valor) !== 'Archivado') {
                    divisor.contentEditable = true;
                } else {
                    divisor.contentEditable = false;
                }
            });

            divisor.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    divisor.blur();
                } else if (e.key === 'Escape') {
                    divisor.textContent = decodeURIComponent(divisor.dataset.valor); // Restaurar al valor original del dataset
                    const flecha = divisor.querySelector('span:last-child');
                    if (flecha) divisor.appendChild(flecha);
                    divisor.blur();
                }
            });
        } else {
            divisor.contentEditable = false;
        }
    });
}

window.initAsignarSeccionModal = function () {
    const listaTareas = document.querySelector('.social-post-list.clase-tarea');
    if (!listaTareas) {
        console.log('initAsignarSeccionModal: listaTareas no encontrada.');
        return;
    }

    if (listaTareas.dataset.seccionModalInic) {
        // console.log('initAsignarSeccionModal: ya inicializado para esta lista.');
        return;
    }
    listaTareas.dataset.seccionModalInic = 'true';

    async function manejadorClickListaTareas(evento) {
        const divCarpeta = evento.target.closest('.divCarpeta');
        if (divCarpeta) {
            evento.stopPropagation();
            const idTarea = divCarpeta.dataset.tarea;
            // console.log('manejadorClickListaTareas: divCarpeta clickeado, idTarea:', idTarea);
            if (!idTarea) {
                console.log('manejadorClickListaTareas: idTarea no encontrado en divCarpeta.');
                return;
            }
            await abrirModalAsignarSeccion(idTarea, divCarpeta);
        }
    }

    listaTareas.addEventListener('click', manejadorClickListaTareas);
    console.log('initAsignarSeccionModal: listener configurado en listaTareas.');
};

async function abrirModalAsignarSeccion(idTarea, elemRef) {
    console.log('abrirModalAsignarSeccion: idTarea', idTarea);
    window.hideAllOpenTaskMenus();
    cerrarModalAsignarSeccion();

    const modal = document.createElement('div');
    modal.id = 'modalAsignarSeccion';
    modal.classList.add('modal-asignar-seccion', 'modal', 'bloque');

    modal.innerHTML = `
        <div class="div-asignar-seccion-input" style="gap: 5px;">
            <input type="text" id="inputNuevaSeccionModal" placeholder="Crear sección" maxlength="30">
            <button id="btnCrearAsignarSeccionModal" style="display: none;"></button>
        </div>
        <div id="listaSeccionesExistentesModal" ></div>
        <button id="btnCerrarModalSeccion" style="display: none;">Cerrar</button>
    `;

    document.body.appendChild(modal);
    // Forzar reflow para asegurar dimensiones correctas antes de calcular posición
    modal.offsetHeight;

    const modalAncho = modal.offsetWidth;
    const modalAlto = modal.offsetHeight;
    const margenVP = 10; // Margen del viewport

    const rectRef = elemRef.getBoundingClientRect();

    let topCalculado = window.scrollY + rectRef.bottom + 5;
    let leftCalculado = window.scrollX + rectRef.left;

    // Ajustar horizontalmente
    if (leftCalculado + modalAncho > window.scrollX + window.innerWidth - margenVP) {
        leftCalculado = window.scrollX + window.innerWidth - modalAncho - margenVP;
    }
    if (leftCalculado < window.scrollX + margenVP) {
        leftCalculado = window.scrollX + margenVP;
    }

    // Ajustar verticalmente
    if (topCalculado + modalAlto > window.scrollY + window.innerHeight - margenVP) {
        // Si se sale por abajo
        let topArriba = window.scrollY + rectRef.top - modalAlto - 5;
        if (topArriba < window.scrollY + margenVP) {
            // Si al ponerlo arriba, se sale por arriba
            // No cabe ni arriba ni abajo cómodamente pegado al elemento.
            // Colocarlo lo más abajo posible sin salirse del viewport.
            topCalculado = window.scrollY + window.innerHeight - modalAlto - margenVP;
            if (topCalculado < window.scrollY + margenVP) {
                // Si el modal es muy alto para el viewport
                topCalculado = window.scrollY + margenVP; // Pegar al borde superior del viewport
            }
        } else {
            topCalculado = topArriba; // Cabe arriba
        }
    }
    if (topCalculado < window.scrollY + margenVP) {
        // Doble chequeo por si se posicionó muy arriba
        topCalculado = window.scrollY + margenVP;
    }

    modal.style.position = 'absolute';
    modal.style.top = `${Math.max(0, topCalculado)}px`;
    modal.style.left = `${Math.max(0, leftCalculado)}px`;
    modal.style.zIndex = '10001'; // z-index alto

    const listaSecDiv = modal.querySelector('#listaSeccionesExistentesModal');
    const divisores = document.querySelectorAll('.social-post-list.clase-tarea .divisorTarea');

    divisores.forEach(divisor => {
        const nomSecEnc = divisor.dataset.valor;
        const nomSecOrig = decodeURIComponent(nomSecEnc);
        if (nomSecOrig.toLowerCase() === 'archivado') return;

        const pSec = document.createElement('p');
        pSec.textContent = nomSecOrig;
        pSec.addEventListener('click', async () => {
            await manejarAsignacionSeccion(idTarea, nomSecOrig);
        });
        listaSecDiv.appendChild(pSec);
    });

    if (listaSecDiv.children.length === 0) {
        listaSecDiv.innerHTML = '<p>No hay secciones. Crea una.</p>';
    }

    const inpNuevaSec = modal.querySelector('#inputNuevaSeccionModal');
    const btnCrearSec = modal.querySelector('#btnCrearAsignarSeccionModal');

    const procesarNuevaSeccion = async () => {
        const nombreNuevo = inpNuevaSec.value.trim();
        console.log('procesarNuevaSeccion: nombre', nombreNuevo, 'idTarea', idTarea);
        const maxLong = 30;
        const regexVal = /^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s]+$/;

        if (!nombreNuevo) {
            alert('El nombre de la nueva sección no puede estar vacío.');
            return;
        }
        if (nombreNuevo.length > maxLong) {
            alert(`El nombre de la sección no puede exceder los ${maxLong} caracteres.`);
            return;
        }
        if (!regexVal.test(nombreNuevo)) {
            alert('El nombre de la sección solo puede contener letras, números y espacios.');
            return;
        }
        if (nombreNuevo.toLowerCase() === 'general' || nombreNuevo.toLowerCase() === 'archivado') {
            alert('No se puede nombrar una sección como "General" o "Archivado".');
            return;
        }

        let existe = false;
        document.querySelectorAll('.social-post-list.clase-tarea .divisorTarea').forEach(div => {
            if (decodeURIComponent(div.dataset.valor).toLowerCase() === nombreNuevo.toLowerCase()) {
                existe = true;
            }
        });

        if (existe) {
            alert(`La sección "${nombreNuevo}" ya existe. Selecciónala de la lista o elige otro nombre.`);
            return;
        }
        await manejarAsignacionSeccion(idTarea, nombreNuevo);
    };

    btnCrearSec.addEventListener('click', procesarNuevaSeccion);
    inpNuevaSec.addEventListener('keypress', async evento => {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            await procesarNuevaSeccion();
        }
    });
    inpNuevaSec.focus();
    modal.querySelector('#btnCerrarModalSeccion').addEventListener('click', cerrarModalAsignarSeccion);

    window.cerrarModalSeccionEvt = function (evento) {
        if (modal && !modal.contains(evento.target) && evento.target !== elemRef && !elemRef.contains(evento.target)) {
            cerrarModalAsignarSeccion();
        }
    };

    setTimeout(() => {
        // Asegura que este listener se añade después del evento de click actual
        document.addEventListener('click', window.cerrarModalSeccionEvt, true);
    }, 0);
    // console.log('abrirModalAsignarSeccion: modal configurado para idTarea', idTarea);
}

async function manejarAsignacionSeccion(idTarea, nombreSeccion) {
    console.log(`manejarAsignacionSeccion: idTarea ${idTarea}, seccion ${nombreSeccion}`);
    try {
        const resp = await enviarAjax('asignarSeccionMeta', {
            idTarea: idTarea,
            sesion: nombreSeccion
        });

        if (resp.success) {
            // Primero, reiniciamos el post del padre. Esto es importante para que su HTML esté actualizado.
            // Asumimos que reiniciarPost no cambia drásticamente la posición del elemento,
            // o si lo hace, lo encontraremos de nuevo.
            await window.reiniciarPost(idTarea, 'tarea');

            const nombreSeccionCodificado = encodeURIComponent(nombreSeccion);
            const tareaElem = document.querySelector(`.POST-tarea[id-post="${idTarea}"]`);
            // Necesitamos el contenedor principal de tareas para las manipulaciones del DOM.
            const listaContenedora = document.querySelector('.social-post-list.clase-tarea');

            if (tareaElem && listaContenedora) {
                // 1. Actualizar data-sesion de la tarea principal en el DOM.
                //    Esto asegura que dividirTarea sepa a qué sección pertenece.
                tareaElem.setAttribute('data-sesion', nombreSeccionCodificado);

                // 2. Recolectar todas las subtareas y actualizar su data-sesion en el DOM.
                //    Usamos Array.from para obtener una lista estática de elementos.
                const subtareasElems = Array.from(listaContenedora.querySelectorAll(`.POST-tarea[padre="${idTarea}"]`));
                subtareasElems.forEach(subElem => {
                    subElem.setAttribute('data-sesion', nombreSeccionCodificado);
                });

                // 3. Reordenar físicamente la tarea padre y sus subtareas en el DOM.
                //    Queremos que el padre esté primero, seguido inmediatamente por sus hijas,
                //    en el orden en que fueron encontradas.
                //    Esto se hace ANTES de llamar a dividirTarea.

                //    Movemos cada subtarea para que sea el siguiente hermano del padre (o de la subtarea anterior).
                //    Iteramos en reversa sobre las subtareas para facilitar la inserción con `insertBefore`.
                //    Si insertamos HijaN, HijaN-1, ..., Hija1 usando `insertBefore(hija, padre.nextSibling)`,
                //    el resultado será Padre, Hija1, Hija2, ..., HijaN.
                for (let i = subtareasElems.length - 1; i >= 0; i--) {
                    const subElem = subtareasElems[i];
                    // Asegurarnos de que la subtarea realmente está en la lista principal
                    if (subElem.parentNode === listaContenedora) {
                        listaContenedora.insertBefore(subElem, tareaElem.nextSibling);
                    }
                }
                // En este punto, el DOM debería tener: ... TareaAnterior, Padre, Hija1, Hija2, ..., HijaN, SiguienteTarea ...
                // o si el padre estaba al final: ... TareaAnterior, Padre, Hija1, Hija2, ..., HijaN
                // console.log(`manejarAsignacionSeccion: Padre ${idTarea} y sus ${subtareasElems.length} hijas reordenadas en el DOM.`);
            } else {
                if (!tareaElem) console.error(`manejarAsignacionSeccion: Tarea padre ${idTarea} no encontrada en el DOM después de reiniciarPost.`);
                if (!listaContenedora) console.error(`manejarAsignacionSeccion: Lista contenedora principal no encontrada.`);
            }

            cerrarModalAsignarSeccion();

            // Ahora llamamos a dividirTarea. Como el padre y las hijas están contiguas y con el
            // data-sesion correcto, actualizarMapa los leerá en ese orden, y crearSeccion
            // los insertará juntos en la nueva sección.
            if (window.dividirTarea) {
                await window.dividirTarea();
            } else {
                console.error('manejarAsignacionSeccion: window.dividirTarea no está definida.');
            }
        } else {
            alert(`Error al asignar sección: ${resp.data || 'Error desconocido del servidor'}`);
        }
    } catch (error) {
        console.error('manejarAsignacionSeccion: Excepción', error);
        alert('Ocurrió una excepción al intentar asignar la sección.');
    }
}

function cerrarModalAsignarSeccion() {
    // console.log('cerrarModalAsignarSeccion: cerrando modal.');
    const modal = document.getElementById('modalAsignarSeccion');
    if (modal) {
        modal.remove();
    }
    if (window.cerrarModalSeccionEvt) {
        document.removeEventListener('click', window.cerrarModalSeccionEvt, true);
        window.cerrarModalSeccionEvt = null;
    }
}
