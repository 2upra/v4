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
            const trigger = event.target.closest(triggerSelector);
            const triggerRect = trigger.getBoundingClientRect();
            const { top, left } = calculatePosition(triggerRect, submenu, position);
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

function calculatePosition(triggerRect, submenu, position) {
    const { innerWidth: vw, innerHeight: vh } = window;
    let top, left;

    switch (position) {
        case 'arriba':
            top = triggerRect.top - submenu.offsetHeight;
            left = triggerRect.left + (triggerRect.width / 2) - (submenu.offsetWidth / 2);
            break;
        case 'abajo':
            // Posicionar debajo del trigger, alineado a la izquierda del trigger
            top = triggerRect.bottom;
            left = triggerRect.left;
            break;
        case 'izquierda':
            top = triggerRect.top + (triggerRect.height / 2) - (submenu.offsetHeight / 2);
            left = triggerRect.left - submenu.offsetWidth;
            break;
        case 'derecha':
            top = triggerRect.top + (triggerRect.height / 2) - (submenu.offsetHeight / 2);
            left = triggerRect.right;
            break;
        case 'centro':
            top = (vh - submenu.offsetHeight) / 2;
            left = (vw - submenu.offsetWidth) / 2;
            break;
        default:
            // 'auto' o cualquier otro valor: posicionar debajo del trigger, alineado a la izquierda del trigger
            top = triggerRect.bottom;
            left = triggerRect.left;
            break;
    }

    // Asegurar que el submenú no se salga de la pantalla, ajustar si es necesario
    if (top < 0) {
        top = triggerRect.bottom; // Si se sale por arriba, mostrarlo debajo del trigger
    }
    if (top + submenu.offsetHeight > vh) {
        top = vh - submenu.offsetHeight; // Si se sale por abajo, ajustarlo al borde inferior
    }
    if (left < 0) {
        left = 0; // Si se sale por la izquierda, ajustarlo al borde izquierdo
    }
    if (left + submenu.offsetWidth > vw) {
        left = vw - submenu.offsetWidth; // Si se sale por la derecha, ajustarlo al borde derecho
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