function createSubmenu(triggerSelector, submenuIdPrefix, adjustTop = 0, adjustLeft = 0) {
    const triggers = document.querySelectorAll(triggerSelector);
    
    console.log(`[createSubmenu] Inicializando para el selector: ${triggerSelector}, submenú prefijo: ${submenuIdPrefix}, triggers encontrados: ${triggers.length}`);

    function toggleSubmenu(event) {
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) {
            console.warn(`[toggleSubmenu] No se encontró el trigger para el selector: ${triggerSelector}`);
            return;
        }

        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"}`;
        const submenu = document.getElementById(submenuId);
        
        console.log(`[toggleSubmenu] Trigger clickeado: ${trigger.id || trigger.dataset.postId || "default"}, Submenú ID: ${submenuId}`);

        if (!submenu) {
            console.warn(`[toggleSubmenu] No se encontró el submenú con ID: ${submenuId}`);
            return;
        }

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        console.log(`[toggleSubmenu] Submenú ID ${submenuId}, clase mobile-submenu: ${submenu.classList.contains('mobile-submenu')}`);

        if (submenu.style.display === "block") {
            console.log(`[toggleSubmenu] Ocultando submenú ID: ${submenuId}`);
            hideSubmenu(submenu);
        } else {
            console.log(`[toggleSubmenu] Mostrando submenú ID: ${submenuId}`);
            showSubmenu(event, submenu);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, submenu) {
        const rect = event.target.getBoundingClientRect();
        const { innerWidth: vw, innerHeight: vh } = window;

        console.log(`[showSubmenu] Mostrando submenú, posición del trigger: top=${rect.top}, left=${rect.left}`);

        if (vw > 640) {
            submenu.style.position = "fixed";
            submenu.style.top = `${Math.min(rect.bottom + adjustTop, vh - submenu.offsetHeight)}px`;
            submenu.style.left = `${Math.min(rect.left + adjustLeft, vw - submenu.offsetWidth)}px`;
            
            console.log(`[showSubmenu] Ajustando posición submenú para pantallas grandes: top=${submenu.style.top}, left=${submenu.style.left}`);
        }

        submenu.style.display = "block";
        submenu._darkBackground = createSubmenuDarkBackground(submenu);
        submenu.style.zIndex = 999;

        console.log(`[showSubmenu] Submenú ID ${submenu.id} mostrado con z-index: 999`);

        document.body.classList.add('no-scroll');

        submenu.addEventListener('click', (e) => {
            e.stopPropagation();
            console.log(`[showSubmenu] Clic dentro del submenú ID: ${submenu.id}, ocultándolo`);
            hideSubmenu(submenu);
        });
    }

    function hideSubmenu(submenu) {
        if (submenu) {
            console.log(`[hideSubmenu] Ocultando submenú ID: ${submenu.id}`);
            submenu.style.display = "none";
        }

        removeSubmenuDarkBackground(submenu._darkBackground);
        submenu._darkBackground = null;

        const activeSubmenus = Array.from(document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`)).filter(menu => menu.style.display === "block");

        if (activeSubmenus.length === 0) {
            console.log(`[hideSubmenu] No hay submenús activos, eliminando clase 'no-scroll' del body`);
            document.body.classList.remove('no-scroll');
        }
    }

    triggers.forEach(trigger => {
        console.log(`[createSubmenu] Añadiendo evento click al trigger: ${trigger.id || trigger.dataset.postId || "default"}`);
        trigger.addEventListener("click", toggleSubmenu);
    });

    document.addEventListener("click", (event) => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            if (!submenu.contains(event.target) && !event.target.matches(triggerSelector)) {
                console.log(`[document click] Clic fuera del submenú ID: ${submenu.id}, ocultándolo`);
                hideSubmenu(submenu);
            }
        });
    });

    window.addEventListener('resize', () => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
            console.log(`[window resize] Ajuste de clase mobile-submenu para submenú ID: ${submenu.id}, mobile-submenu: ${submenu.classList.contains('mobile-submenu')}`);
        });
    });
}

function initializeStaticMenus() {
    console.log('[initializeStaticMenus] Inicializando menús estáticos');
    createSubmenu(".subiricono", "submenusubir", 0, 120);
    createSubmenu(".chatIcono", "bloqueConversaciones", 30, -270);
}

function submenu() {
    console.log('[submenu] Inicializando menús dinámicos');
    createSubmenu(".mipsubmenu", "submenuperfil", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", 60, 0);
    createSubmenu(".submenucolab", "opcionescolab", 60, 0);
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('[DOMContentLoaded] Documento cargado, inicializando menús estáticos');
    initializeStaticMenus();
});