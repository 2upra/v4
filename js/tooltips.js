function tooltips() {
    const tooltip = document.createElement('div');
    tooltip.style.position = 'absolute';
    tooltip.style.visibility = 'hidden';
    tooltip.style.backgroundColor = '#050505';
    tooltip.style.color = '#aaa';
    tooltip.style.padding = '10px';
    tooltip.style.borderRadius = '5px';
    tooltip.style.zIndex = '9999';
    tooltip.style.pointerEvents = 'none';
    tooltip.style.fontSize = '11px';
    tooltip.style.maxWidth = '180px';
    tooltip.style.minWidth = '80px'; // Ancho mínimo
    document.body.appendChild(tooltip);

    function showTooltip(e) {
        const text = e.target.getAttribute('data-tooltip');
        if (text) {
            tooltip.textContent = text;
            tooltip.style.visibility = 'visible';

            // Obtener la posición del elemento
            const rect = e.target.getBoundingClientRect();
            const elementWidth = rect.width;
            const elementHeight = rect.height;

            // Obtener el tamaño del tooltip
            tooltip.style.visibility = 'hidden'; // Temporalmente oculto para obtener las dimensiones correctas
            tooltip.style.left = '-9999px';
            tooltip.style.top = '-9999px';
            tooltip.style.display = 'block';
            const tooltipWidth = tooltip.offsetWidth;
            const tooltipHeight = tooltip.offsetHeight;
            tooltip.style.display = 'none'; // Restaurar el estado original
            tooltip.style.visibility = 'visible';

            // Obtener el tamaño de la ventana del navegador
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;

            // Definir un padding para los bordes de la pantalla
            const padding = 10;

            // Calcular la posición del tooltip
            let left = rect.left + window.scrollX + elementWidth / 2 - tooltipWidth / 2; // Centrado horizontalmente
            let top = rect.bottom + window.scrollY + padding; // Debajo del elemento

            // Ajustar la posición si el tooltip se sale por la derecha
            if (left + tooltipWidth + padding > windowWidth) {
                left = windowWidth - tooltipWidth - padding;
            }

            // Ajustar la posición si el tooltip se sale por la izquierda
            if (left < padding) {
                left = padding;
            }

            // Ajustar la posición si el tooltip se sale por abajo
            if (top + tooltipHeight + padding > windowHeight) {
                top = rect.top + window.scrollY - tooltipHeight - padding; // Arriba del elemento
            }

            // Ajustar si el tooltip queda fuera de la pantalla en la parte superior
            if (top < window.scrollY + padding) {
                top = window.scrollY + padding;
            }

            // Posicionar el tooltip
            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
        }
    }

    function hideTooltip() {
        tooltip.style.visibility = 'hidden';
    }

    // La función updateTooltipPosition no es necesaria con el nuevo cálculo

    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}
