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
  tooltip.style.maxWidth = '180px'; // Ancho máximo
  tooltip.style.minWidth = '80px'; // Ancho mínimo
  tooltip.style.wordWrap = 'break-word'; // Asegura que el texto no se salga del contenedor
  document.body.appendChild(tooltip);

  const PADDING = 10; // Espacio mínimo desde los bordes de la pantalla

  function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    if (text) {
      tooltip.textContent = text;
      tooltip.style.visibility = 'visible';

      // Obtener la posición del elemento
      const rect = e.target.getBoundingClientRect();

      // Calcular la posición base del tooltip
      let left = rect.left + window.scrollX + 10; // Posición horizontal
      let top = rect.bottom + window.scrollY + 10; // Posición vertical

      // Ajustar si el tooltip se sale por la derecha
      const tooltipWidth = tooltip.offsetWidth;
      if (left + tooltipWidth + PADDING > window.innerWidth) {
        left = window.innerWidth - tooltipWidth - PADDING;
      }

      // Ajustar si el tooltip se sale por la izquierda
      if (left < PADDING) {
        left = PADDING;
      }

      // Ajustar si el tooltip se sale por abajo
      const tooltipHeight = tooltip.offsetHeight;
      if (top + tooltipHeight + PADDING > window.innerHeight) {
        top = rect.top + window.scrollY - tooltipHeight - 10; // Mostrar el tooltip arriba del elemento
      }

      // Ajustar si el tooltip se sale por arriba
      if (top < PADDING) {
        top = PADDING;
      }

      // Aplicar las posiciones finales
      tooltip.style.left = left + 'px';
      tooltip.style.top = top + 'px';
    }
  }

  function hideTooltip() {
    tooltip.style.visibility = 'hidden';
  }

  // Función para actualizar la posición del tooltip si el elemento se mueve
  function updateTooltipPosition(element) {
    if (tooltip.style.visibility === 'visible') {
      const rect = element.getBoundingClientRect();
      let left = rect.left + window.scrollX + 10;
      let top = rect.bottom + window.scrollY + 10;

      // Ajustes basados en los bordes de la pantalla
      const tooltipWidth = tooltip.offsetWidth;
      const tooltipHeight = tooltip.offsetHeight;

      if (left + tooltipWidth + PADDING > window.innerWidth) {
        left = window.innerWidth - tooltipWidth - PADDING;
      }
      if (left < PADDING) {
        left = PADDING;
      }
      if (top + tooltipHeight + PADDING > window.innerHeight) {
        top = rect.top + window.scrollY - tooltipHeight - 10;
      }
      if (top < PADDING) {
        top = PADDING;
      }

      tooltip.style.left = left + 'px';
      tooltip.style.top = top + 'px';
    }
  }

  const tooltipElements = document.querySelectorAll('.tooltip-element');
  tooltipElements.forEach((element) => {
    element.addEventListener('mouseenter', showTooltip);
    element.addEventListener('mouseleave', hideTooltip);

    // Observar cambios en la posición del elemento
    const observer = new MutationObserver(() => {
      updateTooltipPosition(element);
    });

    observer.observe(element, {
      attributes: true,
      childList: true,
      subtree: true,
      attributeFilter: ['style', 'class'], // Puedes ajustar los atributos que quieres observar
    });
  });
}