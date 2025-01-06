let mapa = { general: [], archivado: [] };
const listaSec = document.querySelector('.social-post-list.clase-tarea');

window.dividirTarea = async function () {
    if (!listaSec) return;
    organizarSecciones();
    hacerDivisoresEditables();
    window.addEventListener('reiniciar', organizarSecciones);
};

function actualizarMapa() {
    let log = '';
    mapa = { general: [], archivado: [] };
    const items = Array.from(listaSec.children).filter(item => item.tagName === 'LI');
    const separadoresExistentes = Array.from(listaSec.children).filter(item => item.tagName === 'P' && item.classList.contains('divisorTarea')).map(divisor => decodeURIComponent(divisor.dataset.valor));

    log = `actualizarMapa: Tareas encontradas: ${items.length}. `;
    items.forEach(item => {
        const est = item.getAttribute('estado')?.toLowerCase();
        let sesion = item.getAttribute('sesion')?.toLowerCase();
        const idPost = item.getAttribute('id-post');
        log += `Tarea ID: ${idPost}, Estado: ${est}, Sesión: ${sesion}. `;

        if (est === 'archivado') {
            mapa['archivado'].push(item);
        } else if (est === 'pendiente') {
            if (!sesion || sesion === '' || sesion === 'pendiente' || !separadoresExistentes.includes(sesion)) {
                mapa['general'].push(item);
                item.removeAttribute('sesion'); // Eliminar la sesión si no existe el separador
                sesion = 'general';
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
    const valorDivisorCodificado = divisor.dataset.valor;
    const valorDivisor = decodeURIComponent(valorDivisorCodificado);
    const items = Array.from(listaSec.children).filter(item => item.tagName === 'LI');
    let visible = localStorage.getItem(`seccion-${valorDivisorCodificado}`) !== 'oculto';
    visible = !visible;
    let log = `alternarVisibilidadSeccion: Alternando visibilidad de la sección ${valorDivisor}. `;

    items.forEach(item => {
        if (item.dataset.seccion === valorDivisorCodificado) {
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
    const nom = decodeURIComponent(nomCodificado);
    const flecha = divisor.querySelector('span:last-child');
    let visible = localStorage.getItem(`seccion-${nomCodificado}`) !== 'oculto';
    items.forEach(item => (item.style.display = visible ? '' : 'none'));
    flecha.innerHTML = visible ? window.fechaabajo || '↓' : window.fechaallado || '↑';

    const iconoAgregar = divisor.querySelector('.iconoPlus');
    if (nom === 'General' && !iconoAgregar) {
        const nuevoIconoAgregar = document.createElement('span');
        nuevoIconoAgregar.innerHTML = window.iconoPlus;
        nuevoIconoAgregar.style.marginLeft = 'auto';
        nuevoIconoAgregar.classList.add('iconoPlus');
        divisor.insertBefore(nuevoIconoAgregar, flecha.nextSibling);

        nuevoIconoAgregar.onclick = event => {
            event.stopPropagation();
            crearSesionFront(divisor);
        };
    }

    divisor.onclick = event => {
        event.stopPropagation();
        alternarVisibilidadSeccion(divisor);
    };
}

function crearSeccion(nom, items) {
    let log = `crearSeccion: Creando sección: ${nom}. `;
    const nomCodificado = encodeURIComponent(nom);
    let divisor = document.querySelector(`[data-valor="${nomCodificado}"]`);

    if (!divisor) {
        divisor = document.createElement('p');
        divisor.style.fontWeight = 'bold';
        divisor.style.cursor = 'pointer';
        divisor.style.padding = '5px 20px';
        divisor.style.marginRight = 'auto';
        divisor.style.display = 'flex';
        divisor.style.width = '100%';
        divisor.style.alignItems = 'center';
        divisor.dataset.valor = nomCodificado;
        divisor.classList.add('divisorTarea', nomCodificado);
        listaSec.appendChild(divisor);
    }

    divisor.textContent = nom;
    const flecha = document.createElement('span');
    flecha.style.marginLeft = '5px';
    divisor.appendChild(flecha);

    configurarInteraccionSeccion(divisor, nomCodificado, items);

    if (items.length === 0) {
        divisor.textContent = `No hay tareas en la sección ${nom}`;
        divisor.style.color = 'gray';
        log += `Sección ${nom} vacía, se indica.`;
    }

    log += `Insertando ${items.length} tareas en la sección ${nom}. `;
    let anterior = divisor;
    items.forEach(item => {
        item.setAttribute('data-seccion', nomCodificado);
        if (item.parentNode) item.parentNode.removeChild(item);
        listaSec.insertBefore(item, anterior.nextSibling);
        anterior = item;
    });
}

// No se necesita la función eliminarSeparadoresExistentes()

function organizarSecciones() {
    let log = 'organizarSecciones: Reorganizando tareas... ';
    actualizarMapa();
    //  No llamar a eliminarSeparadoresExistentes()
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
    //console.log(log);
}

function crearSesionFront(divisorGeneral) {
    const textoInicial = 'Nueva sesión';
    const nuevaSesion = document.createElement('p');
    nuevaSesion.dataset.valor = encodeURIComponent(textoInicial);
    nuevaSesion.classList.add('divisorTarea');
    nuevaSesion.contentEditable = true;
    nuevaSesion.textContent = textoInicial;

    const spanFlecha = document.createElement('span');
    spanFlecha.style.marginLeft = '5px';
    spanFlecha.innerHTML = window.fechaabajo;

    nuevaSesion.appendChild(spanFlecha);

    divisorGeneral.parentNode.insertBefore(nuevaSesion, divisorGeneral.nextSibling);

    nuevaSesion.focus();

    nuevaSesion.addEventListener('blur', () => {
        let textoEditado = nuevaSesion.textContent;
        if (textoEditado == '') {
            textoEditado = 'Nueva sesión';
            nuevaSesion.textContent = textoEditado;
        }
        nuevaSesion.dataset.valor = encodeURIComponent(textoEditado);
        //console.log('Nombre de la sesión actualizado:', nuevaSesion.dataset.valor);
    });
}

function hacerDivisoresEditables() {
    const divisores = document.querySelectorAll('.divisorTarea');

    divisores.forEach(divisor => {
        const valor = decodeURIComponent(divisor.dataset.valor);
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

                divisor.dataset.valor = encodeURIComponent(textoEditado);

                if (textoEditado !== valorOriginal) {
                    let datos = {
                        valorOriginal: encodeURIComponent(valorOriginal),
                        valorNuevo: encodeURIComponent(textoEditado)
                    };
                    try {
                        await enviarAjax('actualizarSesion', datos);

                        valorOriginal = textoEditado;
                        //console.log('Sesión actualizada y tareas reasignadas');
                    } catch (error) {
                        //console.error('Error al actualizar sesión:', error);
                        divisor.textContent = valorOriginal;
                        divisor.dataset.valor = encodeURIComponent(valorOriginal);
                    }
                } else {
                    //console.log('El nombre de la sesión no ha cambiado');
                }
            });
        }
    });
}