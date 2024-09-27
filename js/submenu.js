function createSubmenu(triggerSelector, submenuIdPrefix, modalBackgroundClass, adjustTop = 0, adjustLeft = 0) {
    const triggers = document.querySelectorAll(triggerSelector);
    console.log(`Triggers encontrados: ${triggers.length}`);
    
    function toggleSubmenu(event) {
        const trigger = event.target.closest(triggerSelector);
        console.log('Trigger clicado:', trigger);
        if (!trigger) return; // Verificación adicional para evitar null
        
        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"}`;
        console.log('ID del submenu:', submenuId);
        
        const submenu = document.getElementById(submenuId);
        console.log('Submenu:', submenu);
        if (!submenu) return; // Verificación para evitar null

        const modalBackground = trigger.closest(modalBackgroundClass);
        console.log('Fondo Modal:', modalBackground);
        if (!modalBackground) return; // Verificación para evitar null

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        console.log('Clases del submenu:', submenu.className);
        
        if (submenu.style.display === "block") {
            console.log('Ocultando el submenu');
            hideSubmenu(submenu, modalBackground);
        } else {
            console.log('Mostrando el submenu');
            showSubmenu(event, submenu, modalBackground);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, submenu, modalBackground) {
        const rect = event.target.getBoundingClientRect();
        console.log('Rectángulo del elemento:', rect);
        
        const { innerWidth: vw, innerHeight: vh } = window;
        console.log(`Dimensiones del viewport: ${vw}x${vh}`);

        if (vw > 640) {
            submenu.style.position = "fixed";
            submenu.style.top = `${Math.min(rect.bottom + adjustTop, vh - submenu.offsetHeight)}px`;
            submenu.style.left = `${Math.min(rect.left + adjustLeft, vw - submenu.offsetWidth)}px`;
        }

        console.log('Estilo de display del submenu configurado a block');
        submenu.style.display = "block";
        modalBackground.style.display = "block";
        document.body.classList.add('no-scroll');  // Bloquear scroll
    }

    function hideSubmenu(submenu, modalBackground) {
        if (submenu) submenu.style.display = "none"; // Verificación adicional
        if (modalBackground) modalBackground.style.display = "none"; // Verificación adicional
        document.body.classList.remove('no-scroll');  // Habilitar scroll
    }

    triggers.forEach(trigger => trigger.addEventListener("click", toggleSubmenu));

    document.addEventListener("click", (event) => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            const modalBackground = submenu.closest(modalBackgroundClass);
            if (!submenu.contains(event.target) && !event.target.matches(triggerSelector)) {
                hideSubmenu(submenu, modalBackground);
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
    console.log('Inicializando menús estáticos');
    createSubmenu(".subiricono", "submenusubir", ".modal-background", 0, 120);
}

function submenu() {
    console.log('Inicializando submenús dinámicos');
    createSubmenu(".mipsubmenu", "submenuperfil", ".modal-background", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", ".modal-background", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", ".modal-background", 60, 0);
}

document.addEventListener('DOMContentLoaded', initializeStaticMenus);