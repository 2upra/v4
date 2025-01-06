let mapa = {general: [], archivado: []};

window.dividirTarea = async function () {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    if (!listaSec) return;
    organizarSecciones();
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
        const sesion = item.getAttribute('sesion')?.toLowerCase();
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
    console.log(log + `Mapa actualizado: ${JSON.stringify(mapa)}`);
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
        if (item.dataset.seccion === valorDivisorCodificado) { // Usar el nombre codificado
            item.style.display = visible ? '' : 'none';
            log += `Tarea ID: ${item.getAttribute('id-post')}, Visibilidad: ${visible ? 'visible' : 'oculta'}. `;
        }
    });

    const flecha = divisor.querySelector('span:last-child');
    flecha.innerHTML = visible ? (window.fechaabajo || '↓') : (window.fechaallado || '↑');
    localStorage.setItem(`seccion-${valorDivisorCodificado}`, visible ? 'visible' : 'oculto');
    console.log(log);
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
            crearSesionFront(divisor); // Se llama a la función aquí, pasando el divisor como parámetro
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
    const nomCodificado = encodeURIComponent(nom);
    let divisor = document.querySelector(`[data-valor="${nomCodificado}"]`);

    if (items.length === 0) {
        if (divisor) {
            divisor.textContent = `No hay tareas en la sección ${nom}`;
            divisor.style.color = 'gray';
        }
        log += `Sección ${nom} vacía, se omite.`;
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
        divisor.textContent = nom;
        divisor.dataset.valor = nomCodificado;
        divisor.classList.add('divisorTarea', nomCodificado);

        const flecha = document.createElement('span');
        flecha.style.marginLeft = '5px';
        divisor.appendChild(flecha);
        listaSec.appendChild(divisor);
    }

    configurarInteraccionSeccion(divisor, nomCodificado, items);

    log += `Insertando ${items.length} tareas en la sección ${nom}. `;
    let anterior = divisor;
    items.forEach(item => {
        item.setAttribute('data-seccion', nomCodificado);
        if (item.parentNode) item.parentNode.removeChild(item);
        listaSec.insertBefore(item, anterior.nextSibling);
        anterior = item;
    });
    console.log(log);
}

function eliminarSeparadoresExistentes() {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    const separadores = Array.from(listaSec.children).filter(item => item.tagName === 'P' && item.classList.contains('divisorTarea'));
    separadores.forEach(separador => separador.remove());
}

function organizarSecciones() {
    let log = 'organizarSecciones: Reorganizando tareas... ';
    actualizarMapa();
    //eliminarSeparadoresExistentes();
    crearSeccion('General', mapa.general);

    const otrasSecciones = Object.keys(mapa).filter(seccion => seccion !== 'general' && seccion !== 'archivado');
    otrasSecciones.forEach(seccion => crearSeccion(seccion, mapa[seccion]));

    crearSeccion('Archivado', mapa.archivado);

    log += `Secciones reorganizadas: General (${mapa.general.length}), `;
    if (otrasSecciones.length > 0) {
        log += `${otrasSecciones.map(s => `${s} (${mapa[s].length})`).join(', ')}, `;
    }
    log += `Archivado (${mapa.archivado.length}). `;
    console.log(log);
    generarLogFinal();
}

function generarLogFinal() {
    const listaSec = document.querySelector('.social-post-list.clase-tarea');
    let log = '';
    const final = [];
    Array.from(listaSec.children).forEach(item => {
        if (item.tagName === 'LI') {
            const idPost = item.getAttribute('id-post');
            final.push(`${item.getAttribute('data-seccion') || 'Sin sección'} - ${idPost || 'sin ID'}`);
        } else if (item.tagName === 'P') {
            final.push(`${item.textContent} - Divisor`);
        }
    });
    log = `generarLogFinal: Orden final: ${final.join(', ')}`;
    console.log(log);
}

/*
aqui cuando reinicio esto, pasa esto

taskSesiones.js?ver=0.2.314:181  Uncaught (in promise) TypeError: Cannot read properties of null (reading 'addEventListener')
    at crearSesionFront (taskSesiones.js?ver=0.2.314:181:15)
    at window.dividirTarea (taskSesiones.js?ver=0.2.314:7:5)
    at initTareas (task.js?ver=0.2.314:31:16)
    at ajaxPage.js?ver=0.2.314:98:72
    at Array.forEach (<anonymous>)
    at initScripts (ajaxPage.js?ver=0.2.314:98:15)
    at reinit (ajaxPage.js?ver=0.2.314:102:9)
*/

function crearSesionFront() {
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
            console.log('Nombre de la sesión actualizado:', nuevaSesion.dataset.valor);
        });
    });
}

function hacerDivisoresEditables() {
    const divisores = document.querySelectorAll('.divisorTarea');

    divisores.forEach(divisor => {
        const valor = divisor.dataset.valor;
        const clase = divisor.classList;

        if (valor !== 'General' && valor !== 'Archivado') {
            divisor.contentEditable = true;

            let valorOriginal = valor; 

            divisor.addEventListener('blur', async () => {
                let textoEditado = divisor.textContent;
                
                if (textoEditado === '') {
                    textoEditado = valorOriginal;
                    divisor.textContent = textoEditado;
                }
                
                divisor.dataset.valor = textoEditado;
                
                if (textoEditado !== valorOriginal) {
                    let datos = {
                        valorOriginal: valorOriginal,
                        valorNuevo: textoEditado
                    };
                    try {
                        await enviarAjax('actualizarSesion', datos);
                        
                        valorOriginal = textoEditado;
                        console.log('Sesión actualizada y tareas reasignadas');
                    } catch (error) {
                        //console.error('Error al actualizar sesión:', error);
                        divisor.textContent = valorOriginal;
                        divisor.dataset.valor = valorOriginal;
                    }
                } else {
                    console.log('El nombre de la sesión no ha cambiado');
                }
            });
        }
    });
}

