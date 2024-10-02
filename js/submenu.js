function createSubmenu(triggerSelector, submenuIdPrefix, adjustTop = 0, adjustLeft = 0) {
    const triggers = document.querySelectorAll(triggerSelector);
    
    function toggleSubmenu(event) {
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) return;

        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"}`;
        const submenu = document.getElementById(submenuId);
        if (!submenu) return;

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);

        if (submenu.style.display === "block") {
            hideSubmenu(submenu);
        } else {
            showSubmenu(event, submenu);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, submenu) {
        const rect = event.target.getBoundingClientRect();
        const { innerWidth: vw, innerHeight: vh } = window;

        if (vw > 640) {
            submenu.style.position = "fixed";
            submenu.style.top = `${Math.min(rect.bottom + adjustTop, vh - submenu.offsetHeight)}px`;
            submenu.style.left = `${Math.min(rect.left + adjustLeft, vw - submenu.offsetWidth)}px`;
        }

        submenu.style.display = "block";

        submenu._darkBackground = createSubmenuDarkBackground(submenu);
        submenu.style.zIndex = 999; // Siempre por encima del fondo oscuro

        document.body.classList.add('no-scroll');

        // Agregar evento click a los botones dentro del submenu
        const buttons = submenu.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                hideSubmenu(submenu); // Cierra el submenu al hacer clic en un botón
            });
        });
    }

    function hideSubmenu(submenu) {
        if (submenu) submenu.style.display = "none";
        removeSubmenuDarkBackground(submenu._darkBackground);
        submenu._darkBackground = null;

        const activeSubmenus = Array.from(document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`)).filter(menu => menu.style.display === "block");

        if (activeSubmenus.length === 0) {
            document.body.classList.remove('no-scroll');
        }
    }

    triggers.forEach(trigger => trigger.addEventListener("click", toggleSubmenu));

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

function initializeStaticMenus() {
    createSubmenu(".subiricono", "submenusubir", 0, 120);
    createSubmenu(".chatIcono", "bloqueConversaciones", 120, 0);
}

function submenu() {
    createSubmenu(".mipsubmenu", "submenuperfil", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", 60, 0);
    createSubmenu(".submenucolab", "opcionescolab", 60, 0);
}

document.addEventListener('DOMContentLoaded', initializeStaticMenus);