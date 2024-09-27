function createSubmenu(triggerSelector, submenuIdPrefix, adjustTop = 0, adjustLeft = 0) {
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

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        console.log('Clases del submenu:', submenu.className);
        
        if (submenu.style.display === "block") {
            console.log('Ocultando el submenu');
            hideSubmenu(submenu);
        } else {
            console.log('Mostrando el submenu');
            showSubmenu(event, submenu);
        }

        event.stopPropagation();
    }

    function showSubmenu(event, submenu) {
        const rect = event.target.getBoundingClientRect();
        console.log('Rectángulo del elemento:', rect);
        
        const { innerWidth: vw, innerHeight: vh } = window;
        console.log(`Dimensiones del viewport: ${vw}x${vh}`);

        // Posicionar el submenú dentro del viewport
        if (vw > 640) {
            submenu.style.position = "fixed";
            submenu.style.top = `${Math.min(rect.bottom + adjustTop, vh - submenu.offsetHeight)}px`;
            submenu.style.left = `${Math.min(rect.left + adjustLeft, vw - submenu.offsetWidth)}px`;
        }

        // Mostrar el submenú
        submenu.style.display = "block";

        // Crear un fondo semioscuro dinámico debajo del submenú
        const darkBackground = document.createElement('div');
        darkBackground.classList.add('submenu-background');
        darkBackground.style.position = 'fixed';
        darkBackground.style.top = 0;
        darkBackground.style.left = 0;
        darkBackground.style.width = '100vw';
        darkBackground.style.height = '100vh';
        darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        darkBackground.style.zIndex = 999;  // Asegura que el fondo quede detrás del submenú
        darkBackground.style.pointerEvents = 'none';  // Permitir clics a través del fondo
        submenu.parentElement.appendChild(darkBackground);

        // Agregamos una referencia al fondo en el submenú para eliminarlo después
        submenu._darkBackground = darkBackground;

        // Aumentar el índice z del submenú para que esté sobre el fondo semioscuro
        submenu.style.zIndex = 1000;
    }

    function hideSubmenu(submenu) {
        if (submenu) submenu.style.display = "none"; // Ocultar el submenú
        if (submenu._darkBackground) {
            submenu._darkBackground.remove();  // Eliminar el fondo semioscuro
            submenu._darkBackground = null;    // Limpiar la referencia
        }
    }

    triggers.forEach(trigger => trigger.addEventListener("click", toggleSubmenu));

    // Cerrar el submenú si se hace clic fuera de él
    document.addEventListener("click", (event) => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            if (!submenu.contains(event.target) && !event.target.matches(triggerSelector)) {
                hideSubmenu(submenu);
            }
        });
    });

    // Ajustar la clase del submenú en función del tamaño de la ventana
    window.addEventListener('resize', () => {
        document.querySelectorAll(`[id^="${submenuIdPrefix}-"]`).forEach(submenu => {
            submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        });
    });
}

function initializeStaticMenus() {
    console.log('Inicializando menús estáticos');
    createSubmenu(".subiricono", "submenusubir", 0, 120);
}

function submenu() {
    console.log('Inicializando submenús dinámicos');
    createSubmenu(".mipsubmenu", "submenuperfil", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", 60, 0);
}

document.addEventListener('DOMContentLoaded', initializeStaticMenus);