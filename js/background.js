// Función para crear y mostrar el fondo oscuro al mismo nivel que el submenú
window.createDarkBackground = function(submenu) {
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

// Función para remover el fondo oscuro
window.removeDarkBackground = function(darkBackground) {
    if (darkBackground && darkBackground.parentNode) {
        darkBackground.parentNode.removeChild(darkBackground);
    }
};
