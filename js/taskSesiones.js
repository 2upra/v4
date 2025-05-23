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
    const valorDivisor = decodeURIComponent(valorDivisorCodificado); 
    const items = Array.from(listaSec.children).filter(item => item.tagName === 'LI');
    let visible = localStorage.getItem(`seccion-${valorDivisorCodificado}`) !== 'oculto';
    visible = !visible;
    // let log = `alternarVisibilidadSeccion: Alternando visibilidad de la sección ${valorDivisor}. `;

    items.forEach(item => {
        if (item.dataset.seccion === valorDivisorCodificado) {
            item.style.display = visible ? '' : 'none';
            // log += `Tarea ID: ${item.getAttribute('id-post')}, Visibilidad: ${visible ? 'visible' : 'oculta'}. `;
        }
    });

    const flecha = divisor.querySelector('.flecha-seccion'); // BUSCAR POR CLASE
    if (flecha) { // Comprobar que la flecha existe antes de modificarla
        flecha.innerHTML = visible ? (window.fechaabajo || '↓') : (window.fechaallado || '↑');
    } else {
        // console.error("alternarVisibilidadSeccion: No se encontró .flecha-seccion para:", valorDivisor);
    }
    localStorage.setItem(`seccion-${valorDivisorCodificado}`, visible ? 'visible' : 'oculto');
    // console.log(log);
}

function configurarInteraccionSeccion(divisor, nomCodificado, items) {
    const nom = decodeURIComponent(nomCodificado);
    const flecha = divisor.querySelector('.flecha-seccion'); // Buscar por clase
    let visible = localStorage.getItem(`seccion-${nomCodificado}`) !== 'oculto'; 
    items.forEach(item => (item.style.display = visible ? '' : 'none'));
    
    if (flecha) { // Asegurarse que la flecha exista
        flecha.innerHTML = visible ? (window.fechaabajo || '↓') : (window.fechaallado || '↑');
    } else {
        // console.warn("configurarInteraccionSeccion: No se encontró .flecha-seccion para:", nom);
    }


    if (nom === 'General') {
        const iconoAgregar = document.createElement('span');
        iconoAgregar.innerHTML = window.iconoPlus;
        // iconoAgregar.style.marginLeft = 'auto'; // El span de nombre ya usa marginRight auto
        iconoAgregar.style.marginLeft = '5px'; 
        iconoAgregar.classList.add('iconoPlus');
        
        // Insertar iconoPlus después del nombre y antes de la flecha
        const nombreSpan = divisor.querySelector('.nombre-seccion-editable');
        if (nombreSpan && flecha) {
            divisor.insertBefore(iconoAgregar, flecha);
        } else if (nombreSpan) { // Si no hay flecha, después del nombre
            nombreSpan.insertAdjacentElement('afterend', iconoAgregar);
        } else { // Fallback si ni nombreSpan ni flecha existen
            divisor.appendChild(iconoAgregar);
        }


        iconoAgregar.onclick = event => {
            event.stopPropagation();
            // Aquí tu lógica para window.crearTareaEnSeccionGeneral();
        };
    }

    divisor.onclick = event => {
        // Solo alternar si el clic NO fue en el span editable
        if (event.target.classList.contains('nombre-seccion-editable') && event.target.isContentEditable) {
            return; 
        }
        event.stopPropagation();
        alternarVisibilidadSeccion(divisor);
    };
}

function crearSeccion(nom, items) {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    // let log = `crearSeccion: Creando sección: ${nom}. `;
    const nomCodificado = encodeURIComponent(nom); 
    let divisor = document.querySelector(`[data-valor="${nomCodificado}"]`);

    if (items.length === 0) {
        if (divisor) {
            const nombreSpan = divisor.querySelector('.nombre-seccion-editable');
            if (nombreSpan) nombreSpan.textContent = `No hay tareas en ${nom}`;
            else divisor.textContent = `No hay tareas en la sección ${nom}`; // Fallback
            divisor.style.color = 'gray';
        }
        // log += `Sección ${nom} vacía, se omite.`;
        // console.log(log);
        return;
    }

    if (!divisor) {
        divisor = document.createElement('p');
        divisor.style.fontWeight = 'bold';
        divisor.style.cursor = 'pointer';
        divisor.style.padding = '5px 20px';
        // divisor.style.marginRight = 'auto'; // Quitar marginRight auto si el span de nombre se encarga del espacio
        divisor.style.display = 'flex';
        divisor.style.width = '100%';
        divisor.style.alignItems = 'center';
        divisor.dataset.valor = nomCodificado;
        divisor.classList.add('divisorTarea', nomCodificado);

        const nombreSpan = document.createElement('span');
        nombreSpan.classList.add('nombre-seccion-editable');
        nombreSpan.textContent = nom; 
        nombreSpan.style.marginRight = 'auto'; // El nombre ocupa el espacio disponible
        nombreSpan.style.flexGrow = '1';      // Para que ocupe el espacio

        const flecha = document.createElement('span');
        flecha.classList.add('flecha-seccion');
        flecha.style.marginLeft = '5px'; // Mantener un pequeño margen
        // flecha.innerHTML se establece en configurarInteraccionSeccion

        divisor.appendChild(nombreSpan);
        divisor.appendChild(flecha);
        listaSec.appendChild(divisor);
    } else {
        // Si el divisor ya existe, asegurar que el nombre esté actualizado (ej. de "No hay tareas" a nombre real)
        const nombreSpan = divisor.querySelector('.nombre-seccion-editable');
        if (nombreSpan) {
            nombreSpan.textContent = nom;
            divisor.style.color = ''; // Restaurar color si estaba en gris
        }
    }

    configurarInteraccionSeccion(divisor, nomCodificado, items); 

    // log += `Insertando ${items.length} tareas en la sección ${nom}. `;
    let anterior = divisor;
    items.forEach(item => {
        item.setAttribute('data-seccion', nomCodificado); 
        if (item.parentNode) item.parentNode.removeChild(item);
        listaSec.insertBefore(item, anterior.nextSibling);
        anterior = item;
    });
    // console.log(log);
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

    if (!botonPlus || !listaSecTareas) return; // Añadir verificación

    botonPlus.addEventListener('click', () => {
        const textoInicial = 'Nueva sesión';
        const nuevaSesionDivisor = document.createElement('p'); // Es el <p> divisor
        nuevaSesionDivisor.dataset.valor = encodeURIComponent(textoInicial); // Codificar para dataset
        nuevaSesionDivisor.classList.add('divisorTarea', encodeURIComponent(textoInicial));
        nuevaSesionDivisor.style.fontWeight = 'bold';
        nuevaSesionDivisor.style.cursor = 'pointer';
        nuevaSesionDivisor.style.padding = '5px 20px';
        nuevaSesionDivisor.style.display = 'flex';
        nuevaSesionDivisor.style.width = '100%';
        nuevaSesionDivisor.style.alignItems = 'center';

        const nombreSpan = document.createElement('span');
        nombreSpan.classList.add('nombre-seccion-editable');
        nombreSpan.textContent = textoInicial;
        nombreSpan.style.marginRight = 'auto';
        nombreSpan.style.flexGrow = '1';
        nombreSpan.contentEditable = true; // Editable desde el inicio

        const spanFlecha = document.createElement('span');
        spanFlecha.classList.add('flecha-seccion');
        spanFlecha.style.marginLeft = '5px';
        spanFlecha.innerHTML = window.fechaabajo || '↓'; // Asumir visible al crear

        nuevaSesionDivisor.appendChild(nombreSpan);
        nuevaSesionDivisor.appendChild(spanFlecha);
        
        // Insertar después del divisor "General" si existe, o al principio
        const divisorGeneral = listaSecTareas.querySelector('.divisorTarea[data-valor="' + encodeURIComponent('General') + '"]');
        if (divisorGeneral && divisorGeneral.nextSibling) {
            listaSecTareas.insertBefore(nuevaSesionDivisor, divisorGeneral.nextSibling);
        } else if (divisorGeneral) { // Si General es el último
            listaSecTareas.appendChild(nuevaSesionDivisor);
        } else { // Si no hay General (poco probable pero por si acaso)
            listaSecTareas.prepend(nuevaSesionDivisor);
        }
        
        // Configurar interacciones para la nueva sección
        // Para configurarInteraccionSeccion, necesita 'items', que estarán vacíos inicialmente.
        configurarInteraccionSeccion(nuevaSesionDivisor, encodeURIComponent(textoInicial), []); 
        // Hacerla editable (aunque ya lo es, esto adjunta los listeners de blur/keydown)
        hacerDivisoresEditables(); // Volver a llamar para que tome este nuevo elemento
                                   // Ojo: esto re-itera todos, podría ser más eficiente solo aplicar al nuevo.
                                   // Para ser más específico, se podría pasar el nuevo divisor a hacerDivisoresEditables
                                   // o tener una función que aplique listeners a un solo divisor.

        nombreSpan.focus();

        // Los listeners de blur y keydown ya se deberían aplicar por hacerDivisoresEditables.
        // Si decides no llamar a hacerDivisoresEditables() globalmente aquí, 
        // tendrías que replicar la lógica de addEventListener('blur') y ('keydown') de hacerDivisoresEditables
        // específicamente para este nombreSpan.

        // Ejemplo de cómo aplicar listeners directamente si no llamas a hacerDivisoresEditables() completo:
        // (Esta lógica ya está en hacerDivisoresEditables, por lo que es redundante si llamas a esa función)
        /*
        nombreSpan.addEventListener('blur', async () => {
            let textoEditado = nombreSpan.textContent.trim();
            if (textoEditado === '') {
                textoEditado = 'Nueva sesión'; // O el valor que tenía antes del focus
                nombreSpan.textContent = textoEditado;
            }
            const nuevoValorCodificado = encodeURIComponent(textoEditado);
            
            // Verificar conflicto (simplificado, en la real se omite el propio elemento)
            let conflicto = false;
            document.querySelectorAll('.divisorTarea .nombre-seccion-editable').forEach(ns => {
                if (ns !== nombreSpan && ns.textContent.trim().toLowerCase() === textoEditado.toLowerCase()) {
                    conflicto = true;
                }
            });

            if (conflicto) {
                alert(`La sección "${textoEditado}" ya existe.`);
                nombreSpan.textContent = decodeURIComponent(nuevaSesionDivisor.dataset.valor); // Restaurar al data-valor del padre
                return;
            }

            nuevaSesionDivisor.dataset.valor = nuevoValorCodificado;
            // Actualizar clase en nuevaSesionDivisor
            // console.log('Nombre de la sesión actualizado:', textoEditado);
            // Aquí también iría la lógica AJAX si la nueva sección se debe guardar inmediatamente
            // y la reasignación de tareas (aunque no hay tareas para una nueva).
        });
        nombreSpan.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                nombreSpan.blur();
            } else if (e.key === 'Escape') {
                 e.preventDefault();
                nombreSpan.textContent = decodeURIComponent(nuevaSesionDivisor.dataset.valor);
                nombreSpan.blur();
            }
        });
        */
    });
}
function hacerDivisoresEditables() {
    const divisores = document.querySelectorAll('.divisorTarea');

    divisores.forEach(divisor => {
        const nombreSpan = divisor.querySelector('.nombre-seccion-editable');
        const flechaSpan = divisor.querySelector('.flecha-seccion'); // Puede ser útil
        
        if (!nombreSpan) {
            // console.warn("hacerDivisoresEditables: No se encontró .nombre-seccion-editable para divisor:", divisor.dataset.valor);
            return; // Saltar si la estructura no es la esperada
        }

        const nombreOriginalDecodificado = decodeURIComponent(divisor.dataset.valor);

        if (nombreOriginalDecodificado !== 'General' && nombreOriginalDecodificado !== 'Archivado') {
            nombreSpan.contentEditable = true; // Solo el span del nombre es editable

            let valorAlEnfocar = ''; 

            nombreSpan.addEventListener('focus', () => {
                valorAlEnfocar = nombreSpan.textContent.trim(); // Obtener texto actual del span
            });

            nombreSpan.addEventListener('blur', async () => {
                let textoEditadoDecodificado = nombreSpan.textContent.trim();

                if (textoEditadoDecodificado === '') {
                    textoEditadoDecodificado = valorAlEnfocar; 
                    nombreSpan.textContent = textoEditadoDecodificado; // Restaurar visualmente
                }
                
                // El dataset.valor del <p> (divisor) sigue siendo la fuente de verdad para el nombre codificado.
                const valorCodificadoOriginalEditor = divisor.dataset.valor; // Este es el valor ANTES de la edición
                const valorAntiguoDecodificado = decodeURIComponent(valorCodificadoOriginalEditor);

                if (textoEditadoDecodificado !== valorAntiguoDecodificado) {
                    let conflicto = false;
                    document.querySelectorAll('.divisorTarea').forEach(d => {
                        const otroNombreSpan = d.querySelector('.nombre-seccion-editable');
                        if (d !== divisor && otroNombreSpan && otroNombreSpan.textContent.trim().toLowerCase() === textoEditadoDecodificado.toLowerCase()) {
                            conflicto = true;
                        }
                    });

                    if (conflicto) {
                        alert(`La sección "${textoEditadoDecodificado}" ya existe.`);
                        nombreSpan.textContent = valorAntiguoDecodificado; // Restaurar texto visual al original del dataset
                        // No es necesario tocar dataset.valor si no se envió AJAX
                        return;
                    }

                    let datos = {
                        valorOriginal: valorAntiguoDecodificado, // Enviar el nombre decodificado original
                        valorNuevo: textoEditadoDecodificado
                    };
                    try {
                        await enviarAjax('actualizarSeccion', datos);
                        
                        const nuevoValorCodificadoEditor = encodeURIComponent(textoEditadoDecodificado);
                        divisor.dataset.valor = nuevoValorCodificadoEditor; // Actualizar el <p>
                        // Actualizar clases si se usan para estilizar basado en el nombre codificado
                        // divisor.classList.remove(valorCodificadoOriginalEditor); // Cuidado aquí, valorCodificadoOriginalEditor puede no ser el nombre de clase si el nombre cambió y luego hubo error
                        // divisor.classList.add(nuevoValorCodificadoEditor);

                        // La clase con el nombre codificado en el <p> también debe actualizarse si la usas para algo más que 'divisorTarea'
                        const clasesAntiguasCodificadas = Array.from(divisor.classList).filter(cls => cls !== 'divisorTarea' && cls !== decodeURIComponent(valorCodificadoOriginalEditor));
                        divisor.className = 'divisorTarea'; // Reset base
                        clasesAntiguasCodificadas.forEach(cls => divisor.classList.add(cls)); // Re-añadir otras clases
                        divisor.classList.add(nuevoValorCodificadoEditor); // Añadir la nueva clase de nombre codificado


                        const tareasAfectadas = document.querySelectorAll(`.POST-tarea[data-sesion="${valorCodificadoOriginalEditor}"]`);
                        tareasAfectadas.forEach(tarea => {
                            tarea.setAttribute('data-sesion', nuevoValorCodificadoEditor);
                        });
                        // console.log('Sesión actualizada y tareas reasignadas en el frontend.');
                    } catch (error) {
                        // console.error('Error al actualizar sesión:', error);
                        alert(`Error al actualizar. Se restaurará el nombre original.`);
                        nombreSpan.textContent = valorAntiguoDecodificado; // Restaurar texto visual
                        // dataset.valor no se cambió, así que no necesita restauración
                    }
                }
                // Asegurar que el contentEditable sigue bien (puede que no sea necesario si no se quita)
                if (decodeURIComponent(divisor.dataset.valor) !== 'General' && decodeURIComponent(divisor.dataset.valor) !== 'Archivado') {
                    nombreSpan.contentEditable = true;
                } else {
                    nombreSpan.contentEditable = false;
                }
            });

            nombreSpan.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    nombreSpan.blur();
                } else if (e.key === 'Escape') {
                    e.preventDefault(); 
                    nombreSpan.textContent = decodeURIComponent(divisor.dataset.valor); // Restaurar al valor original del dataset del <p>
                    nombreSpan.blur();
                }
            });
        } else {
            nombreSpan.contentEditable = false;
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
