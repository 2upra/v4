let selectedPostId = null;
let selectedCollectionId = null;

function colec() {
    const modal = document.querySelector('.modalColec');
    if (!modal) {
        console.error('No se encontró el elemento con la clase .modalColec');
        return;
    }

    let darkBackground = null;

    const openModal = () => {
        modal.style.display = 'block';
        darkBackground = createDarkBackground();
        document.body.classList.add('no-scroll');
    };

    const closeModal = () => {
        modal.style.display = 'none';
        removeDarkBackground();
        document.body.classList.remove('no-scroll');
        resetSelections();
    };

    const createDarkBackground = () => {
        const bg = document.createElement('div');
        bg.classList.add('submenu-background');
        Object.assign(bg.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100vw',
            height: '100vh',
            backgroundColor: 'rgba(0, 0, 0, 0.5)',
            zIndex: '998',
            pointerEvents: 'auto'
        });
        document.body.appendChild(bg);
        bg.addEventListener('click', closeModal, { once: true });
        return bg;
    };

    const removeDarkBackground = () => {
        if (darkBackground) {
            darkBackground.remove();
            darkBackground = null;
        }
    };

    const resetSelections = () => {
        selectedPostId = null;
        selectedCollectionId = null;
        document.querySelectorAll('.coleccion').forEach(item => item.classList.remove('seleccion'));
    };

    const handleCollectionClick = (coleccion) => {
        document.querySelectorAll('.coleccion').forEach(item => item.classList.remove('seleccion'));
        coleccion.classList.add('seleccion');
        selectedCollectionId = coleccion.getAttribute('data-id') || coleccion.id;
    };

    const handleListoClick = () => {
        if (selectedPostId && selectedCollectionId) {
            console.log('Post ID:', selectedPostId);
            console.log('Collection ID:', selectedCollectionId);
            closeModal();
        } else {
            alert('Por favor, selecciona una colección.');
        }
    };

    const filterCollections = (query) => {
        document.querySelectorAll('.listaColeccion .coleccion').forEach(coleccion => {
            const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
            coleccion.style.display = titulo.includes(query) ? 'flex' : 'none';
        });
    };

    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.botonColeccionBtn');
        if (btn) {
            e.preventDefault();
            selectedPostId = btn.getAttribute('data-post_id');
            openModal();
        }
    });

    const listaColecciones = document.querySelector('.listaColeccion');
    if (listaColecciones) {
        listaColecciones.addEventListener('click', (e) => {
            const coleccion = e.target.closest('.coleccion');
            if (coleccion) handleCollectionClick(coleccion);
        });
    } else {
        console.error('No se encontró el elemento con la clase .listaColeccion');
    }

    const btnListo = document.getElementById('btnListo');
    if (btnListo) {
        btnListo.addEventListener('click', handleListoClick);
    } else {
        console.error('No se encontró el botón con el ID #btnListo');
    }

    const buscarInput = document.getElementById('buscarColeccion');
    if (buscarInput) {
        buscarInput.addEventListener('input', () => {
            const query = buscarInput.value.toLowerCase();
            filterCollections(query);
        });
    } else {
        console.error('No se encontró el input con el ID #buscarColeccion');
    }
}


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