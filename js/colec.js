let colecSampleId = null;
let colecSelecionado = null;
let colecIniciado = false;
let imgColec = null;
let imgColecId = null;

function colec() {
    if (!colecIniciado) {
        iniciarColec();
        colecIniciado = true;
    }
}

function iniciarColec() {
    document.body.addEventListener('click', e => {
        const btn = e.target.closest('.botonColeccionBtn');
        if (btn) {
            e.preventDefault();
            colecSampleId = btn.getAttribute('data-post_id');
            // console.log('Post ID seleccionado:', colecSampleId);
            abrirColec();
        }
    });

    document.addEventListener('click', e => {
        const coleccion = e.target.closest('.coleccion');
        if (coleccion && coleccion.closest('.listaColeccion')) {
            manejarClickColec(coleccion);
        }
    });

    a('#btnListo')?.addEventListener('click', manejarClickListoColec);
    a('#btnEmpezarCreaColec')?.addEventListener('click', abrirModalCrearColec);
    a('#btnCrearColec')?.addEventListener('click', crearNuevaColec);
    a('#btnVolverColec')?.addEventListener('click', volverColec);

    const buscarInput = document.getElementById('buscarColeccion');
    if (buscarInput) {
        buscarInput.addEventListener('input', () => {
            const query = buscarInput.value.toLowerCase();
            busquedaColec(query);
        });
    } else {
        return;
    }
    subidaImagenColec();
    document.addEventListener('modalOpened', () => {
        resetColec();
    });
}

function manejarClickColec(coleccion) {
    const button = a('#btnListo');
    a.quitar('.coleccion', 'seleccion');
    a.gregar(coleccion, 'seleccion');
    colecSelecionado = coleccion.getAttribute('data-post_id') || coleccion.id;
    button.innerText = 'Guardar';
}

async function abrirColec() {
    console.log('Función abrirColec iniciada');
    const modal = a('.modalColec');
    mostrar(modal);
    crearBackgroundColec();
    a.gregar('body', 'no-scroll');
    console.log('Modal mostrado y fondo creado');
    await verificarSampleEnColecciones();
    console.log('verificarSampleEnColecciones completado');
}

async function manejarClickListoColec() {
    console.log('Función manejarClickListoColec iniciada');
    if (colecSampleId && colecSelecionado) {
        console.log('colecSampleId y colecSelecionado existen:', colecSampleId, colecSelecionado);

        const button = a('#btnListo');
        const originalText = button.innerText;
        button.innerText = 'Guardando...';
        button.disabled = true;

        try {
            console.log('Enviando petición AJAX para guardar sample en colección');
            const response = await enviarAjax('guardarSampleEnColec', {
                colecSampleId,
                colecSelecionado
            });
            console.log('Respuesta recibida:', response);

            if (response?.success) {
                alert('Sample guardado en la colección con éxito');
                cerrarColec();
            } else {
                alert(`Error al guardar en la colección: ${response?.message || 'Desconocido'}`);
            }
        } catch (error) {
            console.error('Error al guardar el sample:', error);
            alert('Ocurrió un error al guardar en la colección. Por favor, inténtelo de nuevo.');
        } finally {
            button.innerText = originalText;
            button.disabled = false;
        }
    } else {
        console.log('colecSampleId o colecSelecionado faltan:', colecSampleId, colecSelecionado);
        cerrarColec();
    }
}



async function verificarSampleEnColecciones() {
    console.log('Función verificarSampleEnColecciones iniciada');
    try {
        console.log('Enviando petición AJAX para verificar sample en colecciones con ID:', colecSampleId);
        const response = await enviarAjax('verificar_sample_en_colecciones', {
            sample_id: colecSampleId
        });
        console.log('Respuesta recibida de verificarSampleEnColecciones:', response);

        if (response.success) {
            const colecciones = document.querySelectorAll('.coleccion');
            colecciones.forEach(coleccion => {
                const coleccionId = coleccion.getAttribute('data-post_id');

                if (coleccionId && response.data.colecciones.includes(parseInt(coleccionId))) {
                    // Verificar si ya existe la etiqueta para no duplicarla
                    if (!coleccion.querySelector('.ya-existe')) {
                        const existeSpan = document.createElement('span');
                        existeSpan.className = 'ya-existe';
                        existeSpan.textContent = 'Guardado aquí';
                        coleccion.appendChild(existeSpan);
                        console.log('Etiqueta "Ya existe" añadida a la colección con ID:', coleccionId);
                    }
                } else if (!coleccionId) {
                    console.warn('Elemento sin data-post_id encontrado y omitido:', coleccion);
                }
            });
        } else {
            console.error('Error al verificar las colecciones:', response.message);
        }
    } catch (error) {
        console.error('Error al verificar las colecciones:', error);
    }
}

/*
add_action('wp_ajax_verificar_sample_en_colecciones', 'verificarSampleEnColec');

function verificarSampleEnColec()
{
    $sample_id = isset($_POST['sample_id']) ? intval($_POST['sample_id']) : 0;
    $colecciones_con_sample = array();

    if ($sample_id) {
        // Obtener todas las colecciones del usuario actual
        $current_user_id = get_current_user_id();
        $args = array(
            'post_type'      => 'colecciones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'author'         => $current_user_id,
        );

        $colecciones = get_posts($args);

        // Verificar cada colección
        foreach ($colecciones as $coleccion) {
            $samples = get_post_meta($coleccion->ID, 'samples', true);
            if (is_array($samples) && in_array($sample_id, $samples)) {
                $colecciones_con_sample[] = $coleccion->ID;
            }
        }
    }

    wp_send_json_success(array(
        'colecciones' => $colecciones_con_sample
    ));
}

function modalColeccion()
{
    $current_user_id = get_current_user_id();

    // ID de favoritos y usar más tarde
    $favoritos_id = get_user_meta($current_user_id, 'favoritos_coleccion_id', true);
    $despues_id = get_user_meta($current_user_id, 'despues_coleccion_id', true);

    $args = array(
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author'         => $current_user_id,
    );

    $user_collections = new WP_Query($args);
    $default_image = 'https://2upra.com/wp-content/uploads/2024/10/699bc48ebc970652670ff977acc0fd92.jpg'; // Imagen predeterminada
?>
    <div class="modalColec modal" style="display: none;">
        <div class="colecciones">
            <h3>Colecciones</h3>
            <input type="text" placeholder="Buscar colección" id="buscarColeccion">
            <ul class="listaColeccion borde">
                <? if (!$favoritos_id) : ?>
                    <li class="coleccion" id="favoritos" data-post_id="favoritos">
                        <img src="<? echo esc_url('https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg'); ?>" alt="">
                        <span>Favoritos</span>
                    </li>
                <? endif; ?>

                <? if (!$despues_id) : ?>
                    <li class="coleccion borde" id="despues" data-post_id="despues">
                        <img src="<? echo esc_url('https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg'); ?>" alt="">
                        <span>Usar más tarde</span>
                    </li>
                <? endif; ?>

                <? if ($user_collections->have_posts()) : ?>
                    <? while ($user_collections->have_posts()) : $user_collections->the_post(); ?>
                        <li class="coleccion borde" data-post_id="<? the_ID(); ?>">
                            <?php
                            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                            ?>
                            <img src="<? echo esc_url($thumbnail_url ? $thumbnail_url : $default_image); ?>" alt="">
                            <span><? the_title(); ?></span>
                        </li>
                    <? endwhile; ?>
                    <? wp_reset_postdata(); ?>
                <? endif; ?>
            </ul>

            <div class="XJAAHB">
                <button class="botonsecundario" id="btnEmpezarCreaColec">Nueva colección</button>
                <button class="botonprincipal" id="btnListo">Listo</button>
            </div>
        </div>
    </div>
<?
}


*/

function abrirModalCrearColec() {
    ocultar(a('.modalColec'));
    mostrar(a('.modalCrearColec'));
}

function volverColec() {
    ocultar(a('.modalCrearColec'));
    mostrar(a('.modalColec'));
}

function verificarColec() {
    const titulo = a('#tituloColec').value;
    function verificarCamposColec() {
        if (!colecSampleId) {
            alert('Parece que hay un error, intenta seleccionar algo para guardar nuevamente.');
            return false;
        }
        if (titulo.length < 3) {
            alert('Por favor, ingresa un nombre para tu colección.');
            return false;
        }
        return true;
    }
    return verificarCamposColec;
}



async function crearNuevaColec() {
    const esValido = verificarColec();
    if (!esValido) return;

    const titulo = a('#tituloColec').value;
    const descripcion = a('#descripColec').value || '';
    const privadoCheck = a('#privadoColec');
    const privado = privadoCheck.checked ? privadoCheck.value : 0;

    const data = {
        colecSampleId,
        imgColec,
        titulo,
        imgColecId,
        descripcion,
        privado
    };
    console.log('Datos enviados:', data); // Log de la data que se envía

    const button = a('#btnCrearColec');
    const originalText = button.innerText;
    button.innerText = 'Guardando...';
    button.disabled = true;

    try {
        const response = await enviarAjax('crearColeccion', data);
        if (response?.success) {
            alert('Colección creada con éxito');
            await actualizarListaColecciones();
            cerrarColec();
        } else {
            alert(`Error al crear la colección: ${response?.message || 'Desconocido'}`);
        }
    } catch (error) {
        alert('Ocurrió un error durante la creación de la colección. Por favor, inténtelo de nuevo.');
    } finally {
        button.innerText = originalText;
        button.disabled = false;
    }
}


async function actualizarListaColecciones() {
    try {
        const response = await enviarAjax('obtener_colecciones');
        if (response) {
            const listaColeccion = document.querySelector('.listaColeccion');
            const elementosFijos = listaColeccion.querySelectorAll('#favoritos, #despues');
            listaColeccion.innerHTML = '';
            elementosFijos.forEach(elemento => {
                listaColeccion.appendChild(elemento);
            });

            listaColeccion.insertAdjacentHTML('beforeend', response);
        } else {
        }
    } catch (error) {}
}

function subidaImagenColec() {
    const previewImagenColec = a('#previewImagenColec');
    const formRs = a('#formRs');

    const inicialSubida = event => {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (!file) return;
        if (file.size > 3 * 1024 * 1024) return alert('El archivo no puede superar los 3 MB.');
        if (!file.type.startsWith('image/')) return alert('Por favor, seleccione una imagen.');

        subidaImagen(file);
    };

    const subidaImagen = async file => {
        try {
            const {fileUrl, fileId} = await subidaRsBackend(file, 'barraProgresoImagen');
            imgColec = fileUrl;
            imgColecId = fileId;
            updatePreviewImagen(file);
        } catch {
            alert('Hubo un problema al cargar la imagen. Inténtalo de nuevo.');
        }
    };

    const updatePreviewImagen = file => {
        const reader = new FileReader();
        reader.onload = e => {
            previewImagenColec.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
            previewImagenColec.style.display = 'block';
        };
        reader.readAsDataURL(file);
    };

    previewImagenColec.addEventListener('click', () => {
        const inputFile = document.createElement('input');
        inputFile.type = 'file';
        inputFile.accept = 'image/*';
        inputFile.onchange = inicialSubida;
        inputFile.click();
    });

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        formRs.addEventListener(eventName, e => {
            e.preventDefault();
            formRs.style.backgroundColor = eventName === 'dragover' ? '#e9e9e9' : '';
            if (eventName === 'drop') inicialSubida(e);
        });
    });
}

function busquedaColec(query) {
    document.querySelectorAll('.listaColeccion .coleccion').forEach(coleccion => {
        const titulo = coleccion.querySelector('span')?.innerText.toLowerCase() || '';
        coleccion.style.display = titulo.includes(query) ? 'flex' : 'none';
    });
}

function cerrarColec() {
    ocultar(a('.modalColec'));
    ocultar(a('.modalCrearColec'));
    quitBackground();
    a.quitar('body', 'no-scroll');
    resetColec();
}

function resetColec() {
    colecSampleId = null;
    colecSelecionado = null;
    a.quitar('.coleccion', 'seleccion');
    const existeSpans = document.querySelectorAll('.ya-existe');
    existeSpans.forEach(span => span.remove());
    const button = a('#btnListo');
    button.innerText = 'Listo';
}

function quitBackground() {
    const darkBackground = a('.submenu-background');
    if (darkBackground) {
        darkBackground.remove();
    }
}

function crearBackgroundColec() {
    if (a('.submenu-background')) return;

    const darkBackground = document.createElement('div');
    darkBackground.classList.add('submenu-background');
    Object.assign(darkBackground.style, {
        position: 'fixed',
        top: '0',
        left: '0',
        width: '100vw',
        height: '100vh',
        backgroundColor: 'rgba(0, 0, 0, 0.5)',
        zIndex: '998',
        pointerEvents: 'auto'
    });
    document.body.appendChild(darkBackground);
    darkBackground.addEventListener('click', cerrarColec, {once: true});
    return darkBackground;
}
