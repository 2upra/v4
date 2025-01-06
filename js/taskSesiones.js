let mapa = {general: [], archivado: []};
const listaSec = document.querySelector('.social-post-list.clase-tarea');

//la primera vez que ejecuto dividirTarea funciona bien pero al actualizar por ajax no se crean las sesiones
window.dividirTarea = async function () {
    if (!listaSec) return;
    organizarSecciones();
    hacerDivisoresEditables();
    window.addEventListener('reiniciar', organizarSecciones);
};

function actualizarMapa() {
    let log = '';
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
    //console.log(log + `Mapa actualizado: ${JSON.stringify(mapa)}`);
}

function alternarVisibilidadSeccion(divisor) {
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

function configurarInteraccionSeccion(divisor, nomCodificado) {
    const nom = decodeURIComponent(nomCodificado);
    const flecha = divisor.querySelector('span:last-child');
    let visible = localStorage.getItem(`seccion-${nomCodificado}`) !== 'oculto';
    const items = Array.from(listaSec.children).filter(item => item.tagName === 'LI' && item.dataset.seccion === nomCodificado);
    items.forEach(item => (item.style.display = visible ? '' : 'none'));
    flecha.innerHTML = visible ? window.fechaabajo || '↓' : window.fechaallado || '↑';

    divisor.onclick = event => {
        event.stopPropagation();
        alternarVisibilidadSeccion(divisor);
    };

    if (nom === 'General') {
        const iconoAgregar = document.createElement('span');
        iconoAgregar.innerHTML = window.iconoPlus;
        iconoAgregar.style.marginLeft = 'auto';
        iconoAgregar.classList.add('iconoPlus');
        divisor.insertBefore(iconoAgregar, flecha.nextSibling);

        iconoAgregar.onclick = event => {
            event.stopPropagation();
            crearSesionFront(divisor);
        };
    }

    if (nom !== 'General' && nom !== 'Archivado') {
        divisor.contentEditable = true;

        divisor.onblur = async () => {
            let valorOriginal = nom;
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
                    console.log('Sesión actualizada y tareas reasignadas');
                } catch (error) {
                    console.error('Error al actualizar sesión:', error);
                    divisor.textContent = valorOriginal;
                    divisor.dataset.valor = encodeURIComponent(valorOriginal);
                }
            } else {
                console.log('El nombre de la sesión no ha cambiado');
            }
        };
    }
}

function crearSeccion(nom, items) {
    const nomCodificado = encodeURIComponent(nom);
    let divisor = document.querySelector(`[data-valor="${nomCodificado}"]`);

    if (items.length === 0 && divisor) {
        divisor.textContent = `No hay tareas en la sección ${nom}`;
        divisor.style.color = 'gray';
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
        divisor.classList.add('divisorTarea');

        const flecha = document.createElement('span');
        flecha.style.marginLeft = '5px';
        divisor.appendChild(flecha);
        listaSec.appendChild(divisor);
    }

    configurarInteraccionSeccion(divisor, nomCodificado);

    let anterior = divisor;
    items.forEach(item => {
        item.setAttribute('data-seccion', nomCodificado);
        if (item.parentNode) item.parentNode.removeChild(item);
        listaSec.insertBefore(item, anterior.nextSibling);
        anterior = item;
    });
}

function eliminarSeparadoresExistentes() {
    const separadores = Array.from(listaSec.children).filter(item => item.tagName === 'P' && item.classList.contains('divisorTarea'));
    separadores.forEach(separador => separador.remove());
}

function configurarInteraccionesDivisores() {
    const divisores = document.querySelectorAll('.divisorTarea');
    divisores.forEach(divisor => {
        const nomCodificado = divisor.dataset.valor;
        configurarInteraccionSeccion(divisor, nomCodificado);
    });
}

function organizarSecciones() {
    actualizarMapa();
    eliminarSeparadoresExistentes();
    crearSeccion('General', mapa.general);

    const otrasSecciones = Object.keys(mapa).filter(seccion => seccion !== 'general' && seccion !== 'archivado');
    otrasSecciones.forEach(seccion => crearSeccion(seccion, mapa[seccion]));

    crearSeccion('Archivado', mapa.archivado);

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

    nuevaSesion.onblur = () => {
        let textoEditado = nuevaSesion.textContent;
        if (textoEditado == '') {
            textoEditado = 'Nueva sesión';
            nuevaSesion.textContent = textoEditado;
        }
        nuevaSesion.dataset.valor = encodeURIComponent(textoEditado);
        console.log('Nombre de la sesión actualizado:', nuevaSesion.dataset.valor);
    };
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
                        //console.log('Sesión actualizada y tareas reasignadas');
                    } catch (error) {
                        //console.error('Error al actualizar sesión:', error);
                        divisor.textContent = valorOriginal;
                        divisor.dataset.valor = valorOriginal;
                    }
                } else {
                    //console.log('El nombre de la sesión no ha cambiado');
                }
            });
        }
    });
}
