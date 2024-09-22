document.addEventListener("DOMContentLoaded", function() {
    // Seleccionamos todos los posts
    document.querySelectorAll('p[id-post]').forEach(function(pElement) {
        const postId = pElement.getAttribute('id-post');
        const jsonData = JSON.parse(pElement.textContent); // Convertimos el JSON

        // Seleccionamos el contenedor donde se agregarán los tags
        const tagsContainer = document.getElementById('tags-' + postId);

        // Agregamos los tags del JSON
        if (jsonData.tags && jsonData.tags.length > 0) {
            jsonData.tags.forEach(function(tag) {
                const tagElement = document.createElement('span');
                tagElement.classList.add('tag');
                tagElement.textContent = tag;
                tagsContainer.appendChild(tagElement);
            });
        }

        // Agregar la categoría de BPM
        if (jsonData.bpm) {
            let bpmCategory = '';
            if (jsonData.bpm < 90) {
                bpmCategory = 'Lento';
            } else if (jsonData.bpm >= 90 && jsonData.bpm < 120) {
                bpmCategory = 'Moderado';
            } else if (jsonData.bpm >= 120 && jsonData.bpm < 150) {
                bpmCategory = 'Rápido';
            } else if (jsonData.bpm >= 150) {
                bpmCategory = 'Muy Rápido';
            }

            if (bpmCategory) {
                const bpmElement = document.createElement('span');
                bpmElement.classList.add('tag');
                bpmElement.textContent = bpmCategory + ' (' + jsonData.bpm + ' BPM)';
                tagsContainer.appendChild(bpmElement);
            }
        }

        // Agregar la tonalidad (key) y la escala (scale)
        if (jsonData.key && jsonData.scale) {
            const keyScaleElement = document.createElement('span');
            keyScaleElement.classList.add('tag');
            keyScaleElement.textContent = jsonData.key + ' ' + jsonData.scale;
            tagsContainer.appendChild(keyScaleElement);
        }
    });
});