function vistaPost() {
    const botonCambiarVista = document.getElementById('cambiarVista');
    const listas = document.querySelectorAll('ul.social-post-list.clase-nada[data-posttype="social_post"]');
    const CLAVE_VISTA = 'vistaGridActivada';

    // Función para aplicar o quitar la clase
    const actualizarVista = activar => {
        listas.forEach(lista => {
            if (activar) {
                lista.classList.add('vistagrid');
            } else {
                lista.classList.remove('vistagrid');
            }
        });
    };

    // Cargar preferencia almacenada
    const vistaGuardada = localStorage.getItem(CLAVE_VISTA);
    if (vistaGuardada === 'true') {
        actualizarVista(true);
    }

    // Manejar el clic en el botón
    botonCambiarVista.addEventListener('click', () => {
        const estaActivo = listas[0].classList.contains('vistagrid');
        const nuevaEstado = !estaActivo;
        actualizarVista(nuevaEstado);
        localStorage.setItem(CLAVE_VISTA, nuevaEstado);
    });
}
