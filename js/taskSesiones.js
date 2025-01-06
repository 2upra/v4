/*
No borres las funciones comentadas 

mirad, el problema no lo identifico, cuando carga la primera vez, las sesiones aparecen correctamente, pero al recargar por ajax, las sesiones no aparecen

los atributos sesion la primera vez no son necesarios para general y archivado, tampoco deberían ser necesarios para ajax y por lo visto ese tampoco es el problema no tengo idea 

primera vez
actualizarMapa: sesion (en el mapa original): Para la tarea ID: 337442
taskSesiones.js?ver=0.2.328:129 Procesando tarea 1: Tarea ID: 337442, Estado: pendiente, Sesión: "general". Tarea agregada a sección general. 
taskSesiones.js?ver=0.2.328:109 actualizarMapa: sesion (en el mapa original): Para la tarea ID: 337444
taskSesiones.js?ver=0.2.328:129 Procesando tarea 2: Tarea ID: 337444, Estado: archivado, Sesión: "". Tarea agregada a Archivado. 
taskSesiones.js?ver=0.2.328:133 actualizarMapa: Iniciando actualización de mapa. Tareas encontradas: 2. Mapa final: {"general":[{}],"archivado":[{}]}. 
taskSesiones.js?ver=0.2.328:214 crearSeccion: Insertando tarea en sección General: ID 337442
taskSesiones.js?ver=0.2.328:220 crearSeccion: Iniciando creación de sección: General. Nombre de sección codificado: General. Buscando sección existente con data-valor: General. La sección General no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para General. Insertando 1 tareas en la sección General. Procesando tarea 1 de 1 para la sección General. Atributo data-seccion establecido como General para la tarea. Removiendo tarea de su padre actual. Tarea insertada en listaSec después de General. 
taskSesiones.js?ver=0.2.328:214 crearSeccion: Insertando tarea en sección Archivado: ID 337444
taskSesiones.js?ver=0.2.328:220 crearSeccion: Iniciando creación de sección: Archivado. Nombre de sección codificado: Archivado. Buscando sección existente con data-valor: Archivado. La sección Archivado no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para Archivado. Insertando 1 tareas en la sección Archivado. Procesando tarea 1 de 1 para la sección Archivado. Atributo data-seccion establecido como Archivado para la tarea. Removiendo tarea de su padre actual. Tarea insertada en listaSec después de Archivado. 
taskSesiones.js?ver=0.2.328:74 organizarSecciones: Iniciando reorganización de tareas... Divisores existentes eliminados. Mapa actualizado. Sección General creada con 1 tareas. Otras secciones encontradas: Ninguna. Sección Archivado creada con 1 tareas. Resumen de secciones: General (1), Archivado (1). 
taskSesiones.js?ver=0.2.328:94 generarLogFinal: Generando log final... Procesando elemento 1. Elemento P: General - Divisor. Procesando elemento 2. Elemento LI: Sección - General, ID - 337442. Procesando elemento 3. Elemento P: Archivado - Divisor. Procesando elemento 4. Elemento LI: Sección - Archivado, ID - 337444. Orden final: General - Divisor, General - 337442, Archivado - Divisor, Archivado - 337444
Despues de ajax
organizarSecciones: Eliminando divisor existente: General
taskSesiones.js?ver=0.2.328:45 organizarSecciones: Eliminando divisor existente: Archivado
taskSesiones.js?ver=0.2.328:109 actualizarMapa: sesion (en el mapa original): Para la tarea ID: 337442
taskSesiones.js?ver=0.2.328:129 Procesando tarea 1: Tarea ID: 337442, Estado: pendiente, Sesión: "general". Tarea agregada a sección general. 
taskSesiones.js?ver=0.2.328:109 actualizarMapa: sesion (en el mapa original): Para la tarea ID: 337444
taskSesiones.js?ver=0.2.328:129 Procesando tarea 2: Tarea ID: 337444, Estado: archivado, Sesión: "". Tarea agregada a Archivado. 
taskSesiones.js?ver=0.2.328:133 actualizarMapa: Iniciando actualización de mapa. Tareas encontradas: 2. Mapa final: {"general":[{}],"archivado":[{}]}. 
taskSesiones.js?ver=0.2.328:214 crearSeccion: Insertando tarea en sección General: ID 337442
taskSesiones.js?ver=0.2.328:220 crearSeccion: Iniciando creación de sección: General. Nombre de sección codificado: General. Buscando sección existente con data-valor: General. La sección General no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para General. Insertando 1 tareas en la sección General. Procesando tarea 1 de 1 para la sección General. Atributo data-seccion establecido como General para la tarea. Removiendo tarea de su padre actual. Tarea insertada en listaSec después de General. 
taskSesiones.js?ver=0.2.328:214 crearSeccion: Insertando tarea en sección Archivado: ID 337444
taskSesiones.js?ver=0.2.328:220 crearSeccion: Iniciando creación de sección: Archivado. Nombre de sección codificado: Archivado. Buscando sección existente con data-valor: Archivado. La sección Archivado no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para Archivado. Insertando 1 tareas en la sección Archivado. Procesando tarea 1 de 1 para la sección Archivado. Atributo data-seccion establecido como Archivado para la tarea. Removiendo tarea de su padre actual. Tarea insertada en listaSec después de Archivado. 
taskSesiones.js?ver=0.2.328:74 organizarSecciones: Iniciando reorganización de tareas... Divisores existentes eliminados. Mapa actualizado. Sección General creada con 1 tareas. Otras secciones encontradas: Ninguna. Sección Archivado creada con 1 tareas. Resumen de secciones: General (1), Archivado (1). 
taskSesiones.js?ver=0.2.328:94 generarLogFinal: Generando log final... Procesando elemento 1. Elemento P: General - Divisor. Procesando elemento 2. Elemento LI: Sección - General, ID - 337442. Procesando elemento 3. Elemento P: Archivado - Divisor. Procesando elemento 4. Elemento LI: Sección - Archivado, ID - 337444. Orden final: General - Divisor, General - 337442, Archivado - Divisor, Archivado - 337444

por favor resuelvelo 

*/

let mapa = { general: [], archivado: [] };
const listaSec = document.querySelector('.social-post-list.clase-tarea');

window.dividirTarea = async function () {
    if (!listaSec) return;
    organizarSecciones();
    //hacerDivisoresEditables();
};

//STEP 1
function organizarSecciones() {
    let log = 'organizarSecciones: Iniciando reorganización de tareas... ';

    // Eliminar todos los divisores existentes antes de actualizar el mapa
    const divisoresExistentes = listaSec.querySelectorAll('.divisorTarea');
    divisoresExistentes.forEach(divisor => {
        console.log(`organizarSecciones: Eliminando divisor existente: ${divisor.textContent}`);
        listaSec.removeChild(divisor);
    });
    log += 'Divisores existentes eliminados. ';

    actualizarMapa();

    log += 'Mapa actualizado. ';

    crearSeccion('General', mapa.general);
    log += `Sección General creada con ${mapa.general.length} tareas. `;

    const otrasSecciones = Object.keys(mapa).filter(seccion => seccion !== 'general' && seccion !== 'archivado');
    log += `Otras secciones encontradas: ${otrasSecciones.length > 0 ? otrasSecciones.join(', ') : 'Ninguna'}. `;

    otrasSecciones.forEach(seccion => {
        crearSeccion(seccion, mapa[seccion]);
        log += `Sección ${seccion} creada con ${mapa[seccion].length} tareas. `;
    });

    crearSeccion('Archivado', mapa.archivado);
    log += `Sección Archivado creada con ${mapa.archivado.length} tareas. `;

    log += `Resumen de secciones: General (${mapa.general.length}), `;
    if (otrasSecciones.length > 0) {
        log += `${otrasSecciones.map(s => `${s} (${mapa[s].length})`).join(', ')}, `;
    }
    log += `Archivado (${mapa.archivado.length}). `;

    console.log(log);
    generarLogFinal();
}

function generarLogFinal() {
    let log = 'generarLogFinal: Generando log final... ';
    const final = [];
    Array.from(listaSec.children).forEach((item, index) => {
        log += `Procesando elemento ${index + 1}. `;
        if (item.tagName === 'LI') {
            const idPost = item.getAttribute('id-post');
            const seccion = item.getAttribute('data-seccion') || 'Sin sección';
            final.push(`${seccion} - ${idPost || 'sin ID'}`);
            log += `Elemento LI: Sección - ${seccion}, ID - ${idPost || 'sin ID'}. `;
        } else if (item.tagName === 'P') {
            final.push(`${item.textContent} - Divisor`);
            log += `Elemento P: ${item.textContent} - Divisor. `;
        }
    });
    log += `Orden final: ${final.join(', ')}`;
    console.log(log);
}

function actualizarMapa() {
    let log = 'actualizarMapa: Iniciando actualización de mapa. ';
    mapa = { general: [], archivado: [] };
    const items = Array.from(listaSec.children).filter(item => item.tagName === 'LI');

    log += `Tareas encontradas: ${items.length}. `;

    items.forEach((item, index) => {
        let logItem = `Procesando tarea ${index + 1}: `;
        let est = item.getAttribute('estado')?.toLowerCase() || '';
        const idPost = item.getAttribute('id-post');
        let sesion = item.getAttribute('sesion')?.toLowerCase() || '';
        console.log("actualizarMapa: sesion (en el mapa original): " + item.getAttribute('sesion') + "Para la tarea ID: " + idPost)

        if (!sesion && est !== "archivado") {
            sesion = 'general';
        }

        logItem += `Tarea ID: ${idPost}, Estado: ${est}, Sesión: "${sesion}". `;

        if (est === 'archivado') {
            mapa['archivado'].push(item);
            logItem += `Tarea agregada a Archivado. `;
        } else {
            if (!mapa[sesion]) {
                mapa[sesion] = [];
                logItem += `Sección ${sesion} creada en el mapa. `;
            }
            mapa[sesion].push(item);
            logItem += `Tarea agregada a sección ${sesion}. `;
        }

        console.log(logItem);
    });

    log += `Mapa final: ${JSON.stringify(mapa)}. `;
    console.log(log);
}

//STEP 3
function crearSeccion(nom, items) {
    let log = `crearSeccion: Iniciando creación de sección: ${nom}. `;

    // Codificar el nombre de la sección para usarlo como data-valor
    const nomCodificado = encodeURIComponent(nom);
    log += `Nombre de sección codificado: ${nomCodificado}. `;

    // Buscar si la sección ya existe
    let divisor = document.querySelector(`.divisorTarea[data-valor="${nomCodificado}"]`);
    log += `Buscando sección existente con data-valor: ${nomCodificado}. `;

    // Si no hay tareas para la sección:
    if (items.length === 0) {
        log += `La sección ${nom} no tiene tareas. `;
        if (divisor) {
            divisor.textContent = `No hay tareas en la sección ${nom}`;
            divisor.style.color = 'gray';
            log += `Se actualizó el texto del divisor para ${nom}. `;
        } else {
            log += `No se encontró un divisor para ${nom}. `;
        }
        log += `Sección ${nom} vacía, se omite.`;
        console.log(log);
        return;
    }

    // Si la sección no existe, crearla
    if (!divisor) {
        log += `La sección ${nom} no existe, creando nuevo divisor. `;
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

        // Crear la flecha para expandir/contraer
        const flecha = document.createElement('span');
        flecha.style.marginLeft = '5px';
        divisor.appendChild(flecha);

        // Agregar la sección a la lista de secciones
        listaSec.appendChild(divisor);
        log += `Nuevo divisor creado y agregado a listaSec para ${nom}. `;
    } else {
        log += `Se encontró un divisor existente para ${nom}. `;
        // Limpiar el contenido anterior relacionado con la sección
        let siguiente = divisor.nextElementSibling;
        while (siguiente && siguiente.tagName === 'LI' && siguiente.dataset.seccion === nomCodificado) {
            console.log(`crearSeccion: Eliminando tarea existente en sección ${nom}: ID ${siguiente.getAttribute('id-post')}`);
            listaSec.removeChild(siguiente);
            siguiente = divisor.nextElementSibling;
        }
        log += `Se limpiaron las tareas previas de la sección ${nom}. `;
    }

    //no borrar esto
    //configurarInteraccionSeccion(divisor, nomCodificado, items); 

    // Insertar las tareas en la sección
    log += `Insertando ${items.length} tareas en la sección ${nom}. `;
    let anterior = divisor;
    items.forEach((item, index) => {
        log += `Procesando tarea ${index + 1} de ${items.length} para la sección ${nom}. `;
        item.setAttribute('data-seccion', nomCodificado);
        log += `Atributo data-seccion establecido como ${nomCodificado} para la tarea. `;

        if (item.parentNode) {
            log += `Removiendo tarea de su padre actual. `;
            item.parentNode.removeChild(item);
        }

        console.log(`crearSeccion: Insertando tarea en sección ${nom}: ID ${item.getAttribute('id-post')}`);
        listaSec.insertBefore(item, anterior.nextSibling);
        log += `Tarea insertada en listaSec después de ${anterior.textContent}. `;
        anterior = item;
    });

    console.log(log);
}

/*
//STEP 4
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

//STEP 5
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


//STEP 6
function alternarVisibilidadSeccion(divisor) {
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

function eliminarSeparadoresExistentes() {
    const separadores = Array.from(listaSec.children).filter(item => item.tagName === 'P' && item.classList.contains('divisorTarea'));
    separadores.forEach(separador => separador.remove());
}

//STEP 7
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

*/