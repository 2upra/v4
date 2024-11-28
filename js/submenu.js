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
            showSubmenu(event, trigger, submenu, submenu._position); // Pasamos el trigger
        }

        event.stopPropagation(); // Evita que el clic se propague al documento
    }

    function showSubmenu(event, trigger, submenu, position) {
        const { innerWidth: vw, innerHeight: vh } = window;

        // Mover el submenú al body si no está ya allí
        if (submenu.parentNode !== document.body) {
            document.body.appendChild(submenu);
        }

        submenu.style.position = "fixed";
        submenu.style.zIndex = 1001; // Asegúrate de que tenga un z-index alto

        // Hacemos que el submenú sea temporalmente visible para calcular sus dimensiones
        submenu.style.display = "block";
        submenu.style.visibility = "hidden";

        // Obtenemos las dimensiones del submenú
        let submenuWidth = submenu.offsetWidth;
        let submenuHeight = submenu.offsetHeight;

        // Obtenemos el rectángulo del elemento desencadenante
        const rect = trigger.getBoundingClientRect();

        // En dispositivos móviles, centrar el submenú
        if (vw <= 640) {
            submenu.style.top = `${(vh - submenuHeight) / 2}px`;
            submenu.style.left = `${(vw - submenuWidth) / 2}px`;
        } else {
            let { top, left } = calculatePosition(rect, submenuWidth, submenuHeight, position);

            // Asegurar que el submenú no se salga de la pantalla
            if (top + submenuHeight > vh) {
                top = vh - submenuHeight;
            }
            if (left + submenuWidth > vw) {
                left = vw - submenuWidth;
            }
            if (top < 0) {
                top = 0;
            }
            if (left < 0) {
                left = 0;
            }

            submenu.style.top = `${top}px`;
            submenu.style.left = `${left}px`;
        }

        // Ahora hacemos visible el submenú
        submenu.style.visibility = "visible";

        submenu._darkBackground = createSubmenuDarkBackground(submenu);

        document.body.classList.add('no-scroll');

        // **Evitar que los clics dentro del submenú lo cierren**
        submenu.addEventListener('click', (e) => {
            e.stopPropagation(); // Evita que el clic dentro del submenú cierre el mismo
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
    createSubmenu(".chatIcono", "bloqueConversaciones", 'izquierda');
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