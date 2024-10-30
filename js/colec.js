let selectedPostId = null;
let selectedCollectionId = null;


function colec() {
    // Función para abrir el modal
    function openModal() {
        document.querySelector('.modalColec').style.display = 'block';
    }

    // Función para cerrar el modal
    function closeModal() {
        document.querySelector('.modalColec').style.display = 'none';
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

    // Manejar el botón "Listo"
    const btnListo = document.getElementById('btnListo');
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

    // Manejar el cierre del modal al hacer clic fuera de él
    window.addEventListener('click', function (e) {
        const modal = document.querySelector('.modalColec');
        if (e.target == modal) {
            closeModal();
        }
    });

    // Manejar la búsqueda de colecciones
    const buscarInput = document.getElementById('buscarColeccion');
    buscarInput.addEventListener('input', function () {
        const query = buscarInput.value.toLowerCase();
        const colecciones = document.querySelectorAll('.listaColeccion .coleccion');

        colecciones.forEach(function (coleccion) {
            const titulo = coleccion.querySelector('span').innerText.toLowerCase();
            if (titulo.includes(query)) {
                coleccion.style.display = 'flex'; // Asumiendo que usas flex para el layout
            } else {
                coleccion.style.display = 'none';
            }
        });
    });
}
