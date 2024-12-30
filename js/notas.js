//funcion que se reinicia cada vez que cambia de pagina por ajax
function initNotas() {
    crearNota();
    borrarLasNotas();
    editarNota();
}


function crearNota() {
    const ul = document.querySelector('.clase-notas'); // Contenedor de las notas

    // Usar un solo evento de clic en el contenedor
    ul.addEventListener('click', handleClick);

    function handleClick(event) {
        const nota = event.target.closest('.agregarNuevaNota'); // Verifica si el clic fue en una nota
        if (!nota || nota.id !== 'agregarNuevaNota') return; // Ignorar clics fuera del botón o notas no válidas

        prepararEdicion(nota);
    }

    function prepararEdicion(nota) {
        const texto = nota.querySelector('.contenidoNotaP');
        if (!texto) return;

        // Eliminar ID y preparar para edición
        nota.removeAttribute('id');
        nota.parentElement.classList.add('editandoNota'); // Agregar clase al padre
        texto.contentEditable = 'true';
        texto.textContent = '';
        texto.focus();

        // Asignar los eventos con funciones nombradas (solo una vez)
        texto.addEventListener('keydown', handleKeydown);
        texto.addEventListener('blur', handleBlur);
    }

    async function handleKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            await guardarNota(this, this.closest('.agregarNuevaNota'));
        }
    }

    async function handleBlur() {
        await guardarNota(this, this.closest('.agregarNuevaNota'));
    }

    async function guardarNota(texto, nota) {
        console.log('Guardando nota...');
        const contenido = texto.textContent;

        if (contenido.trim() !== '') {
            const data = {contenido};
            try {
                const respuesta = await enviarAjax('crearNota', data);
                if (respuesta) {
                    // Remover los listeners para evitar duplicaciones
                    texto.removeEventListener('keydown', handleKeydown);
                    texto.removeEventListener('blur', handleBlur);

                    // Eliminar la nota actual en edición
                    const liEditando = document.querySelector('.editandoNota');
                    if (liEditando) {
                        liEditando.remove();
                    }

                    // Reiniciar contenido y agregar nueva nota
                    await window.reiniciarContenido(false, true, 'notas', false, () => {
                        const nuevoLi = document.createElement('li');
                        nuevoLi.classList.add('POST-notas', 'EDYQHV');
                        nuevoLi.setAttribute('filtro', 'notas');
                        nuevoLi.setAttribute('data-submenu-initialized', 'true');
                        nuevoLi.innerHTML = `
                            <div class="contenidoNota agregarNuevaNota" id="agregarNuevaNota">
                                <p class="contenidoNotaP">Escribir una nueva nota</p>
                            </div>
                            <div class="botonesNotasGenerales">
                                <button class="borrarLasNotas">${window.borradorIcon}</button>
                            </div>
                        `;
                        ul.insertBefore(nuevoLi, ul.firstChild);
                    });

                    texto.contentEditable = 'false';
                }
            } catch (error) {
                console.error('Error al guardar nota:', error);
            }
        } else {
            // Restaurar el estado inicial si está vacío
            texto.removeEventListener('keydown', handleKeydown);
            texto.removeEventListener('blur', handleBlur);
            texto.contentEditable = 'false';
            nota.id = 'agregarNuevaNota';
            texto.textContent = 'Escribir una nueva nota';
            nota.parentElement.classList.remove('editandoNota');
        }
    }
}

//cada vez que esto se llama la alerta aparece varias veces, tiene que reiniciarse por los cambios dinamicos pero el cada vez que lo hace la elerta parece muchas veces 
async function borrarLasNotas() {
    const ul = document.querySelector('.clase-notas');
    let limpiar = true;

    if (!ul.dataset.eventRegistered) {
        ul.dataset.eventRegistered = 'true'; // Marca el evento como registrado

        ul.addEventListener('click', async function handleClick(event) {
            const boton = event.target.closest('.borrarLasNotas');

            if (boton) {
                const confirmado = await confirm('¿Estás seguro de que quieres borrar todas las notas?');

                if (confirmado) {
                    const data = { limpiar };

                    try {
                        await enviarAjax('borrarLasNotas', data);
                        console.log('Notas borradas exitosamente.');
                        await window.reiniciarContenido(limpiar, '', 'notas');
                    } catch (error) {
                        console.error('Error al borrar notas:', error);
                    }
                }
            }
        });
    }
}


function editarNota() {
    const notas = document.querySelectorAll('.notaPublicada');

    notas.forEach(nota => {
        let valorAnt = '';
        let id = '';

        const presionarEnter = ev => {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                guardarEdicionNota(nota.querySelector('.contenidoNotaP'), id, valorAnt);
            }
        };

        const salirEdicion = () => {
            guardarEdicionNota(nota.querySelector('.contenidoNotaP'), id, valorAnt);
        };

        const pegarTexto = ev => {
            ev.preventDefault();
            const texto = ev.clipboardData.getData('text/plain').trim();
            document.execCommand('insertText', false, texto);
        };

        nota.addEventListener('click', ev => {
            const parrafo = nota.querySelector('.contenidoNotaP');

            if (!parrafo.isContentEditable) {
                ev.preventDefault();
                id = nota.getAttribute('id-post');
                valorAnt = parrafo.textContent.trim();
                parrafo.contentEditable = true;
                parrafo.spellcheck = false;

                const off = calcularPosicionCursor(ev, parrafo);
                setCursorPos(parrafo, off);
            }
        });

        nota.addEventListener('keydown', presionarEnter);
        nota.addEventListener('blur', salirEdicion);
        nota.querySelector('.contenidoNotaP').addEventListener('paste', pegarTexto);
    });
}

function guardarEdicionNota(n, id, valorAnt) {
    const valorNuevo = n.textContent.trim();

    if (valorAnt !== valorNuevo) {
        n.contentEditable = false;
        n.style.outline = 'none';

        const data = {id, contenido: valorNuevo};
        enviarAjax('modificarNota', data)
            .then(rta => {
                if (!rta.success) {
                    n.textContent = valorAnt;
                    let m = 'Error al modificar.';
                    if (rta.data) m += ' Detalles: ' + rta.data;
                    alert(m);
                } else {
                    alert('Nota modificada con éxito.');
                    valorAnt = valorNuevo;
                }
            })
            .catch(err => {
                n.textContent = valorAnt;
                alert('Error al modificar.');
            });
    } else {
        n.contentEditable = false;
        n.style.outline = 'none';
    }
}

