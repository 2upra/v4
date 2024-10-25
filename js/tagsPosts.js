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
    document.querySelectorAll('p[id-post-algoritmo]').forEach(pElement => {
        const postId = pElement.getAttribute('id-post-algoritmo');
        const rawJson = pElement.textContent;
        const tagsContainer = document.getElementById('tags-' + postId);
        let jsonData = repararJson(rawJson);

        if (!jsonData) {
            console.error(`Error al parsear el JSON para el post ${postId}.`);
            console.log(`Contenido del JSON malformado después de la reparación: ${rawJson}`);
            return;
        }

        if (!tagsContainer) {
            console.warn(`No se encontró el contenedor de tags para el post ${postId}`);
            return;
        }

        tagsContainer.innerHTML = '';  // Limpiar el contenedor de tags

        let allTags = [];

        // Helper para manejar etiquetas y comprobar estructura
        const addTags = (data, field, fallbackField) => {
            if (data && data[field]) {
                allTags.push(...data[field].map(capitalize));
            } else if (fallbackField && data[fallbackField]) {
                allTags.push(...data[fallbackField].map(capitalize));
            }
        };

        // Agregar BPM
        if (jsonData.bpm && typeof jsonData.bpm === 'number') {
            let bpmCategory = '';
            if (jsonData.bpm < 90) bpmCategory = 'Lento';
            else if (jsonData.bpm < 120) bpmCategory = 'Moderado';
            else if (jsonData.bpm < 150) bpmCategory = 'Rápido';
            else bpmCategory = 'Muy Rápido';

            allTags.push(`${bpmCategory} (${jsonData.bpm} BPM)`);
        }

        // Agregar tonalidad y escala
        if (jsonData.key && jsonData.scale) {
            allTags.push(`${capitalize(jsonData.key)} ${capitalize(jsonData.scale)}`);
        }

        // Añadir tags variados según disponibilidad de campo en ambas estructuras
        const descripcion = jsonData.descripcion_ia_pro || jsonData.descripcion_ia;
        if (descripcion) {
            addTags(jsonData, 'instrumentos_posibles', 'Instrumentos posibles');
            addTags(jsonData, 'estado_animo', 'Estado de animo');
            addTags(jsonData, 'genero_posible', 'Genero posible');
            addTags(jsonData, 'tipo_audio', 'Tipo de audio');
            addTags(jsonData, 'tags_posibles', 'Tags posibles');
        }

        // Eliminar duplicados y crear etiquetas
        const uniqueTags = removeDuplicates(allTags);
        uniqueTags.forEach(tag => {
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