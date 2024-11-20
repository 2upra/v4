function repararJson(jsonString) {
    try {
        // Escapar comillas dentro de cualquier propiedad que tenga un objeto como valor
        jsonString = jsonString.replace(/"([^"]+?)":\s*?"({.*?})"/g, function (match, p1, p2) {
            return `"${p1}":"${p2.replace(/"/g, '\\"')}"`;
        });

        // Intentar parsear el JSON reparado
        return JSON.parse(jsonString);
    } catch (e) {
        console.error('Error al parsear el JSON:', e.message);
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
    document.querySelectorAll('p[id-post-algoritmo]').forEach(function (pElement) {
        const postId = pElement.getAttribute('id-post-algoritmo');
        const tagsContainer = document.getElementById('tags-' + postId);

        if (!tagsContainer) {
            console.warn(`No se encontró el contenedor de tags para el post ${postId}`);
            return;
        }

        const jsonData = repararJson(pElement.textContent);
        if (!jsonData) {
            console.error(`Error al parsear el JSON para el post ${postId}`);
            return;
        }

        tagsContainer.innerHTML = '';
        let allTags = [];

        // Función auxiliar para agregar tags desde una fuente específica
        const addTags = (source, key) => {
            if (source?.[key]?.['en']) {
                allTags = allTags.concat(source[key]['en'].map(capitalize));
            } else if (source?.[key]) {
                allTags = allTags.concat(source[key].map(capitalize));
            }
        };

        // Detectar estructura
        const isNewStructure = !!jsonData.instrumentos_principal?.['es'];

        // Primero agregar tipo de audio
        addTags(jsonData, 'tipo_audio');

        // Agregar instrumentos
        if (isNewStructure) {
            addTags(jsonData, 'instrumentos_principal');
        } else {
            addTags(jsonData, 'Instrumentos posibles');
        }

        // Agregar género
        if (isNewStructure) {
            addTags(jsonData, 'genero_posible');
        } else {
            addTags(jsonData, 'Genero posible');
        }

        // Agregar categoría BPM
        if (jsonData.bpm) {
            const bpmCategory = jsonData.bpm < 90 ? 'Lento' : jsonData.bpm < 120 ? 'Moderado' : jsonData.bpm < 150 ? 'Rápido' : 'Muy Rápido';
            allTags.push(`${bpmCategory} (${jsonData.bpm} BPM)`);
        }

        // Agregar tonalidad y escala
        if (jsonData.key && jsonData.scale) {
            allTags.push(`${capitalize(jsonData.key)} ${capitalize(jsonData.scale)}`);
        }

        // Agregar las categorías restantes
        const remainingCategories = isNewStructure ? ['estado_animo', 'artista_posible', 'tags_posibles'] : ['Estado de animo', 'Artista posible', 'Tags posibles'];

        remainingCategories.forEach(category => {
            addTags(jsonData, category);
        });

        // Crear y agregar tags únicos al contenedor
        removeDuplicates(allTags).forEach(tag => {
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
    document.querySelectorAll('[id^="tags-"]').forEach(function (tagsContainer) {
        const tagElements = tagsContainer.querySelectorAll('.postTag');

        // Verifica si hay más tags de los permitidos
        if (tagElements.length > maxVisible) {
            // Oculta los tags que exceden el límite inicialmente
            tagElements.forEach(function (tag, index) {
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
                toggleButton.addEventListener('click', function () {
                    const isCollapsed = toggleButton.textContent === 'Ver más';

                    tagElements.forEach(function (tag, index) {
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
document.addEventListener('DOMContentLoaded', function () {
    // Obtener el elemento que contiene el JSON
    const dataElement = document.getElementById('dataColec');
    if (!dataElement) {
        console.error("Elemento con id 'dataColec' no encontrado.");
        return;
    }

    // Obtener el contenido JSON y parsearlo
    const jsonData = dataElement.textContent || dataElement.innerText;
    let data;
    try {
        data = JSON.parse(jsonData);
    } catch (e) {
        console.error('Error al parsear el JSON:', e);
        return;
    }

    // Obtener el ID del post desde el atributo personalizado
    const postId = dataElement.getAttribute('id-post-algoritmo');
    if (!postId) {
        console.error("Atributo 'id-post-algoritmo' no encontrado.");
        return;
    }

    // Obtener el contenedor donde se insertarán los tags
    const container = document.getElementById('tags-' + postId);
    if (!container) {
        console.error(`Contenedor con id 'tags-${postId}' no encontrado.`);
        return;
    }

    // Función para obtener los dos tags principales de una categoría, ignorando 'descripcion_corta'
    function getTopTwoTags(tagsObj) {
        return Object.entries(tagsObj)
            .filter(([key, _]) => key !== 'descripcion_corta') // Ignorar 'descripcion_corta'
            .sort((a, b) => b[1] - a[1]) // Ordenar de mayor a menor según el valor
            .slice(0, 2) // Tomar los dos primeros
            .map(entry => entry[0]); // Obtener solo los nombres de los tags
    }

    // Crear un conjunto para almacenar tags ya agregados y evitar duplicados
    const addedTags = new Set();

    // Recorrer cada categoría en el JSON
    for (const categoria in data) {
        if (data.hasOwnProperty(categoria)) {
            const tags = data[categoria];
            const topTags = getTopTwoTags(tags);

            topTags.forEach(tag => {
                // Verificar si el tag ya ha sido agregado
                if (!addedTags.has(tag)) {
                    // Crear un elemento <span> con la clase 'postTag'
                    const span = document.createElement('span');
                    span.className = 'postTag';
                    span.textContent = tag;

                    // Opcional: Agregar un separador o espacio
                    // span.style.marginRight = '5px';

                    // Insertar el <span> en el contenedor
                    container.appendChild(span);

                    // Añadir el tag al conjunto para evitar duplicados futuros
                    addedTags.add(tag);
                }
            });
        }
    }
});
