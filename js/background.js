// Función para crear y mostrar el fondo oscuro
window.createDarkBackground = function() {
    const darkBackground = document.createElement('div');
    darkBackground.classList.add('submenu-background');
    darkBackground.style.position = 'fixed';
    darkBackground.style.top = 0;
    darkBackground.style.left = 0;
    darkBackground.style.width = '100vw';
    darkBackground.style.height = '100vh';
    darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    darkBackground.style.zIndex = 999;
    darkBackground.style.pointerEvents = 'auto';
    document.body.appendChild(darkBackground);
    return darkBackground;
};

// Función para remover el fondo oscuro
window.removeDarkBackground = function(darkBackground) {
    if (darkBackground && darkBackground.parentNode) {
        darkBackground.parentNode.removeChild(darkBackground);
    }
};