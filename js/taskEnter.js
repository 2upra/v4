// No necesitamos 'temporizadorGuardado' global aquí si cada elemento maneja el suyo.
// let temporizadorGuardado = null; // Comentado o eliminado

window.initEnter = function () {
    const tit = document.getElementById('tituloTarea'); // Asumo que te refieres al input principal, no a los de las tareas existentes.
                                                       // Si es para tareas existentes, el querySelector en crearTareaEnter es más adecuado.
    if (tit) { // Si te refieres al input principal para crear la *primera* tarea.
        // No parece que initEnter esté relacionado directamente con el problema de Enter *en una tarea existente*.
        // El listener principal para Enter en tareas existentes se añade en crearTareaEnter.
        // Si este initEnter es para otra cosa, lo dejamos.
        // Si es para asegurar que crearTareaEnter se llame, entonces:
    }
    // Considero que crearTareaEnter ya se llama desde initTareas o similar cuando la lista de tareas está presente.
    // Por ahora, asumo que la lógica de initEnter y la condición `if (tit)`
    // son para un contexto diferente al de presionar Enter en un '.tituloTarea' editable.
    // El event listener clave es el de 'keydown' en '.clase-tarea' añadido por crearTareaEnter.
    // Vamos a asegurar que crearTareaEnter se ejecute si hay un contenedor de tareas.
    const contenedorTareas = document.querySelector('.clase-tarea');
    if (contenedorTareas) {
        crearTareaEnter();
    }
};

async function crearTareaEnter() {
    const contenedor = document.querySelector('.clase-tarea');
    if (contenedor) { // Asegurarse de que el contenedor exista
        // Evitar añadir múltiples listeners si la función se llama varias veces
        if (contenedor.dataset.enterListenerAnadido) return;

        contenedor.addEventListener('keydown', manejarTeclaEnter);
        contenedor.dataset.enterListenerAnadido = 'true';
    }
}

async function manejarTeclaEnter(ev) {
    if (ev.target.classList.contains('tituloTarea') && ev.key === 'Enter' && ev.target.contentEditable === 'true') {
        ev.preventDefault();

        const tituloOriginal = ev.target;

        // CAMBIO IMPORTANTE: Hacer que la tarea original deje de ser editable.
        // Esto dispara su propio evento 'blur', que llama a 'salirEdicion' y luego 'guardarEdicion'.
        tituloOriginal.contentEditable = false;

        const tareaActual = tituloOriginal.closest('.POST-tarea');
        if (!tareaActual) {
            console.error("manejarTeclaEnter: No se pudo encontrar el elemento .POST-tarea padre.");
            return;
        }

        const sesion = tareaActual.getAttribute('data-sesion');
        const estado = tareaActual.getAttribute('estado'); // Usualmente 'pendiente' para nuevas
        const padre = tareaActual.getAttribute('padre');
        const importanciaTarea = tareaActual.getAttribute('importancia'); // Renombrado para evitar conflicto con objeto global
        const tipo = tareaActual.getAttribute('tipo-tarea');
        const listaTareas = document.querySelector('.tab.active .social-post-list.clase-tarea');

        if (!listaTareas) {
            console.error("manejarTeclaEnter: No se encontró la lista de tareas (.social-post-list).");
            return;
        }

        const tareaDuplicada = tareaActual.cloneNode(true);
        tareaDuplicada.removeAttribute('id-post'); // El ID será nuevo
        tareaDuplicada.removeAttribute('data-id-post'); // Si también usas este
        // Considera limpiar otros atributos que no deben heredarse ciegamente, si aplica.

        tareaActual.after(tareaDuplicada);

        const nuevoTitulo = tareaDuplicada.querySelector('.tituloTarea');
        if (!nuevoTitulo) {
            console.error("manejarTeclaEnter: No se encontró .tituloTarea en la tarea duplicada.");
            tareaDuplicada.remove();
            return;
        }

        nuevoTitulo.textContent = '';
        nuevoTitulo.setAttribute('placeholder', 'Nueva tarea');
        nuevoTitulo.contentEditable = true;
        nuevoTitulo.spellcheck = false;
        // Limpiar dataset de ID si existiera en el clon
        delete nuevoTitulo.dataset.tareaId;
        delete nuevoTitulo.dataset.tarea;


        requestAnimationFrame(() => {
            nuevoTitulo.focus();
            const rango = document.createRange();
            const sel = window.getSelection();
            if (sel) {
                rango.selectNodeContents(nuevoTitulo);
                rango.collapse(false);
                sel.removeAllRanges();
                sel.addRange(rango);
            }
        });

        const datosCreacion = {
            titulo: 'Nueva tarea', // Título por defecto para el backend
            importancia: importanciaTarea,
            tipo: tipo,
            sesion: sesion,
            estado: 'pendiente', // Nueva tarea siempre pendiente
            padre: null // Por defecto, la nueva tarea no es subtarea, puedes cambiar esto si necesitas heredar 'padre'
        };

        try {
            console.log('manejarTeclaEnter: llamando a crearTarea con datos:', datosCreacion);
            const rta = await enviarAjax('crearTarea', datosCreacion);

            if (rta.success && rta.data && rta.data.tareaId) {
                const htmlNuevaTarea = await window.reiniciarPost(rta.data.tareaId, 'tarea');

                if (htmlNuevaTarea) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = htmlNuevaTarea;
                    const tareaNuevaReal = tempDiv.querySelector('.POST-tarea');

                    if (tareaNuevaReal) {
                        tareaDuplicada.replaceWith(tareaNuevaReal);
                        const nuevoTituloReal = tareaNuevaReal.querySelector('.tituloTarea');

                        if (nuevoTituloReal) {
                            nuevoTituloReal.contentEditable = true;
                            nuevoTituloReal.spellcheck = false;
                            nuevoTituloReal.dataset.tareaId = rta.data.tareaId; // ID para manejarCambioTitulo
                            nuevoTituloReal.dataset.tarea = rta.data.tareaId;  // ID para editarTarea -> guardarEdicion

                            nuevoTituloReal.addEventListener('input', manejarCambioTitulo);

                            requestAnimationFrame(() => {
                                nuevoTituloReal.focus();
                                const rangoReal = document.createRange();
                                const seleccionReal = window.getSelection();
                                if (seleccionReal) {
                                    rangoReal.selectNodeContents(nuevoTituloReal);
                                    rangoReal.collapse(false);
                                    seleccionReal.removeAllRanges();
                                    seleccionReal.addRange(rangoReal);
                                }
                            });
                        } else {
                             console.error('manejarTeclaEnter: nuevoTituloReal no encontrado en HTML de reiniciarPost.');
                        }
                        // initTareas y guardarOrden se llaman después de que la nueva tarea esté en el DOM
                        // y su título sea editable, para que los listeners se apliquen correctamente.
                        initTareas();
                        window.guardarOrden(); // Asumo que esto es necesario tras añadir una tarea
                    } else {
                        console.error('manejarTeclaEnter: .POST-tarea no encontrado en HTML de reiniciarPost.');
                        tareaDuplicada.remove();
                    }
                } else {
                    console.error('manejarTeclaEnter: No se recibió HTML de reiniciarPost.');
                    tareaDuplicada.remove();
                }
            } else {
                let log = 'manejarTeclaEnter: Error en respuesta de crearTarea.';
                log += rta.data ? ` Detalles: ${rta.data}` : (rta.message ? ` Mensaje: ${rta.message}` : ' Sin detalles.');
                console.error(log);
                tareaDuplicada.remove();
            }
        } catch (err) {
            console.error('manejarTeclaEnter: Excepción al crear tarea:', err);
            tareaDuplicada.remove();
        }
    }
}

function manejarCambioTitulo() {
    const nuevoTituloEl = this; // Es el div .tituloTarea
    const tareaId = nuevoTituloEl.dataset.tareaId || nuevoTituloEl.dataset.tarea;

    // Usar una propiedad en el elemento para el temporizador, en lugar de una global
    if (nuevoTituloEl._temporizadorGuardado) {
        clearTimeout(nuevoTituloEl._temporizadorGuardado);
    }

    nuevoTituloEl._temporizadorGuardado = setTimeout(() => {
        delete nuevoTituloEl._temporizadorGuardado; // Limpiar la referencia al temporizador una vez ejecutado

        const tituloActualizado = nuevoTituloEl.textContent.trim();

        // No guardar si el título está vacío o si sigue siendo el placeholder "Nueva tarea"
        // (a menos que el usuario explícitamente quiera una tarea llamada "Nueva tarea"
        // y el placeholder fuera diferente, lo cual no es el caso aquí).
        if (tituloActualizado === '' || tituloActualizado === 'Nueva tarea') {
            // console.log(`manejarCambioTitulo: Título "${tituloActualizado}" para tarea ${tareaId} no guardado (vacío o default).`);
            return;
        }

        const data = {
            id: tareaId,
            titulo: tituloActualizado
        };
        // console.log(`manejarCambioTitulo: Guardando título para tarea ${tareaId}: "${tituloActualizado}"`);
        enviarAjax('modificarTarea', data)
            .then(rta => {
                if (rta.success) {
                    // console.log(`manejarCambioTitulo: Título de tarea ${tareaId} actualizado con éxito.`);
                    // Si guardarEdicion usa data-valor-anterior, podríamos actualizarlo aquí:
                    // nuevoTituloEl.dataset.valorAnterior = tituloActualizado;
                } else {
                    let m = `manejarCambioTitulo: Error al actualizar título de tarea ${tareaId}.`;
                    if (rta.data) m += ' Detalles: ' + rta.data;
                    console.error(m); // Cambiado alert por console.error para no ser intrusivo
                }
            })
            .catch(err => {
                console.error(`manejarCambioTitulo: Excepción al actualizar título de tarea ${tareaId}:`, err);
            });
    }, 1000); // Guardar después de 1 segundo de inactividad
}