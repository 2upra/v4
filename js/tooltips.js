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
    tooltip.style.fontSize = '11px'; // Tamaño de fuente
    tooltip.style.maxWidth = '180px'; // Ancho máximo
    document.body.appendChild(tooltip);

    function showTooltip(e) {
        const text = e.target.getAttribute('data-tooltip');
        if (text) {
            tooltip.textContent = text;
            tooltip.style.visibility = 'visible';
            tooltip.style.left = e.clientX + 10 + 'px';
            tooltip.style.top = e.clientY + 10 + 'px';
        }
    }

    function hideTooltip() {
        tooltip.style.visibility = 'hidden';
    }

    const tooltipElements = document.querySelectorAll('.tooltip-element');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function (e) {
            showTooltip(e);
        });
        element.addEventListener('mouseleave', function (e) {
            hideTooltip(e);
        });
    });
}
