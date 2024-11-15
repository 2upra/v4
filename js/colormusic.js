function updateBackgroundColor() {
    const img = document.querySelector('.um-avatar'); 
    if (!img) {
        return; // Sale de la función si no encuentra el elemento de la imagen
    }

    if (img.complete) {
        setColor(img);
    } else {
        img.addEventListener('load', function() {
            setColor(img);
        });
    }

    function setColor(img) {
        const colorThief = new ColorThief();
        if (!colorThief) {
            return; // Sale si ColorThief no está cargado o disponible
        }

        const dominantColor = colorThief.getColor(img);
        if (!dominantColor) {
            return; // Sale si no se puede obtener el color
        }

        // Modificamos esta línea para incluir transparencia
        const color = `rgba(${dominantColor[0]}, ${dominantColor[1]}, ${dominantColor[2]}, 0.9)`;
        const gradient = `linear-gradient(${color}, rgba(0, 0, 0, 0))`; // Configura un degradado lineal desde el color dominante hasta el negro con transparencia completa
        const container = document.querySelector('.music.custom-uprofile-container');

        if (!container) {
            return; // Sale si no encuentra el contenedor
        }

        container.style.background = gradient;
    }
}