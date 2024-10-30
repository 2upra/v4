let selectedPostId = null;
let selectedCollectionId = null;

function colec() {
    // Variables para almacenar el fondo oscuro y el modal
    let darkBackground = null;
    const modal = document.querySelector('.modalColec');

    if (!modal) {
        console.error('No se encontró el elemento con la clase .modalColec');
        return;
    }

    // Función para abrir el modal
    function openModal() {
        modal.style.display = 'block';
        // Crear el fondo oscuro
        darkBackground = window.createModalDarkBackgroundColec(modal);
        // Bloquear el scroll de la página
        document.body.classList.add('no-scroll');
    }

    // Función para cerrar el modal
    function closeModal() {
        modal.style.display = 'none';
        // Remover el fondo oscuro
        window.removeModalDarkBackgroundColec(darkBackground);
        darkBackground = null;
        // Permitir el scroll de la página
        document.body.classList.remove('no-scroll');
    }

    // Delegar el evento click en los botones de colección
    document.body.addEventListener('click', function (e) {
        if (e.target.closest('.botonColeccionBtn')) {
            e.preventDefault();
            const btn = e.target.closest('.botonColeccionBtn');
            selectedPostId = btn.getAttribute('data-post_id');
            // Opcional: Puedes usar el nonce para verificar acciones en el frontend si es necesario

            // Abrir el modal
            openModal();
        }
    });

    // Manejar la selección de una colección
    const listaColecciones = document.querySelector('.listaColeccion');
    if (listaColecciones) {
        listaColecciones.addEventListener('click', function (e) {
            const coleccion = e.target.closest('.coleccion');
            if (coleccion) {
                // Eliminar la clase 'seleccion' de todas las colecciones
                document.querySelectorAll('.coleccion').forEach(function (item) {
                    item.classList.remove('seleccion');
                });

                // Añadir la clase 'seleccion' a la colección clicada
                coleccion.classList.add('seleccion');

                // Almacenar el ID de la colección seleccionada
                selectedCollectionId = coleccion.getAttribute('data-id') || coleccion.id;
            }
        });
    } else {
        console.error('No se encontró el elemento con la clase .listaColeccion');
    }

    // Manejar el botón "Listo"
    const btnListo = document.getElementById('btnListo');
    if (btnListo) {
        btnListo.addEventListener('click', function () {
            if (selectedPostId && selectedCollectionId) {
                // Aquí puedes realizar una acción, como enviar una solicitud AJAX al servidor
                console.log('Post ID:', selectedPostId);
                console.log('Collection ID:', selectedCollectionId);

                // Cerrar el modal
                closeModal();

                // Resetear las selecciones
                selectedPostId = null;
                selectedCollectionId = null;

                // Opcional: Eliminar la clase 'seleccion'
                document.querySelectorAll('.coleccion').forEach(function (item) {
                    item.classList.remove('seleccion');
                });
            } else {
                alert('Por favor, selecciona una colección.');
            }
        });
    } else {
        console.error('No se encontró el botón con el ID #btnListo');
    }

    // Manejar el cierre del modal al hacer clic fuera de él
    // Añadiremos el listener directamente al darkBackground cuando se crea
    function addBackgroundClickListener() {
        if (darkBackground) {
            darkBackground.addEventListener('click', function (e) {
                // Asegurarse de que el clic sea directamente en el fondo, no en alguna burbuja
                if (e.target === darkBackground) {
                    closeModal();
                }
            }, { once: true }); // Se asegura de que se añada solo una vez por apertura
        }
    }

    // Modificar la función closeModal para remover cualquier listener adicional si es necesario
    // Pero en este caso, usamos { once: true }, así que no es necesario

    // Modificar openModal para agregar el listener
    const originalOpenModal = openModal;
    openModal = function() {
        originalOpenModal();
        addBackgroundClickListener();
    };

    // Manejar la búsqueda de colecciones
    const buscarInput = document.getElementById('buscarColeccion');
    if (buscarInput) {
        buscarInput.addEventListener('input', function () {
            const query = buscarInput.value.toLowerCase();
            const colecciones = document.querySelectorAll('.listaColeccion .coleccion');

            colecciones.forEach(function (coleccion) {
                const tituloSpan = coleccion.querySelector('span');
                const titulo = tituloSpan ? tituloSpan.innerText.toLowerCase() : '';
                if (titulo.includes(query)) {
                    coleccion.style.display = 'flex'; // Asumiendo que usas flex para el layout
                } else {
                    coleccion.style.display = 'none';
                }
            });
        });
    } else {
        console.error('No se encontró el input con el ID #buscarColeccion');
    }
}

// Función para crear y mostrar el fondo oscuro al mismo nivel que el modal
window.createModalDarkBackgroundColec = function(modal) {
    const darkBackground = document.createElement('div');
    darkBackground.classList.add('modal-background');
    document.body.appendChild(darkBackground);

    // Forzar reflow para que la transición CSS funcione
    void darkBackground.offsetWidth;

    // Añadir la clase 'show' para la transición
    darkBackground.classList.add('show');

    return darkBackground;
};

// Función para remover el fondo oscuro del modal
window.removeModalDarkBackgroundColec = function(darkBackground) {
    if (darkBackground) {
        darkBackground.classList.remove('show');
        // Espera a que termine la transición antes de remover
        darkBackground.addEventListener('transitionend', function() {
            if (darkBackground.parentNode) {
                darkBackground.parentNode.removeChild(darkBackground);
            }
        });
    }
};
