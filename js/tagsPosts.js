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


function tagsPosts() {
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

        // Limpiar el contenedor de tags antes de agregar nuevas etiquetas
        tagsContainer.innerHTML = '';

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

        // Verificar si estamos usando la estructura nueva con "es" y "en"
        const descripcion = jsonData.descripcion_ia_pro || jsonData.descripcion_ia;

        if (descripcion) {
            // Agregar instrumentos posibles (nueva estructura)
            if (jsonData.instrumentos_posibles && jsonData.instrumentos_posibles["es"]) {
                allTags = allTags.concat(jsonData.instrumentos_posibles["es"].map(capitalize));
            } else if (descripcion["Instrumentos posibles"]) { // Estructura vieja
                allTags = allTags.concat(descripcion["Instrumentos posibles"].map(capitalize));
            }

            // Agregar estados de ánimo (nueva estructura)
            if (jsonData.estado_animo && jsonData.estado_animo["es"]) {
                allTags = allTags.concat(jsonData.estado_animo["es"].map(capitalize));
            } else if (descripcion["Estado de animo"]) { // Estructura vieja
                allTags = allTags.concat(descripcion["Estado de animo"].map(capitalize));
            }

            // Agregar géneros posibles (nueva estructura)
            if (jsonData.genero_posible && jsonData.genero_posible["es"]) {
                allTags = allTags.concat(jsonData.genero_posible["es"].map(capitalize));
            } else if (descripcion["Genero posible"]) { // Estructura vieja
                allTags = allTags.concat(descripcion["Genero posible"].map(capitalize));
            }

            // Agregar tipo de audio (nueva estructura)
            if (jsonData.tipo_audio && Array.isArray(jsonData.tipo_audio["es"])) {
                allTags = allTags.concat(jsonData.tipo_audio["es"].map(capitalize));
            } else if (descripcion["Tipo de audio"] && Array.isArray(descripcion["Tipo de audio"])) { // Estructura vieja
                allTags = allTags.concat(descripcion["Tipo de audio"].map(capitalize));
            }

            // Agregar tags posibles (nueva estructura)
            if (jsonData.tags_posibles && jsonData.tags_posibles["es"]) {
                allTags = allTags.concat(jsonData.tags_posibles["es"].map(capitalize));
            } else if (descripcion["Tags posibles"]) { // Estructura vieja
                allTags = allTags.concat(descripcion["Tags posibles"].map(capitalize));
            }
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
    limitTags();
}

function limitTags(maxVisible = 5) {
    // Selecciona todos los contenedores de tags cuyo ID comienza con "tags-"
    document.querySelectorAll('[id^="tags-"]').forEach(function(tagsContainer) {
        const tagElements = tagsContainer.querySelectorAll('.postTag');
        
        // Verifica si hay más tags de los permitidos
        if (tagElements.length > maxVisible) {
            // Oculta los tags que exceden el límite inicialmente
            tagElements.forEach(function(tag, index) {
                if (index >= maxVisible) {
                    tag.style.display = 'none';
                }
            });

            // Verifica si el botón "Ver más" ya existe para evitar duplicados
            let toggleButton = tagsContainer.querySelector('.postTagToggle');

            if (!toggleButton) {
                // Crea el elemento de toggle (inicialmente "Ver más")
                toggleButton = document.createElement('span');
                toggleButton.classList.add('postTagToggle');
                toggleButton.textContent = 'Ver más';

                // Agrega un event listener para manejar el clic
                toggleButton.addEventListener('click', function() {
                    const isCollapsed = toggleButton.textContent === 'Ver más';
                    
                    tagElements.forEach(function(tag, index) {
                        if (isCollapsed) {
                            // Mostrar todas las etiquetas
                            tag.style.display = 'inline';
                        } else {
                            // Ocultar las etiquetas que exceden el límite
                            if (index >= maxVisible) {
                                tag.style.display = 'none';
                            }
                        }
                    });

                    // Cambiar el texto del botón
                    toggleButton.textContent = isCollapsed ? 'Ver menos' : 'Ver más';
                });

                // Agrega el botón de toggle al contenedor de tags
                tagsContainer.appendChild(toggleButton);
            }
        }
    });
}





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