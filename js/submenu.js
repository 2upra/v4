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
            showSubmenu(event, submenu, submenu._position);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, submenu, position) {
        const { innerWidth: vw, innerHeight: vh } = window;

        // Mover el submenú al body si no está ya allí
        if (!document.body.contains(submenu)) {
            document.body.appendChild(submenu);
        }

        submenu.style.position = "fixed";
        submenu.style.zIndex = 9999; // Asegúrate de que tenga un z-index alto

        if (vw <= 640) {
            // En dispositivos móviles, centrar el submenú
            submenu.style.top = `${(vh - submenu.offsetHeight) / 2}px`;
            submenu.style.left = `${(vw - submenu.offsetWidth) / 2}px`;
        } else {
            const rect = event.target.getBoundingClientRect();
            const { top, left } = calculatePosition(rect, submenu, position);
            submenu.style.top = `${top}px`;
            submenu.style.left = `${left}px`;
        }

        submenu.style.display = "block";

        submenu._darkBackground = createSubmenuDarkBackground(submenu);

        document.body.classList.add('no-scroll');

        submenu.addEventListener('click', (e) => {
            e.stopPropagation();
            hideSubmenu(submenu);
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

function calculatePosition(rect, submenu, position) {
    const { innerWidth: vw, innerHeight: vh } = window;
    let top, left;

    switch (position) {
        case 'arriba':
            top = rect.top - submenu.offsetHeight;
            left = rect.left + (rect.width / 2) - (submenu.offsetWidth / 2);
            break;
        case 'abajo':
            top = rect.bottom;
            left = rect.left + (rect.width / 2) - (submenu.offsetWidth / 2);
            break;
        case 'izquierda':
            top = rect.top + (rect.height / 2) - (submenu.offsetHeight / 2);
            left = rect.left - submenu.offsetWidth;
            break;
        case 'derecha':
            top = rect.top + (rect.height / 2) - (submenu.offsetHeight / 2);
            left = rect.right;
            break;
        case 'centro':
            top = (vh - submenu.offsetHeight) / 2;
            left = (vw - submenu.offsetWidth) / 2;
            break;
        default:
            // 'auto' o cualquier otro valor: posicionar debajo del trigger
            top = rect.bottom;
            left = rect.left;
            break;
    }

    // Asegurar que el submenú no se salga de la pantalla
    top = Math.max(0, Math.min(top, vh - submenu.offsetHeight));
    left = Math.max(0, Math.min(left, vw - submenu.offsetWidth));

    return { top, left };
}

function createSubmenuDarkBackground(submenu) {
    // Implementa tu función para crear el fondo oscuro detrás del submenú
    // Aquí puedes agregar el código que ya tengas para esto
    const darkBackground = document.createElement('div');
    darkBackground.style.position = 'fixed';
    darkBackground.style.top = 0;
    darkBackground.style.left = 0;
    darkBackground.style.width = '100%';
    darkBackground.style.height = '100%';
    darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    darkBackground.style.zIndex = 9998; // Debe estar debajo del submenú
    document.body.appendChild(darkBackground);

    darkBackground.addEventListener('click', () => {
        hideSubmenu(submenu);
    });

    return darkBackground;
}

function removeSubmenuDarkBackground(darkBackground) {
    if (darkBackground && darkBackground.parentNode) {
        darkBackground.parentNode.removeChild(darkBackground);
    }
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