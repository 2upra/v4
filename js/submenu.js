
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
            e.stopPropagation(); 
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