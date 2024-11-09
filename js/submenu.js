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

        // Mover el submenú al body si no está ya allí
        if (!document.body.contains(submenu)) {
            document.body.appendChild(submenu);
        }

        submenu.style.position = "fixed";
        submenu.style.zIndex = 9999; // Asegúrate de que tenga un z-index alto
        submenu.style.top = `${Math.min(rect.bottom + adjustTop, vh - submenu.offsetHeight)}px`;
        submenu.style.left = `${Math.min(rect.left + adjustLeft, vw - submenu.offsetWidth)}px`;
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

function initializeStaticMenus() {
    //console.log('[initializeStaticMenus] Inicializando menús estáticos');
    createSubmenu(".subiricono", "submenusubir", 0, 120);
    createSubmenu(".chatIcono", "bloqueConversaciones", 30, -270);
    createSubmenu(".fotoperfilsub", "fotoperfilsub", 80, -30);
}

// Esto se reinicia cada vez que cargan nuevos posts
function submenu() {
    //boton clase - submenu id - posicon x - posicion y
    createSubmenu(".filtrosboton", "filtrosMenu", 0, 0);
    createSubmenu(".mipsubmenu", "submenuperfil", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", 60, 0);
    createSubmenu(".submenucolab", "opcionescolab", 60, 0);
}

// PORQUE SI TENGO ESTE BOTON <button class="filtrosboton">Filtros<? echo $GLOBALS['filtro']; ?></button> Y ESTE SUBMENU 

document.addEventListener('DOMContentLoaded', () => {
    //console.log('[DOMContentLoaded] Documento cargado, inicializando menús estáticos');
    initializeStaticMenus();
});