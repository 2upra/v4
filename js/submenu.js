function createSubmenu(triggerSelector, submenuIdPrefix, adjustTop = 0, adjustLeft = 0) {
    const triggers = document.querySelectorAll(triggerSelector);
    console.log(`Buscando triggers con selector: ${triggerSelector}. Encontrados:`, triggers);

    function toggleSubmenu(event) {
        console.log(`Evento click recibido para:`, event.target);

        const trigger = event.target.closest(triggerSelector);
        if (!trigger) {
            console.log('Trigger no encontrado.');
            return;
        }

        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"}`;
        console.log(`ID del submenu a buscar: ${submenuId}`);

        const submenu = document.getElementById(submenuId);
        if (!submenu) {
            console.log(`Submenu con ID ${submenuId} no encontrado.`);
            return;
        }

        console.log(`Submenu encontrado. Estado actual de display: ${submenu.style.display}`);
        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);

        if (submenu.style.display === "block") {
            console.log('Ocultando submenu porque ya está visible.');
            hideSubmenu(submenu);
        } else {
            console.log('Mostrando submenu porque no está visible.');
            showSubmenu(event, submenu);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, submenu) {
        console.log(`Mostrando submenu:`, submenu);

        const rect = event.target.getBoundingClientRect();
        const { innerWidth: vw, innerHeight: vh } = window;

        if (vw > 640) {
            submenu.style.position = "fixed";
            submenu.style.top = `${Math.min(rect.bottom + adjustTop, vh - submenu.offsetHeight)}px`;
            submenu.style.left = `${Math.min(rect.left + adjustLeft, vw - submenu.offsetWidth)}px`;
        }

        submenu.style.display = "block";
        console.log(`Submenu display establecido a block. Posición: top=${submenu.style.top}, left=${submenu.style.left}`);

        submenu._darkBackground = createSubmenuDarkBackground(submenu);
        submenu.style.zIndex = 999; // Siempre por encima del fondo oscuro

        document.body.classList.add('no-scroll');

        const buttons = submenu.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                console.log('Botón dentro del submenu clickeado. Cerrando submenu.');
                hideSubmenu(submenu);
            });
        });
    }

    function hideSubmenu(submenu) {
        console.log(`Ocultando submenu:`, submenu);
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
    createSubmenu(".chatIcono", "bloqueConversaciones", 50, 0);
}

function submenu() {
    createSubmenu(".mipsubmenu", "submenuperfil", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", 60, 0);
    createSubmenu(".submenucolab", "opcionescolab", 60, 0);
}

document.addEventListener('DOMContentLoaded', initializeStaticMenus);