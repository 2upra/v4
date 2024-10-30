let selectedPostId = null;
let selectedCollectionId = null;


//EL MODAL SE ABRE PERO NO APARECE EL BACKGROUDND OS
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


// Función para crear y mostrar el fondo oscuro al mismo nivel que el submenú
window.createModalDarkBackgroundColec = function(submenu) {
    const darkBackground = document.createElement('div');
    darkBackground.classList.add('submenu-background');
    darkBackground.style.position = 'fixed';
    darkBackground.style.top = 0;
    darkBackground.style.left = 0;
    darkBackground.style.width = '100vw';
    darkBackground.style.height = '100vh';
    darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    darkBackground.style.zIndex = 998; // Debe estar por debajo del submenú
    darkBackground.style.pointerEvents = 'auto';

    // Insertar el background justo antes del submenu, como hermano
    submenu.parentNode.insertBefore(darkBackground, submenu);

    return darkBackground;
};

// Función para remover el fondo oscuro del submenú
window.removeModalDarkBackgroundColec = function(darkBackground) {
    if (darkBackground && darkBackground.parentNode) {
        darkBackground.parentNode.removeChild(darkBackground);
    }
};


/*
//en el header
function modalColeccion()
{
    // Obtener el ID del usuario actual
    $current_user_id = get_current_user_id();

    // Consultar las colecciones del usuario
    $args = array(
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author'         => $current_user_id,
    );

    $user_collections = new WP_Query($args);
    ?>
    <div class="modalColec modal" style="display: none;">
        <div class="colecciones">
            <h3>Colecciones</h3>
            <input type="text" placeholder="Buscar colección" id="buscarColeccion">

            <ul class="listaColeccion borde">
                <li class="coleccion" id="favoritos">
                    <img src="<?php echo esc_url('https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg'); ?>" alt=""><span>Favoritos</span>
                </li>
                <li class="coleccion borde" id="despues">
                    <img src="<?php echo esc_url('https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg'); ?>" alt=""><span>Usar más tarde</span>
                </li>

                <?php if ($user_collections->have_posts()) : ?>
                    <?php while ($user_collections->have_posts()) : $user_collections->the_post(); ?>
                        <li class="coleccion borde" data-id="<?php the_ID(); ?>">
                            <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')); ?>" alt="">
                            <span><?php the_title(); ?></span>
                        </li>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <li class="coleccion borde"></li>
                <?php endif; ?>
            </ul>

            <div class="XJAAHB">
                <button class="botonsecundario">Nueva colección</button>
                <button class="botonprincipal" id="btnListo">Listo</button>
            </div>
        </div>
    </div>
    <?php
}

*/