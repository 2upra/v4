let temporizadorGuardado = null;
window.initEnter = function () {
    const tit = document.getElementById('tituloTarea');

    if (tit) {
        crearTareaEnter();
    }
};

async function crearTareaEnter() {
    const contenedor = document.querySelector('.clase-tarea');
    contenedor.addEventListener('keydown', manejarTeclaEnter);
}

//esto funciona bien solo que el cursor de editar texto al momento de recibir la tarea, debe estar al final, no al principio,
async function manejarTeclaEnter(ev) {
    if (ev.target.classList.contains('tituloTarea') && ev.key === 'Enter' && ev.target.contentEditable === 'true') {
        ev.preventDefault();
        const tareaActual = ev.target.closest('.POST-tarea');
        const sesion = tareaActual.getAttribute('sesion');
        const estado = tareaActual.getAttribute('estado');
        const padre = tareaActual.getAttribute('padre');
        const importancia = tareaActual.getAttribute('importancia');
        const tipo = tareaActual.getAttribute('tipo-tarea');
        const listaTareas = document.querySelector('.tab.active .social-post-list.clase-tarea');

        // Duplicar la tarea actual para respuesta instantánea
        const tareaDuplicada = tareaActual.cloneNode(true);
        tareaActual.after(tareaDuplicada);

        const nuevoTitulo = tareaDuplicada.querySelector('.tituloTarea');
        nuevoTitulo.textContent = '';
        nuevoTitulo.contentEditable = true;
        nuevoTitulo.spellcheck = false;
        nuevoTitulo.setAttribute('placeholder', 'Nueva tarea');

        requestAnimationFrame(() => {
            nuevoTitulo.focus();
            const rango = document.createRange();
            const sel = window.getSelection();
            rango.selectNodeContents(nuevoTitulo);
            rango.collapse(false);
            sel.removeAllRanges();
            sel.addRange(rango);
        });

        const data = {
            titulo: 'Nueva tarea',
            importancia: importancia,
            tipo: tipo,
            sesion: sesion,
            estado: estado,
            padre: padre
        };

        try {
            console.log('llamando a crearTarea desde crearTareaEnter');
            const rta = await enviarAjax('crearTarea', data);

            if (rta.success) {
                const respuestaCompleta = await window.reiniciarPost(rta.data.tareaId, 'tarea');

                if (respuestaCompleta && listaTareas) {
                    const nuevaTarea = document.createElement('div');
                    nuevaTarea.innerHTML = respuestaCompleta;
                    const tareaNueva = nuevaTarea.querySelector('.POST-tarea');

                    tareaDuplicada.replaceWith(tareaNueva);

                    const nuevoTituloReal = tareaNueva.querySelector('.tituloTarea');
                    nuevoTituloReal.contentEditable = true;
                    nuevoTituloReal.spellcheck = false;
                    nuevoTituloReal.dataset.tareaId = rta.data.tareaId; // Almacenar el ID de la tarea

                    // Agregar el evento input para guardar el título
                    nuevoTituloReal.addEventListener('input', manejarCambioTitulo);

                    requestAnimationFrame(() => {
                        nuevoTituloReal.focus();
                        const rango = document.createRange();
                        const seleccion = window.getSelection();
                        rango.selectNodeContents(nuevoTituloReal);
                        rango.collapse(false);
                        seleccion.removeAllRanges();
                        seleccion.addRange(rango);
                    });

                    initTareas();
                    window.guardarOrden();
                } else {
                    let log = 'Error al crear tarea.';
                    log += ' No se recibió respuesta o no se encontró la lista de tareas.';
                    console.error(log);
                    tareaDuplicada.remove();
                }
            } else {
                let log = 'Error al crear tarea.';
                if (rta.data) {
                    log += ' Detalles: ' + rta.data;
                }
                console.error(log);
                tareaDuplicada.remove();
            }
        } catch (err) {
            console.error('Error al crear tarea:', err);
            tareaDuplicada.remove();
        }
    }
}

//esto es importante porque al inicio se necesita manejar de esta forma el cambio de titulo
function manejarCambioTitulo() {
    const nuevoTitulo = this;
    const tareaId = nuevoTitulo.dataset.tareaId;

    clearTimeout(temporizadorGuardado); // Reiniciar el temporizador

    temporizadorGuardado = setTimeout(() => {
        const tituloActualizado = nuevoTitulo.textContent.trim();

        if (tituloActualizado === '' || tituloActualizado === 'Nueva tarea') {
            return;
        }

        const data = {
            id: tareaId,
            titulo: tituloActualizado
        };

        enviarAjax('modificarTarea', data)
            .then(rta => {
                if (rta.success) {
                    console.log('Título de tarea actualizado con éxito.');
                } else {
                    let m = 'Error al actualizar el título de la tarea.';
                    if (rta.data) m += ' Detalles: ' + rta.data;
                    alert(m);
                }
            })
            .catch(err => {
                console.error('Error al actualizar el título de la tarea:', err);
            });
    }, 1000); // Guardar después de 1 segundo de inactividad
}
