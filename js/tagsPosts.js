// Función para intentar reparar JSON
function repararJson(jsonString) {
    try {
        // Reemplaza comas antes de corchetes cerrados o corchetes abiertos mal colocados
        jsonString = jsonString
            .replace(/,\s*([}\]])/g, '$1')  // Eliminar comas finales antes de } o ]
            .replace(/([{,]\s*)['"]?([a-zA-Z0-9_]+)['"]?\s*:/g, '$1"$2":')  // Asegurar que todas las claves estén entre comillas dobles
            .replace(/['"]?([a-zA-Z0-9_]+)['"]?\s*:/g, '"$1":');  // Asegurar claves con comillas

        // Intenta devolver el JSON ya parseado
        return JSON.parse(jsonString);
    } catch (e) {
        // Si hay un error, devolver el string original para su análisis
        return null;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('p[id-post-algoritmo]').forEach(function(pElement) {
        const postId = pElement.getAttribute('id-post-algoritmo');
        let jsonData = null;
        let rawJson = pElement.textContent;

        // Aplicar la función para intentar reparar el JSON
        jsonData = repararJson(rawJson);

        if (!jsonData) {
            console.error(`Error al parsear el JSON para el post ${postId}.`);
            console.log(`Contenido del JSON malformado después de la reparación: ${rawJson}`);
            return;
        }

        const tagsContainer = document.getElementById('tags-' + postId);

        if (!tagsContainer) {
            console.warn(`No se encontró el contenedor de tags para el post ${postId}`);
            return;
        }

        // Agregar los tags si existen
        if (jsonData.tags && Array.isArray(jsonData.tags)) {
            jsonData.tags.forEach(function(tag) {
                const tagElement = document.createElement('span');
                tagElement.classList.add('tag');
                tagElement.textContent = tag;
                tagsContainer.appendChild(tagElement);
            });
        }

        // Categoría de BPM
        if (jsonData.bpm && typeof jsonData.bpm === 'number') {
            let bpmCategory = '';
            if (jsonData.bpm < 90) bpmCategory = 'Lento';
            else if (jsonData.bpm >= 90 && jsonData.bpm < 120) bpmCategory = 'Moderado';
            else if (jsonData.bpm >= 120 && jsonData.bpm < 150) bpmCategory = 'Rápido';
            else if (jsonData.bpm >= 150) bpmCategory = 'Muy Rápido';

            const bpmElement = document.createElement('span');
            bpmElement.classList.add('tag');
            bpmElement.textContent = bpmCategory + ' (' + jsonData.bpm + ' BPM)';
            tagsContainer.appendChild(bpmElement);
        }

        // Tonalidad y escala
        if (jsonData.key && jsonData.scale) {
            const keyScaleElement = document.createElement('span');
            keyScaleElement.classList.add('tag');
            keyScaleElement.textContent = jsonData.key + ' ' + jsonData.scale;
            tagsContainer.appendChild(keyScaleElement);
        }
    });
});


function modalDetallesIA() {
    const modal = document.getElementById('modalDetallesIA');
    const modalBackground = document.getElementById('backgroundDetallesIA');
    const modalContent = document.getElementById('modalDetallesContent');

    document.querySelectorAll('.infoIA-btn').forEach(button => {
        button.addEventListener('click', function () {
            const postId = this.getAttribute('data-post-id');
            const postDetalles = document.querySelector(`p[id-post-detalles-ia="${postId}"]`);

            if (postDetalles) {
                let detallesIA;

                try {
                    detallesIA = JSON.parse(postDetalles.textContent);
                } catch (e) {
                    console.error('Error al parsear el JSON:', e);
                    modalContent.textContent = "Error al mostrar los detalles.";
                    return;
                }

                modalContent.innerHTML = '';

                function mostrarDetalles(obj, parentElement) {
                    for (let key in obj) {
                        if (obj.hasOwnProperty(key)) {
                            let value = obj[key];
                            let detailElement = document.createElement('p');

                            if (Array.isArray(value)) {
                                detailElement.innerHTML = `<strong>${key}:</strong> ${value.join(', ')}`;
                            } else if (typeof value === 'object' && value !== null) {
                                let subContainer = document.createElement('div');
                                subContainer.innerHTML = `<strong>${key}:</strong>`;
                                mostrarDetalles(value, subContainer);
                                parentElement.appendChild(subContainer);
                                continue;
                            } else {
                                detailElement.innerHTML = `<strong>${key}:</strong> ${value}`;
                            }

                            parentElement.appendChild(detailElement);
                        }
                    }
                }

                mostrarDetalles(detallesIA, modalContent);

                modal.style.display = 'block';
                modalBackground.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    modalBackground.addEventListener('click', function () {
        modal.style.display = 'none';
        modalBackground.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
}