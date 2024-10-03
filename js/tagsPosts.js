function repararJson(jsonString) {
    try {
        // Intenta corregir el JSON anidado escapando comillas en los valores de propiedades mal formadas
        jsonString = jsonString.replace(/"descripcion_ia":"({.*?})"/g, function(match, p1) {
            return `"descripcion_ia":"${p1.replace(/"/g, '\\"')}"`;
        });

        return JSON.parse(jsonString);
    } catch (e) {
        return null;
    }
}

function capitalize(word) {
    return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
}

function removeDuplicates(arr) {
    return [...new Set(arr)];
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

        // Crear un array para almacenar todas las etiquetas unificadas
        let allTags = [];

        // Recopilar todos los tags existentes en el JSON
        if (jsonData.tags && Array.isArray(jsonData.tags)) {
            allTags = allTags.concat(jsonData.tags.map(tag => capitalize(tag)));
        }

        // Categoría de BPM
        if (jsonData.bpm && typeof jsonData.bpm === 'number') {
            let bpmCategory = '';
            if (jsonData.bpm < 90) bpmCategory = 'Lento';
            else if (jsonData.bpm >= 90 && jsonData.bpm < 120) bpmCategory = 'Moderado';
            else if (jsonData.bpm >= 120 && jsonData.bpm < 150) bpmCategory = 'Rápido';
            else if (jsonData.bpm >= 150) bpmCategory = 'Muy Rápido';

            allTags.push(bpmCategory + ' (' + jsonData.bpm + ' BPM)');
        }

        // Tonalidad y escala
        if (jsonData.key && jsonData.scale) {
            allTags.push(capitalize(jsonData.key) + ' ' + capitalize(jsonData.scale));
        }

        // Agregar instrumentos posibles
        if (jsonData.descripcion_ia && jsonData.descripcion_ia["Instrumentos posibles"]) {
            allTags = allTags.concat(jsonData.descripcion_ia["Instrumentos posibles"].map(capitalize));
        }

        // Agregar estados de ánimo
        if (jsonData.descripcion_ia && jsonData.descripcion_ia["Estado de animo"]) {
            allTags = allTags.concat(jsonData.descripcion_ia["Estado de animo"].map(capitalize));
        }

        // Agregar géneros posibles
        if (jsonData.descripcion_ia && jsonData.descripcion_ia["Genero posible"]) {
            allTags = allTags.concat(jsonData.descripcion_ia["Genero posible"].map(capitalize));
        }

        // Agregar tipo de audio
        if (jsonData.descripcion_ia && jsonData.descripcion_ia["Tipo de audio"]) {
            allTags = allTags.concat(jsonData.descripcion_ia["Tipo de audio"].map(capitalize));
        }

        // Agregar tags posibles
        if (jsonData.descripcion_ia && jsonData.descripcion_ia["Tags posibles"]) {
            allTags = allTags.concat(jsonData.descripcion_ia["Tags posibles"].map(capitalize));
        }

        // Eliminar duplicados globalmente y agregar al contenedor de tags
        let uniqueTags = removeDuplicates(allTags);
        uniqueTags.forEach(function(tag) {
            const tagElement = document.createElement('span');
            tagElement.classList.add('postTag');
            tagElement.textContent = tag;
            tagsContainer.appendChild(tagElement);
        });
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