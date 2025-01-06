/*
No borres las funciones comentadas 

mirad, el problema no lo identifico, cuando carga la primera vez, las sesiones aparecen correctamente, pero al recargar por ajax, las sesiones no aparecen

los atributos sesion la primera vez no son necesarios para general y archivado, tampoco deberían ser necesarios para ajax y por lo visto ese tampoco es el problema no tengo idea 

ya llevo mucho tiempo con este problema y he intentano de todo, por favor, haz algo con una solucion forzada o directa

primera vez
actualizarMapa: sesion (en el mapa original): generalPara la tarea ID: 337442
taskSesiones.js?ver=0.2.330:151 Procesando tarea 1: Tarea ID: 337442, Estado: pendiente, Sesión: "general". Tarea agregada a sección general. 
taskSesiones.js?ver=0.2.330:131 actualizarMapa: sesion (en el mapa original): archivadoPara la tarea ID: 337444
taskSesiones.js?ver=0.2.330:151 Procesando tarea 2: Tarea ID: 337444, Estado: archivado, Sesión: "archivado". Tarea agregada a Archivado. 
taskSesiones.js?ver=0.2.330:155 actualizarMapa: Iniciando actualización de mapa. Tareas encontradas: 2. Mapa final: {"general":[{}],"archivado":[{}]}. 
taskSesiones.js?ver=0.2.330:236 crearSeccion: Insertando tarea en sección General: ID 337442
taskSesiones.js?ver=0.2.330:242 crearSeccion: Iniciando creación de sección: General. Nombre de sección codificado: General. Buscando sección existente con data-valor: General. La sección General no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para General. Insertando 1 tareas en la sección General. Procesando tarea 1 de 1 para la sección General. Atributo data-seccion establecido como General para la tarea. Removiendo tarea de su padre actual. Tarea insertada en listaSec después de General. 
taskSesiones.js?ver=0.2.330:236 crearSeccion: Insertando tarea en sección Archivado: ID 337444
taskSesiones.js?ver=0.2.330:242 crearSeccion: Iniciando creación de sección: Archivado. Nombre de sección codificado: Archivado. Buscando sección existente con data-valor: Archivado. La sección Archivado no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para Archivado. Insertando 1 tareas en la sección Archivado. Procesando tarea 1 de 1 para la sección Archivado. Atributo data-seccion establecido como Archivado para la tarea. Removiendo tarea de su padre actual. Tarea insertada en listaSec después de Archivado. 
taskSesiones.js?ver=0.2.330:96 organizarSecciones: Iniciando reorganización de tareas... Divisores existentes eliminados. Mapa actualizado. Sección General creada con 1 tareas. Otras secciones encontradas: Ninguna. Sección Archivado creada con 1 tareas. Resumen de secciones: General (1), Archivado (1). 
taskSesiones.js?ver=0.2.330:116 generarLogFinal: Generando log final... Procesando elemento 1. Elemento P: General - Divisor. Procesando elemento 2. Elemento LI: Sección - General, ID - 337442. Procesando elemento 3. Elemento P: Archivado - Divisor. Procesando elemento 4. Elemento LI: Sección - Archivado, ID - 337444. Orden final: General - Divisor, General - 337442, Archivado - Divisor, Archivado - 337444
Despues de ajax
organizarSecciones: Eliminando divisor existente: General
taskSesiones.js?ver=0.2.330:56 organizarSecciones: Eliminando divisor existente: Archivado
taskSesiones.js?ver=0.2.330:131 actualizarMapa: sesion (en el mapa original): generalPara la tarea ID: 337442
taskSesiones.js?ver=0.2.330:151 Procesando tarea 1: Tarea ID: 337442, Estado: pendiente, Sesión: "general". Tarea agregada a sección general. 
taskSesiones.js?ver=0.2.330:131 actualizarMapa: sesion (en el mapa original): archivadoPara la tarea ID: 337444
taskSesiones.js?ver=0.2.330:151 Procesando tarea 2: Tarea ID: 337444, Estado: archivado, Sesión: "archivado". Tarea agregada a Archivado. 
taskSesiones.js?ver=0.2.330:155 actualizarMapa: Iniciando actualización de mapa. Tareas encontradas: 2. Mapa final: {"general":[{}],"archivado":[{}]}. 
taskSesiones.js?ver=0.2.330:236 crearSeccion: Insertando tarea en sección General: ID 337442
taskSesiones.js?ver=0.2.330:242 crearSeccion: Iniciando creación de sección: General. Nombre de sección codificado: General. Buscando sección existente con data-valor: General. La sección General no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para General. Insertando 1 tareas en la sección General. Procesando tarea 1 de 1 para la sección General. Atributo data-seccion establecido como General para la tarea. Removiendo tarea de su padre actual. Tarea insertada en listaSec después de General. 
taskSesiones.js?ver=0.2.330:236 crearSeccion: Insertando tarea en sección Archivado: ID 337444
taskSesiones.js?ver=0.2.330:242 crearSeccion: Iniciando creación de sección: Archivado. Nombre de sección codificado: Archivado. Buscando sección existente con data-valor: Archivado. La sección Archivado no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para Archivado. Insertando 1 tareas en la sección Archivado. Procesando tarea 1 de 1 para la sección Archivado. Atributo data-seccion establecido como Archivado para la tarea. Removiendo tarea de su padre actual. Tarea insertada en listaSec después de Archivado. 
taskSesiones.js?ver=0.2.330:96 organizarSecciones: Iniciando reorganización de tareas... Divisores existentes eliminados. Mapa actualizado. Sección General creada con 1 tareas. Otras secciones encontradas: Ninguna. Sección Archivado creada con 1 tareas. Resumen de secciones: General (1), Archivado (1). 
taskSesiones.js?ver=0.2.330:116 generarLogFinal: Generando log final... Procesando elemento 1. Elemento P: General - Divisor. Procesando elemento 2. Elemento LI: Sección - General, ID - 337442. Procesando elemento 3. Elemento P: Archivado - Divisor. Procesando elemento 4. Elemento LI: Sección - Archivado, ID - 337444. Orden final: General - Divisor, General - 337442, Archivado - Divisor, Archivado - 337444

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
        //console.log(`organizarSecciones: Eliminando divisor existente: ${divisor.textContent}`);
        listaSec.removeChild(divisor);
    });
    log += 'Divisores existentes eliminados. ';

    // Forzar la actualización del atributo 'sesion' en cada tarea
    const items = Array.from(listaSec.children).filter(item => item.tagName === 'LI');
    items.forEach(item => {
        let est = item.getAttribute('estado')?.toLowerCase() || '';
        if (est !== "archivado") {
            item.setAttribute('sesion', 'general');
        } else {
            item.setAttribute('sesion', 'archivado');
        }
    });

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

    //console.log(log);
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
    //console.log(log);
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
        //console.log("actualizarMapa: sesion (en el mapa original): " + item.getAttribute('sesion') + "Para la tarea ID: " + idPost)

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

        //console.log(logItem);
    });

    log += `Mapa final: ${JSON.stringify(mapa)}. `;
    //console.log(log);
}

//STEP 3

/*
Esto en verdad no esta funcionando bien 

crearSeccion: Iniciando creación de sección: General. Nombre de sección codificado: General. Buscando sección existente con data-valor: General. La sección General no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para General. Se limpiaron las tareas previas de la sección General. Insertando 1 tareas en la sección General. Procesando tarea 1 de 1 para la sección General. ID: 337442. Atributo data-seccion establecido como General para la tarea. Removiendo tarea de su padre actual. Insertando tarea en listaSec después de General. Verificando si el divisor original (General) sigue en el DOM... ERROR: El divisor original para General NO está en el DOM después de insertar la tarea 337442. No se encontró el divisor para General en el DOM. Creando uno nuevo.Nuevo divisor creado e insertado para General. Referencia a divisor actualizada para General. Tareas insertadas correctamente en la sección General. crearSeccion: Proceso de creación de sección General finalizado.
taskSesiones.js?ver=0.2.339:244 crearSeccion: Iniciando creación de sección: Archivado. Nombre de sección codificado: Archivado. Buscando sección existente con data-valor: Archivado. La sección Archivado no existe, creando nuevo divisor. Nuevo divisor creado y agregado a listaSec para Archivado. Se limpiaron las tareas previas de la sección Archivado. Insertando 1 tareas en la sección Archivado. Procesando tarea 1 de 1 para la sección Archivado. ID: 337444. Atributo data-seccion establecido como Archivado para la tarea. Removiendo tarea de su padre actual. Insertando tarea en listaSec después de Archivado. Verificando si el divisor original (Archivado) sigue en el DOM... ERROR: El divisor original para Archivado NO está en el DOM después de insertar la tarea 337444. No se encontró el divisor para Archivado en el DOM. Creando uno nuevo.Nuevo divisor creado e insertado para Archivado. Referencia a divisor actualizada para Archivado. Tareas insertadas correctamente en la sección Archivado. crearSeccion: Proceso de creación de sección Archivado finalizado.

La primera vez funciona bien pero la segunda vez que se reinicia dice que Iniciando creación de sección: General. pero en veradd no crea nada, necesito que verdad compruebe si la creo o porque demonios no aparece en el dom o que esta pasando, por favor arreglalo
*/
function crearSeccion(nom, items) {
    let log = `crearSeccion: Iniciando creación de sección: ${nom}. `;
    const nomCodificado = encodeURIComponent(nom);
    log += `Nombre de sección codificado: ${nomCodificado}. `;

    // Buscar el divisor al principio de la función
    let divisor = document.querySelector(`.divisorTarea[data-valor="${nomCodificado}"]`);
    log += `Buscando sección existente con data-valor: ${nomCodificado}. `;

    if (!divisor) {
        log += `La sección ${nom} no existe, creando nuevo divisor. `;
        divisor = crearNuevoDivisor(nom, nomCodificado);
        listaSec.appendChild(divisor);
        log += `Nuevo divisor creado y agregado a listaSec para ${nom}. `;
    } else {
        log += `Se encontró un divisor existente para ${nom}. `;
    }

    // Limpiar solo las tareas LI de la sección actual, no el divisor, incluyendo tareas completadas
    let siguiente = divisor.nextElementSibling;
    while (siguiente && siguiente.tagName === 'LI' && siguiente.dataset.seccion === nomCodificado) {
        log += `crearSeccion: Eliminando tarea existente en sección ${nom}: ID ${siguiente.getAttribute('id-post')}. `;
        listaSec.removeChild(siguiente);
        siguiente = divisor.nextElementSibling;
    }
    log += `Se limpiaron las tareas previas de la sección ${nom}. `;

    if (items.length === 0) {
        log += `La sección ${nom} no tiene tareas. `;
        divisor.textContent = `No hay tareas en la sección ${nom}`;
        divisor.style.color = 'gray';
        log += `Se actualizó el texto del divisor para ${nom}. `;
    } else {
        divisor.textContent = nom; // Restaurar el texto original si hay tareas
        divisor.style.color = ''; // Restaurar el color original
        log += `Insertando ${items.length} tareas en la sección ${nom}. `;
        let anterior = divisor;
        items.forEach((item, index) => {
            log += `Procesando tarea ${index + 1} de ${items.length} para la sección ${nom}. ID: ${item.getAttribute('id-post')}. `;
            item.setAttribute('data-seccion', nomCodificado);
            log += `Atributo data-seccion establecido como ${nomCodificado} para la tarea. `;

            if (item.parentNode) {
                log += `Removiendo tarea de su padre actual. `;
                item.parentNode.removeChild(item);
            }

            log += `Insertando tarea en listaSec después de ${anterior.tagName === 'P' ? anterior.textContent : 'tarea ' + anterior.getAttribute('id-post')}. `;
            listaSec.insertBefore(item, anterior.nextSibling);
            anterior = item;

            // Verificar si el divisor sigue en el DOM después de la inserción
            log += `Verificando si el divisor original (${nom}) sigue en el DOM... `;
            let divisorOriginal = document.querySelector(`.divisorTarea[data-valor="${nomCodificado}"]`);
            if (divisorOriginal) {
                log += `El divisor original para ${nom} SI está en el DOM después de insertar la tarea ${item.getAttribute('id-post')}. `;
            } else {
                log += `ERROR: El divisor original para ${nom} NO está en el DOM después de insertar la tarea ${item.getAttribute('id-post')}. `;

                // Buscar un nuevo divisor (podría haberse creado en una iteración anterior)
                divisor = document.querySelector(`.divisorTarea[data-valor="${nomCodificado}"]`);
                if (!divisor) {
                    log += `No se encontró el divisor para ${nom} en el DOM. Creando uno nuevo.`;
                    divisor = crearNuevoDivisor(nom, nomCodificado);
                    listaSec.insertBefore(divisor, item);
                    log += `Nuevo divisor creado e insertado para ${nom}. `;
                }
                // Clave: NO actualizar anterior aquí, solo cuando se inserta el divisor
            }
        });
        log += `Tareas insertadas correctamente en la sección ${nom}. `;
        anterior = divisor;
    }

    log += `crearSeccion: Proceso de creación de sección ${nom} finalizado.`;
    console.log(log);
}

// Función auxiliar para crear un nuevo divisor
function crearNuevoDivisor(nom, nomCodificado) {
    let divisor = document.createElement('p');
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

    return divisor;
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
            //console.log('Nombre de la sesión actualizado:', nuevaSesion.dataset.valor);
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
    //console.log(log);
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

*/