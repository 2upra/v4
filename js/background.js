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

const root = document.body;
const observer = new MutationObserver((mutationsList) => {
  mutationsList.forEach((mutation) => {
    if (mutation.type === "attributes" && mutation.attributeName === "style") {
      const element = mutation.target;
      
      if (element._isTransitioning) return; // Ignorar si ya está en transición
      
      if (getComputedStyle(element).display !== 'none') {
        element._isTransitioning = true; // Marcar como en transición
        element.style.opacity = 0;
        element.style.transition = "opacity 0.5s";
        
        requestAnimationFrame(() => {
          element.style.opacity = 1;
        });
        
        element.addEventListener("transitionend", function handler() {
          element._isTransitioning = false; // Quitar la marca
          element.style.transition = ""; // Limpiar transición
          element.removeEventListener("transitionend", handler);
        });
      }
    }
  });
});

observer.observe(root, {
  attributes: true,
  attributeFilter: ["style"],
  subtree: true,
});
