function createSubmenu(triggerSelector, submenuIdPrefix, position = 'auto') {
    const triggers = document.querySelectorAll(triggerSelector);
    let openSubmenu = null; // Variable para mantener un registro del submenú abierto

    function toggleSubmenu(event) {
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) return;

        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || 'default'}`;
        const submenu = document.getElementById(submenuId);

        if (!submenu) return;

        // Cerrar el submenú actualmente abierto si se abre uno nuevo
        if (openSubmenu && openSubmenu !== submenu) {
            hideSubmenu(openSubmenu);
        }

        submenu._position = position;

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);

        if (submenu.style.display === 'block') {
            hideSubmenu(submenu);
        } else {
            showSubmenu(event, trigger, submenu, submenu._position);
        }

        event.stopPropagation(); // Necesario para evitar conflictos con otros manejadores de eventos
    }

    function showSubmenu(event, trigger, submenu, position) {
        const {innerWidth: vw, innerHeight: vh} = window;

        if (submenu.parentNode !== document.body) {
            document.body.appendChild(submenu);
        }

        submenu.style.position = 'fixed';
        submenu.style.zIndex = 1003;

        submenu.style.display = 'block';
        submenu.style.visibility = 'hidden';

        let submenuWidth = submenu.offsetWidth;
        let submenuHeight = submenu.offsetHeight;

        const rect = trigger.getBoundingClientRect();

        if (vw <= 640) {
            submenu.style.top = `${(vh - submenuHeight) / 2}px`;
            submenu.style.left = `${(vw - submenuWidth) / 2}px`;
        } else {
            let {top, left} = calculatePosition(rect, submenuWidth, submenuHeight, position);

            if (top + submenuHeight > vh) top = vh - submenuHeight;
            if (left + submenuWidth > vw) left = vw - submenuWidth;
            if (top < 0) top = 0;
            if (left < 0) left = 0;

            submenu.style.top = `${top}px`;
            submenu.style.left = `${left}px`;
        }

        submenu.style.visibility = 'visible';

        createSubmenuDarkBackground();

        document.body.classList.add('no-scroll');

        openSubmenu = submenu; // Registrar el submenú abierto
    }

    function hideSubmenu(submenu) {
        if (submenu) {
            submenu.style.display = 'none';
            openSubmenu = null; // Restablecer el submenú abierto
        }

        removeSubmenuDarkBackground();

        const activeSubmenus = Array.from(document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`)).filter(menu => menu.style.display === 'block');

        if (activeSubmenus.length === 0) {
            document.body.classList.remove('no-scroll');
        }
    }
    window.hideAllSubmenus = function () {
        console.log('Ejecutando hideAllSubmenus'); // Indica el inicio de la función

        // Asumiendo que 'triggers' está definido en otro lugar y es un array o NodeList
        if (!triggers || triggers.length === 0) {
            console.log("No se encontraron 'triggers'. Verifica que la variable 'triggers' esté definida correctamente y contenga elementos.");
            return; // Salir de la función si no hay triggers
        }

        console.log(`Se encontraron ${triggers.length} triggers:`, triggers); // Muestra la cantidad de triggers y los propios triggers

        triggers.forEach((trigger, index) => {
            console.log(`Procesando trigger ${index + 1}:`, trigger); // Muestra el trigger actual

            // Mejorando la lógica para obtener postId
            const postId = trigger.dataset.postId || trigger.id;
            console.log(`postId obtenido: ${postId}`);

            if (!postId) {
                console.warn(`El trigger ${index + 1} no tiene postId ni id. Se usará 'default'.`, trigger);
            }

            const submenuId = `${submenuIdPrefix}-${postId || 'default'}`;
            console.log(`submenuId generado: ${submenuId}`);

            const submenu = document.getElementById(submenuId);
            console.log(`Elemento submenu encontrado con ID ${submenuId}:`, submenu);

            if (submenu) {
                hideSubmenu(submenu);
                console.log(`Se intentó ocultar el submenu con ID ${submenuId}`);
            } else {
                console.error(`No se encontró un elemento con el ID ${submenuId}. Verifica el ID generado y la estructura del DOM.`);
            }
        });

        console.log('hideAllSubmenus finalizado'); // Indica el final de la función
    };

    triggers.forEach(trigger => {
        if (trigger.dataset.submenuInitialized) return;

        trigger.addEventListener('click', toggleSubmenu);
        trigger.dataset.submenuInitialized = 'true';
    });

    document.addEventListener('click', event => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            // Verificar si el submenú está visible antes de intentar ocultarlo
            if (submenu.style.display === 'block' && !submenu.contains(event.target) && !event.target.closest(triggerSelector) && !event.target.closest('a')) {
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

function submenu() {
    // Botón clase - submenu id - posición
    createSubmenu('.filtrosboton', 'filtrosMenu', 'abajo');
    createSubmenu('.mipsubmenu', 'submenuperfil', 'abajo');
    createSubmenu('.HR695R7', 'opcionesrola', 'abajo');
    createSubmenu('.HR695R8', 'opcionespost', 'abajo'); //especialmente este es el qume gustaría cerrar pero debería cerrar todos
    createSubmenu('.submenucolab', 'opcionescolab', 'abajo');
}

/*

por ejemplo cuando lo llamo aca

    function load(url, pushState) {
        if (!url || /^(javascript|data|vbscript):|#/.test(url.toLowerCase()) || url.includes('descarga_token')) return;
        if (pageCache[url] && shouldCache(url)) {
            document.getElementById('content').innerHTML = pageCache[url];
            if (pushState) history.pushState(null, '', url);
            reinit();
            // Llamar a hideAllSubmenus después de reinit si es necesario
            if (typeof window.hideAllSubmenus === 'function') {
                window.hideAllSubmenus();
            } else {
                error.log('hideAllSubmenus no definido');
            }
            return;
        }
        document.getElementById('loadingBar').style.cssText = 'width: 70%; opacity: 1; transition: width 0.4s ease';
        fetch(url)
            .then(r => r.text())
            .then(data => {
                const doc = new DOMParser().parseFromString(data, 'text/html');
                const content = doc.getElementById('content').innerHTML;
                document.getElementById('content').innerHTML = content;
                if (shouldCache(url)) pageCache[url] = content;
                document.getElementById('loadingBar').style.cssText = 'width: 100%; transition: width 0.1s ease, opacity 0.3s ease';
                setTimeout(() => (document.getElementById('loadingBar').style.cssText = 'width: 0%; opacity: 0'), 100);
                if (pushState) history.pushState(null, '', url);
                doc.querySelectorAll('script').forEach(s => {
                    if (s.src && !document.querySelector(`script[src="${s.src}"]`)) {
                        document.body.appendChild(Object.assign(document.createElement('script'), {src: s.src, async: false}));
                    } else if (!s.src) {
                        document.body.appendChild(Object.assign(document.createElement('script'), {textContent: s.textContent}));
                    }
                });
                setTimeout(reinit, 100);

                // Llamar a hideAllSubmenus después de reinit y después de que el DOM se haya actualizado
                setTimeout(() => {
                    if (typeof window.hideAllSubmenus === 'function') {
                        window.hideAllSubmenus();
                    }
                }, 150); // Ajusta el tiempo de espera según sea necesario
            })
            .catch(e => console.error('Load error:', e));
    }

*/

window.createSubmenuDarkBackground = function () {
    let darkBackground = document.getElementById('submenu-background5322');
    if (!darkBackground) {
        // Crear el fondo oscuro si no existe
        darkBackground = document.createElement('div');
        darkBackground.id = 'submenu-background5322';
        darkBackground.style.position = 'fixed';
        darkBackground.style.top = 0;
        darkBackground.style.left = 0;
        darkBackground.style.width = '100%';
        darkBackground.style.height = '100%';
        darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        darkBackground.style.zIndex = 1002;
        darkBackground.style.display = 'none';
        darkBackground.style.pointerEvents = 'none';
        darkBackground.style.opacity = '0';
        darkBackground.style.transition = 'opacity 0.3s ease';
        document.body.appendChild(darkBackground);

        // Agregar evento para cerrar submenús al hacer clic en el fondo oscuro
        darkBackground.addEventListener('click', () => {
            document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
                hideSubmenu(submenu);
            });
        });
    }

    darkBackground.style.display = 'block';
    setTimeout(() => {
        darkBackground.style.opacity = '1';
    }, 10);
    darkBackground.style.pointerEvents = 'auto';
};

// Eliminar el fondo oscuro
window.removeSubmenuDarkBackground = function () {
    const darkBackground = document.getElementById('submenu-background5322');
    if (darkBackground) {
        darkBackground.style.opacity = '0';
        setTimeout(() => {
            darkBackground.style.display = 'none';
            darkBackground.style.pointerEvents = 'none';
        }, 300);
    }
};

function calculatePosition(rect, submenuWidth, submenuHeight, position) {
    const {innerWidth: vw, innerHeight: vh} = window;
    let top, left;

    switch (position) {
        case 'arriba':
            top = rect.top - submenuHeight;
            left = rect.left + rect.width / 2 - submenuWidth / 2;
            break;
        case 'abajo':
            top = rect.bottom;
            left = rect.left + rect.width / 2 - submenuWidth / 2;
            break;
        case 'izquierda':
            top = rect.top + rect.height / 2 - submenuHeight / 2;
            left = rect.left - submenuWidth;
            break;
        case 'derecha':
            top = rect.top + rect.height / 2 - submenuHeight / 2;
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

    return {top, left};
}

function initializeStaticMenus() {
    // Ejemplos de uso con la nueva parametrización de posición
    createSubmenu('.chatIcono', 'bloqueConversaciones', 'abajo');
    createSubmenu('.fotoperfilsub', 'fotoperfilsub', 'abajo');
}

// Esto se reinicia cada vez que cargan nuevos posts

document.addEventListener('DOMContentLoaded', () => {
    initializeStaticMenus();
});
