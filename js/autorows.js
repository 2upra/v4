function autoRows() {
    // Función para ajustar el tamaño del textarea automáticamente
    function autoResizeTextarea() {
        this.style.height = 'auto'; // Reinicia la altura
        this.style.height = this.scrollHeight + 'px'; // Ajusta según el contenido
    }

    // Selecciona todos los textarea en la página
    const textareas = document.querySelectorAll('textarea');

    // Añade un event listener a cada textarea para ajustar su tamaño automáticamente
    textareas.forEach(textarea => {
        textarea.addEventListener('input', autoResizeTextarea);
    });
}
