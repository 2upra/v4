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
    document.body.appendChild(tooltip);

    let currentTooltipElement = null; // Para rastrear el elemento con tooltip activo

    function showTooltip(e) {
        const text = e.target.getAttribute('data-tooltip');
        if (text) {
            tooltip.textContent = text;
            tooltip.style.visibility = 'visible';
            updateTooltipPosition(e); // Posición inicial
            currentTooltipElement = e.target; // Guarda el elemento actual
        }
    }

    function hideTooltip() {
        tooltip.style.visibility = 'hidden';
        currentTooltipElement = null; // Resetea el elemento activo
    }

    function updateTooltipPosition(e) {
        tooltip.style.left = e.clientX + 10 + 'px';
        tooltip.style.top = e.clientY + 10 + 'px';
    }

    const tooltipElements = document.querySelectorAll('.tooltip-element');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);

        // Actualizar la posición mientras el ratón está dentro
        element.addEventListener('mousemove', updateTooltipPosition);
    });
}

// No olvides llamar a la función tooltips() para inicializarla
tooltips();