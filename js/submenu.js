function createSubmenu(triggerSelector, submenuIdPrefix, adjustTop = 0, adjustLeft = 0) {
    const triggers = document.querySelectorAll(triggerSelector);
    console.log("Número de disparadores encontrados:", triggers.length);

    function toggleSubmenu(event) {
        console.log("Evento para alternar el submenú activado.");
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) {
            console.warn("No se encontró un disparador para el objetivo del evento.");
            return;
        }

        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"}`;
        console.log("Intentando alternar el submenú con ID:", submenuId);
        const submenu = document.getElementById(submenuId);
        if (!submenu) {
            console.warn("Submenú no encontrado:", submenuId);
            return;
        }

        console.log("Estilo de visualización actual del submenú:", submenu.style.display);

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);

        if (submenu.style.display === "block") {
            console.log("El submenú está actualmente visible. Ocultando submenú.");
            hideSubmenu(submenu);
        } else {
            console.log("El submenú está actualmente oculto. Mostrando submenú.");
            showSubmenu(event, submenu);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, submenu) {
        console.log("Mostrando submenú:", submenu.id);
        const rect = event.target.getBoundingClientRect();
        const { innerWidth: vw, innerHeight: vh } = window;

        if (vw > 640) {
            console.log("Posicionando submenú para vista de escritorio.");
            submenu.style.position = "fixed";
            submenu.style.top = `${Math.min(rect.bottom + adjustTop, vh - submenu.offsetHeight)}px`;
            submenu.style.left = `${Math.min(rect.left + adjustLeft, vw - submenu.offsetWidth)}px`;
        }

        submenu.style.display = "block";
        console.log("Submenú posicionado en:", submenu.style.top, submenu.style.left);

        submenu._darkBackground = createSubmenuDarkBackground(submenu);
        submenu.style.zIndex = 999; // Siempre por encima del fondo oscuro

        document.body.classList.add('no-scroll');
        console.log("Clase no-scroll añadida al cuerpo.");

        // Evitar el cierre del submenú en clic interno
        submenu.addEventListener('click', (e) => {
            console.log("Submenú clicado. Propagación del evento detenida.");
            e.stopPropagation();
            hideSubmenu(submenu); // Desmarca esta línea si no deseas cerrar el submenú con clics internos
        });
    }

    function hideSubmenu(submenu) {
        console.log("Ocultando submenú:", submenu.id);
        if (submenu) submenu.style.display = "none";
        removeSubmenuDarkBackground(submenu._darkBackground);
        submenu._darkBackground = null;

        const activeSubmenus = Array.from(document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`)).filter(menu => menu.style.display === "block");
        console.log("Submenús activos tras ocultar:", activeSubmenus.length);

        if (activeSubmenus.length === 0) {
            document.body.classList.remove('no-scroll');
            console.log("Sin submenús activos. Scroll de página restaurado.");
        }
    }

    triggers.forEach(trigger => trigger.addEventListener("click", toggleSubmenu));

    document.addEventListener("click", (event) => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            if (!submenu.contains(event.target) && !event.target.matches(triggerSelector)) {
                console.log("Documento clicado fuera del submenú y del disparador. Ocultando submenú:", submenu.id);
                hideSubmenu(submenu);
            }
        });
    });

    window.addEventListener('resize', () => {
        console.log("Ventana redimensionada. Ajustando clases del submenú.");
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        });
    });
}

function initializeStaticMenus() {
    console.log("Inicializando menús estáticos.");
    createSubmenu(".subiricono", "submenusubir", 0, 120);
    createSubmenu(".chatIcono", "bloqueConversaciones", 30, -270);
}


function submenu() {
    console.log("Reiniciando ajax submenu");
    createSubmenu(".mipsubmenu", "submenuperfil", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", 60, 0);
    createSubmenu(".submenucolab", "opcionescolab", 60, 0);
}

document.addEventListener('DOMContentLoaded', initializeStaticMenus);