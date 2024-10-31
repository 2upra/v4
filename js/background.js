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

// Crear el observer
const observer = new MutationObserver((mutationsList) => {
  mutationsList.forEach((mutation) => {
    if (mutation.type === "attributes" && mutation.attributeName === "style") {
      const element = mutation.target;
      
      // Solo si el elemento tiene display distinto de 'none' y no tiene la clase 'fade-in'
      if (getComputedStyle(element).display !== 'none' && !element.classList.contains("fade-in")) {
        // Agregar la clase 'fade-in'
        element.classList.add("fade-in");
        
        // Forzar reflow para asegurarse de que el cambio de clase se registre
        void element.offsetWidth;
        
        // Agregar la clase 'show' para iniciar la transición
        element.classList.add("show");
        
        // Escuchar el final de la transición para limpiar las clases
        const handleTransitionEnd = () => {
          element.classList.remove("fade-in");
          element.classList.remove("show");
          element.removeEventListener("transitionend", handleTransitionEnd);
        };
        
        element.addEventListener("transitionend", handleTransitionEnd);
      }
    }
  });
});

// Observar cambios en el DOM y estilo
observer.observe(root, {
  attributes: true,
  attributeFilter: ["style"],
  subtree: true,
});
