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

async function manejarTeclaEnter(ev) {
    if (ev.target.classList.contains('tituloTarea') && ev.key === 'Enter' && ev.target.contentEditable === 'true') {
        ev.preventDefault();
        const tareaActual = ev.target.closest('.POST-tarea');
        const sesion = tareaActual.getAttribute('sesion');
        const estado = tareaActual.getAttribute('estado');
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

        // Elimina el listener anterior antes de agregar uno nuevo
        nuevoTitulo.removeEventListener('blur', manejarBlur);
        nuevoTitulo.addEventListener('blur', manejarBlur);

        // Usar requestAnimationFrame para enfocar después del repintado
        requestAnimationFrame(() => {
            nuevoTitulo.focus();
            // Colocar el cursor al inicio del campo editable
            const rango = document.createRange();
            const sel = window.getSelection();
            rango.setStart(nuevoTitulo, 0);
            rango.collapse(true);
            sel.removeAllRanges();
            sel.addRange(rango);
        });

        // Enviar la solicitud al servidor para crear la tarea real
        const data = {
            titulo: 'Nueva tarea',
            importancia: importancia,
            tipo: tipo,
            sesion: sesion,
            estado: estado
        };

        try {
            console.log('llamando a crearTarea desde crearTareaEnter');
            const rta = await enviarAjax('crearTarea', data);

            if (rta.success) {
                const {respuestaCompleta} = await window.reiniciarPost(rta.data.tareaId, 'tarea');

                if (respuestaCompleta && listaTareas) {
                    // Reemplazar la tarea duplicada con la tarea real
                    const nuevaTarea = document.createElement('div');
                    nuevaTarea.innerHTML = respuestaCompleta;
                    const tareaNueva = nuevaTarea.querySelector('.POST-tarea');

                    tareaDuplicada.replaceWith(tareaNueva);

                    const nuevoTituloReal = tareaNueva.querySelector('.tituloTarea');
                    nuevoTituloReal.contentEditable = true;
                    nuevoTituloReal.spellcheck = false;

                    // Elimina el listener anterior antes de agregar uno nuevo
                    nuevoTituloReal.removeEventListener('blur', manejarBlur);
                    nuevoTituloReal.addEventListener('blur', manejarBlur);

                    // Usar requestAnimationFrame para enfocar después del repintado
                    requestAnimationFrame(() => {
                        nuevoTituloReal.focus();
                        // Colocar el cursor al inicio del campo editable en la tarea real
                        const rango = document.createRange();
                        const seleccion = window.getSelection();
                        rango.setStart(nuevoTituloReal, 0);
                        rango.collapse(true);
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

function manejarBlur() {
    const nuevoTitulo = this;
    setTimeout(() => {
        if (!nuevoTitulo.matches(':focus')) {
            if (nuevoTitulo.textContent.trim() === '') {
                nuevoTitulo.textContent = 'Nueva tarea';
            }
            nuevoTitulo.contentEditable = false;
        }
    }, 100);
}
