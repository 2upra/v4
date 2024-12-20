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
  
        // Obtener la posición del elemento
        const rect = e.target.getBoundingClientRect();
  
        // Posicionar el tooltip cerca del elemento
        tooltip.style.left = rect.left + window.scrollX + 10 + 'px'; // Considerar el scroll
        tooltip.style.top = rect.bottom + window.scrollY + 10 + 'px'; // Considerar el scroll
      }
    }
  
    function hideTooltip() {
      tooltip.style.visibility = 'hidden';
    }
  
    // Función para actualizar la posición del tooltip si el elemento se mueve
    function updateTooltipPosition(element) {
      if (tooltip.style.visibility === 'visible') {
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + window.scrollX + 10 + 'px';
        tooltip.style.top = rect.bottom + window.scrollY + 10 + 'px';
      }
    }
  
    const tooltipElements = document.querySelectorAll('.tooltip-element');
    tooltipElements.forEach(element => {
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
  
      // También puedes usar requestAnimationFrame para una actualización más fluida
      // pero es importante considerar el rendimiento.
      // function animateTooltip() {
      //   updateTooltipPosition(element);
      //   requestAnimationFrame(animateTooltip);
      // }
      // element.addEventListener('mouseenter', () => {
      //   showTooltip(element); // Asegúrate de pasar el elemento aquí
      //   requestAnimationFrame(animateTooltip);
      // });
      // element.addEventListener('mouseleave', hideTooltip);
    });
  }