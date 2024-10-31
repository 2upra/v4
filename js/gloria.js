function addTransition(element, from, to) {
    if (element._isTransitioning) return;
    element._isTransitioning = true; 
    element.style.opacity = from;
    element.style.transition = 'opacity 0.3s ease';
    if (to === 1) {
        element.style.display = element._previousDisplay || 'block'; 
    }
    element.offsetHeight;
    element.style.opacity = to;
    element.addEventListener(
        'transitionend',
        function handler() {
            element._isTransitioning = false;
            element.removeEventListener('transitionend', handler);
            if (to === 0) {
                element.style.display = 'none';
            }
            element.style.transition = ''; 
        },
        {once: true}
    );
}

window.mostrar = function (element) {
    if (getComputedStyle(element).display === 'none') {
        element._previousDisplay = getComputedStyle(element).display === 'none' ? 'block' : getComputedStyle(element).display;
        addTransition(element, 0, 1);
    }
}

window.ocultar = function (element) {
    if (getComputedStyle(element).display !== 'none') {
        addTransition(element, 1, 0); 
    }
}