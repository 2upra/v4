//////////////////////////////////////////////
//ACTIVAR O DESACTIVAR LOGS
const A04 = false; // Cambia a true para activar los logs

const log04 = A04 ? log04 : function () {};
//////////////////////////////////////////////


function createSubmenu(triggerSelector, submenuIdPrefix, modalBackgroundId, adjustTop = 0, adjustLeft = 0) {
    const triggers = document.querySelectorAll(triggerSelector);
    const modalBackground = document.getElementById(modalBackgroundId);
    if (!modalBackground) return log04(`No se encontró el elemento de fondo modal con id ${modalBackgroundId}`);

    function toggleSubmenu(event) {
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) return log04("No se encontró el elemento trigger");

        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"}`;
        const submenu = document.getElementById(submenuId);
        if (!submenu) return log04(`No se encontró el submenu con id ${submenuId}`);

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);

        submenu.style.display === "block" ? hideSubmenu(submenu) : showSubmenu(event, submenu);
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
        modalBackground.style.display = "block";
    }

    function hideSubmenu(submenu) {
        submenu.style.display = "none";
        modalBackground.style.display = "none";
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
    createSubmenu(".subiricono", "submenusubir", "modalBackground2", 0, 120);
}

function submenu() {
    createSubmenu(".mipsubmenu", "submenuperfil", "modalBackground2", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", "modalBackground3", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", "modalBackground4", 60, 0);
    initializeSubirSample("#subirsample", "#formulariosubirsample", "#social-post-container");
}

// Ejecuta la inicialización de menús estáticos al cargar la página
document.addEventListener('DOMContentLoaded', initializeStaticMenus);

function initializeSubirSample(
    triggerSelector,
    formSelector,
    containerSelector
) {
    const trigger = document.querySelector(triggerSelector);
    const form = document.querySelector(formSelector);
    const container = document.querySelector(containerSelector);

    if (!trigger || !form || !container) return;

    trigger.addEventListener("click", (event) => {
        form.style.display = "block";
        form.scrollIntoView({ behavior: "smooth" });
        event.stopPropagation();
    });

    document.addEventListener("click", (event) => {
        if (
            !form.contains(event.target) &&
            event.target !== trigger &&
            !container.contains(event.target)
        ) {
            form.style.display = "none";
        }
    });

    form.addEventListener("click", (event) => {
        if (!container.contains(event.target)) {
            form.style.display = "none";
        }
        event.stopPropagation();
    });
}


