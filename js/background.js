// Función para crear y mostrar el fondo oscuro al mismo nivel que el submenú
window.createSubmenuDarkBackground = function(submenu) {
    const darkBackground = document.createElement('div');
    darkBackground.classList.add('submenu-background');
    darkBackground.style.position = 'fixed';
    darkBackground.style.top = 0;
    darkBackground.style.left = 0;
    darkBackground.style.width = '100vw';
    darkBackground.style.height = '100vh';
    darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    darkBackground.style.zIndex = 998; // Debe estar por debajo del submenú
    darkBackground.style.pointerEvents = 'auto';

    // Insertar el background justo antes del submenu, como hermano
    submenu.parentNode.insertBefore(darkBackground, submenu);

    return darkBackground;
};

// Función para remover el fondo oscuro del submenú
window.removeSubmenuDarkBackground = function(darkBackground) {
    if (darkBackground && darkBackground.parentNode) {
        darkBackground.parentNode.removeChild(darkBackground);
    }
};

// Función para crear y mostrar el fondo oscuro al mismo nivel que el modal
window.createModalDarkBackground = function(modal) {
    const darkBackground = document.createElement('div');
    darkBackground.classList.add('modal-background');
    darkBackground.style.position = 'fixed';
    darkBackground.style.top = 0;
    darkBackground.style.left = 0;
    darkBackground.style.width = '100vw';
    darkBackground.style.height = '100vh';
    darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    darkBackground.style.zIndex = 998; // Debe estar por debajo del modal
    darkBackground.style.pointerEvents = 'auto';

    // Insertar el background justo antes del modal, como hermano
    modal.parentNode.insertBefore(darkBackground, modal);

    return darkBackground;
};

// Función para remover el fondo oscuro del modal
window.removeModalDarkBackground = function(darkBackground) {
    if (darkBackground && darkBackground.parentNode) {
        darkBackground.parentNode.removeChild(darkBackground);
    }
};

(function() {
    const root = document.body;

    // Función auxiliar para agregar transición
    function addTransition(element, from, to) {
        // Si ya está en transición, no hacer nada
        if (element._isTransitioning) return;
        
        element._isTransitioning = true;  // Marcar el estado de transición

        // Configurar el estado inicial de opacidad
        element.style.opacity = from;
        element.style.transition = "opacity 0.3s ease";
        
        // Configurar la visualización (display) adecuada
        if (to === 0) {
            element._previousDisplay = getComputedStyle(element).display !== 'none' ? getComputedStyle(element).display : 'block';
            element.style.display = element._previousDisplay;  // Asegurar que se muestre antes de la transición
        }

        // Forzar reflow
        element.offsetHeight;

        // Aplicar el estado final de opacidad
        element.style.opacity = to;

        // Limpiar después de la transición
        element.addEventListener('transitionend', function handler() {
            element._isTransitioning = false;
            element.removeEventListener('transitionend', handler);
            if (to === 0) {
                element.style.display = 'none';  // Ocultar el elemento después de la transición
            }
            element.style.transition = '';  // Limpiar la transición
        }, { once: true });
    }

    // Función global para observar cambios en estilo y aplicar transiciones
    function observarTransiciones() {
        const observer = new MutationObserver((mutationsList) => {
            mutationsList.forEach((mutation) => {
                if (mutation.type === "attributes" && mutation.attributeName === "style") {
                    const element = mutation.target;
                    const display = getComputedStyle(element).display;

                    // Guardar el valor de display si es que no es 'none'
                    if (display !== 'none') {
                        element._previousDisplay = display;
                    }

                    // Si cambia a 'none', iniciar la transición para ocultar
                    if (display === 'none' && !element._isTransitioning) {
                        element.style.display = element._previousDisplay || 'block';  // Mostrar el elemento temporalmente
                        addTransition(element, 1, 0);  // Iniciar la transición para ocultar
                    }
                    // Si cambia de 'none' a visible, iniciar la transición para mostrar
                    else if (display !== 'none' && element.style.opacity !== '1') {
                        addTransition(element, 0, 1);  // Iniciar la transición para mostrar
                    }
                }
            });
        });

        observer.observe(root, {
            attributes: true,
            attributeFilter: ["style"],
            subtree: true
        });
    }

    // Funciones helpers para mostrar y ocultar elementos manualmente
    function mostrar(element) {
        if (getComputedStyle(element).display === 'none') {
            addTransition(element, 0, 1);  // Transición para mostrar
        }
    }

    function ocultar(element) {
        if (getComputedStyle(element).display !== 'none') {
            addTransition(element, 1, 0);  // Transición para ocultar
        }
    }

    // Hacer funciones globales
    window.mostrarElemento = mostrar;
    window.ocultarElemento = ocultar;
    window.iniciarObservadorTransiciones = observarTransiciones;

    // Iniciar observador automáticamente al cargar el script
    observarTransiciones();
})();