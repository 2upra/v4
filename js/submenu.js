
/*


<div class="A1806241" id="opcionespost-<? echo $postId; ?>">
    <div class="A1806242">
        <? if (current_user_can('administrator')) : ?>
            <button class="eliminarPost" data-post-id="<? echo $postId; ?>">Eliminar</button>
            <button class="cambiarTitulo" data-post-id="<? echo $postId; ?>">Cambiar titulo</button>
            <button class="cambiarImagen" data-post-id="<? echo $postId; ?>">Cambiar imagen</button>
            <? if (!$post_verificado) : ?>
                <button class="verificarPost" data-post-id="<? echo $postId; ?>">Verificar</button>
            <? endif; ?>
            <button class="editarWordPress" data-post-id="<? echo $postId; ?>">Editar en WordPress</button>
            <button class="banearUsuario" data-post-id="<? echo $postId; ?>">Banear</button>
        <? elseif ($usuarioActual == $autorId) : ?>
            <button class="eliminarPost" data-post-id="<? echo $postId; ?>">Eliminar</button>
            <button class="cambiarImagen" data-post-id="<? echo $postId; ?>">Cambiar Imagen</button>
        <? else : ?>
            <button class="reporte" data-post-id="<? echo $postId; ?>" tipoContenido="social_post">Reportar</button>
            <button class="bloquear" data-post-id="<? echo $postId; ?>">Bloquear</button>
        <? endif; ?>
    </div>
</div>


ESTE BOTON; ESTE UNICO BOTON NO FUNCIONA DENTRO DE LOS SUBMENU SIN MOTIVO ALGUNO, EL RESTO DE BOTONES SI FUNCIONA PERO NI SE DE TECTA EL CLICK EN EN 
function inicializarCambiarImagen() {
    console.log('inicializarCambiarImagen: La función se ha inicializado correctamente.');

    // Registrar el evento click a nivel del documento
    document.addEventListener('click', async (e) => {
        console.log('inicializarCambiarImagen: Evento de clic detectado.', e);

        // Verificar si el elemento clicado tiene la clase "cambiarImagen"
        if (e.target && e.target.classList.contains('cambiarImagen')) {
            console.log('inicializarCambiarImagen: El elemento clicado tiene la clase "cambiarImagen".');

            e.preventDefault();
            e.stopPropagation(); // Detener la propagación para evitar que el submenú se oculte

            const postId = e.target.getAttribute('data-post-id');
            console.log('inicializarCambiarImagen: postId obtenido del atributo data-post-id:', postId);

            if (!postId) {
                console.error('inicializarCambiarImagen: El botón no contiene un atributo data-post-id.');
                return;
            }

            // Crear un input de tipo archivo para seleccionar la imagen
            const inputFile = document.createElement('input');
            inputFile.type = 'file';
            inputFile.accept = 'image/*';
            console.log('inicializarCambiarImagen: Input file creado con éxito.');

            // Registrar el evento change en el input para detectar la selección del archivo
            inputFile.addEventListener('change', async (fileEvent) => {
                console.log('inicializarCambiarImagen: Evento de cambio en el input file detectado.', fileEvent);

                const file = fileEvent.target.files[0];
                console.log('inicializarCambiarImagen: Archivo seleccionado:', file);

                if (!file) {
                    console.warn('inicializarCambiarImagen: No se seleccionó ningún archivo.');
                    return;
                }

                // Crear un objeto FormData para enviar la imagen
                const formData = new FormData();
                formData.append('action', 'cambiar_imagen_post'); // Acción para el backend de WordPress
                formData.append('post_id', postId);
                formData.append('imagen', file);
                console.log('inicializarCambiarImagen: FormData creado con los siguientes datos:', {
                    action: 'cambiar_imagen_post',
                    post_id: postId,
                    imagen: file,
                });

                try {
                    // Enviar la imagen al servidor
                    console.log('inicializarCambiarImagen: Enviando datos al servidor mediante fetch.');

                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData,
                    });

                    console.log('inicializarCambiarImagen: Respuesta recibida del servidor.', response);

                    const result = await response.json();
                    console.log('inicializarCambiarImagen: Resultado parseado de la respuesta JSON:', result);

                    if (result.success) {
                        console.log('inicializarCambiarImagen: Imagen cambiada con éxito en el servidor.');

                        // Actualizar la imagen en el frontend
                        const postImage = document.querySelector(
                            `.post-image-container a[data-post-id="${postId}"] img`
                        );
                        console.log('inicializarCambiarImagen: Elemento de la imagen encontrado en el DOM:', postImage);

                        if (postImage) {
                            postImage.src = result.new_image_url;
                            console.log('inicializarCambiarImagen: URL de la imagen actualizada en el frontend:', result.new_image_url);
                        } else {
                            console.warn('inicializarCambiarImagen: No se encontró el elemento de la imagen en el DOM.');
                        }
                    } else {
                        console.error('inicializarCambiarImagen: Error al cambiar la imagen en el servidor:', result.message);
                        alert('Hubo un problema al cambiar la imagen.');
                    }
                } catch (error) {
                    console.error('inicializarCambiarImagen: Error en la solicitud AJAX:', error);
                    alert('Hubo un error al enviar la imagen.');
                }
            });

            // Simular un clic en el input de archivo para abrir el selector
            console.log('inicializarCambiarImagen: Abriendo el selector de archivos.');
            inputFile.click();
        } else {
            console.log('inicializarCambiarImagen: El elemento clicado no tiene la clase "cambiarImagen".');
        }
    });

    console.log('inicializarCambiarImagen: Evento "click" registrado en el documento.');
}

*/

function createSubmenu(triggerSelector, submenuIdPrefix, position = 'auto') {
    const triggers = document.querySelectorAll(triggerSelector);

    function toggleSubmenu(event) {
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) return;

        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"}`;
        const submenu = document.getElementById(submenuId);

        if (!submenu) return;

        submenu._position = position; // Guardamos la posición deseada

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);

        if (submenu.style.display === "block") {
            hideSubmenu(submenu);
        } else {
            showSubmenu(event, trigger, submenu, submenu._position);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, trigger, submenu, position) {
        const { innerWidth: vw, innerHeight: vh } = window;

        if (submenu.parentNode !== document.body) {
            document.body.appendChild(submenu);
        }

        submenu.style.position = "fixed";
        submenu.style.zIndex = 1001;

        submenu.style.display = "block";
        submenu.style.visibility = "hidden";

        let submenuWidth = submenu.offsetWidth;
        let submenuHeight = submenu.offsetHeight;

        const rect = trigger.getBoundingClientRect();

        if (vw <= 640) {
            submenu.style.top = `${(vh - submenuHeight) / 2}px`;
            submenu.style.left = `${(vw - submenuWidth) / 2}px`;
        } else {
            let { top, left } = calculatePosition(rect, submenuWidth, submenuHeight, position);

            if (top + submenuHeight > vh) top = vh - submenuHeight;
            if (left + submenuWidth > vw) left = vw - submenuWidth;
            if (top < 0) top = 0;
            if (left < 0) left = 0;

            submenu.style.top = `${top}px`;
            submenu.style.left = `${left}px`;
        }

        submenu.style.visibility = "visible";

        submenu._darkBackground = createSubmenuDarkBackground(submenu);

        document.body.classList.add('no-scroll');

        submenu.addEventListener('click', (e) => {
            e.stopPropagation(); // Evitar que el clic dentro del submenú cierre el mismo
        });
    }

    function hideSubmenu(submenu) {
        if (submenu) {
            submenu.style.display = "none";
        }

        removeSubmenuDarkBackground(submenu._darkBackground);
        submenu._darkBackground = null;

        const activeSubmenus = Array.from(document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`)).filter(menu => menu.style.display === "block");

        if (activeSubmenus.length === 0) {
            document.body.classList.remove('no-scroll');
        }
    }

    triggers.forEach(trigger => {
        if (trigger.dataset.submenuInitialized) return;

        trigger.addEventListener("click", toggleSubmenu);
        trigger.dataset.submenuInitialized = "true";
    });

    document.addEventListener("click", (event) => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            if (
                submenu.contains(event.target) &&
                event.target.classList.contains('cambiarImagen')
            ) {
                return; // No cerrar el submenú si se hace clic en el botón cambiarImagen
            }

            if (!submenu.contains(event.target) && !event.target.matches(triggerSelector)) {
                hideSubmenu(submenu);
            }
        });
    });

    window.addEventListener('resize', () => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        });
    });
}

function calculatePosition(rect, submenuWidth, submenuHeight, position) {
    const { innerWidth: vw, innerHeight: vh } = window;
    let top, left;

    switch (position) {
        case 'arriba':
            top = rect.top - submenuHeight;
            left = rect.left + (rect.width / 2) - (submenuWidth / 2);
            break;
        case 'abajo':
            top = rect.bottom;
            left = rect.left + (rect.width / 2) - (submenuWidth / 2);
            break;
        case 'izquierda':
            top = rect.top + (rect.height / 2) - (submenuHeight / 2);
            left = rect.left - submenuWidth;
            break;
        case 'derecha':
            top = rect.top + (rect.height / 2) - (submenuHeight / 2);
            left = rect.right;
            break;
        case 'centro':
            top = (vh - submenuHeight) / 2;
            left = (vw - submenuWidth) / 2;
            break;
        default:
            // 'auto' o cualquier otro valor: intentar posicionar debajo del trigger
            top = rect.bottom;
            left = rect.left;
            break;
    }

    return { top, left };
}

function initializeStaticMenus() {
    // Ejemplos de uso con la nueva parametrización de posición
    createSubmenu(".subiricono", "submenusubir", 'derecha');
    createSubmenu(".chatIcono", "bloqueConversaciones", 'abajo');
    createSubmenu(".fotoperfilsub", "fotoperfilsub", 'abajo');
}

// Esto se reinicia cada vez que cargan nuevos posts
function submenu() {
    // Botón clase - submenu id - posición
    createSubmenu(".filtrosboton", "filtrosMenu", 'abajo');
    createSubmenu(".mipsubmenu", "submenuperfil", 'abajo');
    createSubmenu(".HR695R7", "opcionesrola", 'abajo');
    createSubmenu(".HR695R8", "opcionespost", 'abajo');
    createSubmenu(".submenucolab", "opcionescolab", 'abajo');
}

document.addEventListener('DOMContentLoaded', () => {
    initializeStaticMenus();
});