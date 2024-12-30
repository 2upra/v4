/*
porque este formato no funciona

{"tags":["lofi","test"],"texto":"Lofi drum loop","autor":{"id":"1","usuario":"1ndoryu","nombre":"Wandorius"},"bpm":150,"emotion":"","key":"Ab","scale":"major","descripcion_ia":{"es":"Loop de batería lofi con un ritmo relajado y repetitivo, ideal para la creación de beats hip hop o música electrónica con una estética vintage.  Su sonido es cálido y orgánico, con un groove sutil que invita a la relajación.  Perfecto para proyectos chillhop, lofi hip hop, o cualquier género que requiera un beat suave y atmosférico.","en":"Lofi drum loop with a relaxed and repetitive rhythm, ideal for creating hip hop beats or electronic music with a vintage aesthetic. Its sound is warm and organic, with a subtle groove that invites relaxation. Perfect for chillhop, lofi hip hop, or any genre that requires a smooth and atmospheric beat."},"instrumentos_posibles":{"es":[],"en":[]},"estado_animo":{"es":["Relajado","Tranquilo"],"en":["Relaxed","Calm"]},"artista_posible":{"es":["Nujabes","J Dilla","MF DOOM"],"en":["Nujabes","J Dilla","MF DOOM"]},"genero_posible":{"es":["Lofi hip hop","Chillhop","Hip hop instrumental"],"en":["Lofi hip hop","Chillhop","Hip hop instrumental"]},"tipo_audio":{"es":["Loop"],"en":["Loop"]},"tags_posibles":{"es":["Lofi","Hip Hop","Chillhop","Loop","Drums","Batería","Suave","Relax","Vintage","Warm"],"en":["Lofi","Hip Hop","Chillhop","Loop","Drums","Smooth","Relax","Vintage","Warm"]},"sugerencia_busqueda":{"es":["Beat lofi relajante","Loop de batería lofi","Música lofi para relajarse"],"en":["Relaxing lofi beat","Lofi drum loop","Relaxing lofi music"]}}

necesito que funcione sin dañar el resto de cosas
*/

//LA FORMA EN QUE ESTO PROCESA LAS COSA NO DEBE ALTERARSE PORQUE SOPORTA DIFERENTES FORMATOS, SOLO HACERLO MAS FLEXIBLE
function parseJson(jsonString) {
    try {
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

        const jsonText = pElement.textContent.trim();
        const jsonData = parseJson(jsonText);
        if (!jsonData) {
            console.error(`Error al parsear el JSON para el post ${postId}`);
            return;
        }

        tagsContainer.innerHTML = '';
        let allTags = [];

        const addTags = (source, key) => {
            ['es', 'en'].forEach(lang => {
                if (source?.[key]?.[lang]) {
                    allTags = allTags.concat(source[key][lang].map(capitalize));
                }
            });
        };

        // Agregar tags
        addTags(jsonData, 'tipo_audio');
        addTags(jsonData, 'instrumentos_posibles');
        addTags(jsonData, 'genero_posible');
        addTags(jsonData, 'estado_animo');
        addTags(jsonData, 'artista_posible');
        addTags(jsonData, 'tags_posibles');

        // Procesar BPM
        if (jsonData.bpm) {
            const bpmCategory = jsonData.bpm < 90 ? 'Lento' : jsonData.bpm < 120 ? 'Moderado' : jsonData.bpm < 150 ? 'Rápido' : 'Muy Rápido';
            allTags.push(`${bpmCategory} (${jsonData.bpm} BPM)`);
        }

        // Procesar tonalidad y escala
        if (jsonData.key && jsonData.scale) {
            allTags.push(`${capitalize(jsonData.key)} ${capitalize(jsonData.scale)}`);
        }

        // Eliminar duplicados y añadir tags al DOM
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
    const maxMobile = 2;
    const screenLimit = 640;

    document.querySelectorAll('[id^="tags-"]').forEach(tagsCont => {
        const tags = tagsCont.querySelectorAll('.postTag');
        const isMobile = window.innerWidth < screenLimit;
        const maxToShow = isMobile ? maxMobile : maxVisible;

        if (tags.length > maxToShow) {
            tags.forEach((tag, index) => {
                if (index >= maxToShow) {
                    tag.style.display = 'none';
                }
            });

            let btn = tagsCont.querySelector('.postTagToggle');

            if (!btn) {
                btn = document.createElement('span');
                btn.classList.add('postTagToggle');
                btn.textContent = 'Ver más';

                btn.addEventListener('click', () => {
                    const isCollapsed = btn.textContent === 'Ver más';

                    tags.forEach((tag, index) => {
                        if (isCollapsed) {
                            tag.style.display = 'inline';
                        } else {
                            if (index >= maxToShow) {
                                tag.style.display = 'none';
                            }
                        }
                    });

                    btn.textContent = isCollapsed ? 'Ver menos' : 'Ver más';
                });

                tagsCont.appendChild(btn);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Obtener el elemento que contiene el JSON
    const dataElement = document.getElementById('dataColec');
    if (!dataElement) {
        //console.error("Elemento con id 'dataColec' no encontrado.");
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
