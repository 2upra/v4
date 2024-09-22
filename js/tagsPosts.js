// Función para reparar JSON malformados, escapando comillas dobles dentro de los valores de las claves
function repararJson(jsonStr) {
    // Buscar claves del JSON y evitar reemplazarlas
    return jsonStr.replace(/\"([^\":,{]+?)\"(?=:)/g, (match, p1) => {
        // Mantener las claves con comillas dobles intactas.
        return `"${p1}"`; 
    }).replace(/:(\s*?)\"((?:\\.|[^\"])*)\"(?=\s*[},])/g, (match, p1, p2) => {
        // Escapar las comillas dobles dentro de los valores que contienen JSON malformados.
        return `:${p1}"${p2.replace(/\"/g, '\\"')}"`;
    });
}

document.addEventListener("DOMContentLoaded", function() {
    // Seleccionamos todos los posts
    document.querySelectorAll('p[id-post]').forEach(function(pElement) {
        const postId = pElement.getAttribute('id-post');
        let jsonData = null;

        // Intentamos reparar el JSON malformado
        let rawJson = pElement.textContent;

        // Aplicamos la reparación del JSON
        rawJson = repararJson(rawJson);

        // Intentamos parsear el JSON reparado
        try {
            jsonData = JSON.parse(rawJson);
        } catch (e) {
            console.error(`Error al parsear el JSON para el post ${postId}:`, e);
            console.log(`Contenido del JSON malformado después de la reparación: ${rawJson}`);
            return;  // Si el JSON aún está malformado, saltamos este post
        }
        // Seleccionamos el contenedor donde se agregarán los tags
        const tagsContainer = document.getElementById('tags-' + postId);

        if (!tagsContainer) {
            console.warn(`No se encontró el contenedor de tags para el post ${postId}`);
            return;
        }

        // Agregamos los tags del JSON, si existen
        if (jsonData.tags && Array.isArray(jsonData.tags)) {
            jsonData.tags.forEach(function(tag) {
                const tagElement = document.createElement('span');
                tagElement.classList.add('tag');
                tagElement.textContent = tag;
                tagsContainer.appendChild(tagElement);
            });
        }

        // Agregar la categoría de BPM, si existe
        if (jsonData.bpm && typeof jsonData.bpm === 'number') {
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

        // Agregar la tonalidad (key) y la escala (scale), si existen
        if (jsonData.key && jsonData.scale) {
            const keyScaleElement = document.createElement('span');
            keyScaleElement.classList.add('tag');
            keyScaleElement.textContent = jsonData.key + ' ' + jsonData.scale;
            tagsContainer.appendChild(keyScaleElement);
        }
    });
});