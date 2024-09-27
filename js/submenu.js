function createSubmenu(triggerSelector, submenuIdPrefix, modalBackgroundClass, adjustTop = 0, adjustLeft = 0) {
    const triggers = document.querySelectorAll(triggerSelector);

    function toggleSubmenu(event) {
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) return; // Verificación adicional para evitar null
        const submenuId = `${submenuIdPrefix}-${trigger.dataset.postId || trigger.id || "default"}`;
        const submenu = document.getElementById(submenuId);
        if (!submenu) return; // Verificación para evitar null

        const modalBackground = trigger.closest(modalBackgroundClass);
        if (!modalBackground) return; // Verificación para evitar null

        submenu.classList.toggle('mobile-submenu', window.innerWidth <= 640);
        submenu.style.display === "block" ? hideSubmenu(submenu, modalBackground) : showSubmenu(event, submenu, modalBackground);
        event.stopPropagation();
    }

    function showSubmenu(event, submenu, modalBackground) {
        const rect = event.target.getBoundingClientRect();
        const { innerWidth: vw, innerHeight: vh } = window;

        if (vw > 640) {
            submenu.style.position = "fixed";
            submenu.style.top = `${Math.min(rect.bottom + adjustTop, vh - submenu.offsetHeight)}px`;
            submenu.style.left = `${Math.min(rect.left + adjustLeft, vw - submenu.offsetWidth)}px`;
        }

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
    createSubmenu(".subiricono", "submenusubir", ".modal-background", 0, 120);
}

function submenu() {
    createSubmenu(".mipsubmenu", "submenuperfil", ".modal-background", 0, 120);
    createSubmenu(".HR695R7", "opcionesrola", ".modal-background", 100, 0);
    createSubmenu(".HR695R8", "opcionespost", ".modal-background", 60, 0);
    initializeSubirSample("#subirsample", "#formulariosubirsample", "#social-post-container");
}

document.addEventListener('DOMContentLoaded', initializeStaticMenus);

function initializeSubirSample(triggerSelector, formSelector, containerSelector) {
    const trigger = document.querySelector(triggerSelector);
    const form = document.querySelector(formSelector);
    const container = document.querySelector(containerSelector);
    if (!trigger || !form || !container) return; // Verificación adicional para evitar null

    trigger.addEventListener("click", (event) => {
        form.style.display = "block";
        form.scrollIntoView({ behavior: "smooth" });
        event.stopPropagation();
    });

    document.addEventListener("click", (event) => {
        if (!form.contains(event.target) && event.target !== trigger && !container.contains(event.target)) {
            form.style.display = "none";
        }
    });

    form.addEventListener("click", (event) => {
        if (!container.contains(event.target)) form.style.display = "none";
        event.stopPropagation();
    });
}
