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

/*
porque falla con esta estructura, el proposito es hacer el codigo funcione en todos los casos, este es un caso donde no ha funcionado, no dañe la logica actual
{"bpm":"","emotion":"","key":"","scale":"","descripcion_ia":{"es":"Cuenta regresiva de audio "Countdown 03" que incluye los números 'one, two, three, four',  perteneciente a un kit de samples de hip hop.  Ideal para introducir una canción o sección musical.","en":"Audio countdown "Countdown 03" featuring the numbers 'one, two, three, four', from a hip hop sample kit. Ideal for introducing a song or musical section."},"instrumentos_principal":{"es":["Voz"],"en":["Voice"]},"nombre_corto":{"es":["Countdown 03"],"en":["Countdown 03"]},"descripcion_corta":{"es":"Cuenta regresiva: sample hip hop","en":"Countdown sample: hip hop"},"estado_animo":{"es":["Enérgico"],"en":["Energetic"]},"artista_posible":{"es":[],"en":[]},"genero_posible":{"es":["Hip hop"],"en":["Hip hop"]},"tipo_audio":{"es":["sample"],"en":["sample"]},"tags_posibles":{"es":["Countdown","Sample","HipHop","Intro"],"en":["Countdown","Sample","HipHop","Intro"]},"sugerencia_busqueda":{"es":["Sample cuenta regresiva","Intro hip hop","Sample hip hop gratis","Countdown audio"],"en":["Countdown sample","Hip hop intro","Free hip hop sample","Audio countdown"]}}
*/


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
            if (source?.[key]?.["en"]) {
                allTags = allTags.concat(source[key]["en"].map(capitalize));
            } else if (source?.[key]) {
                allTags = allTags.concat(source[key].map(capitalize));
            }
        };

        // Primero agregar tipo de audio
        addTags(jsonData, 'tipo_audio');

        // Agregar categoría BPM
        if (jsonData.bpm) {
            const bpmCategory = jsonData.bpm < 90 ? 'Lento' :
                              jsonData.bpm < 120 ? 'Moderado' :
                              jsonData.bpm < 150 ? 'Rápido' : 'Muy Rápido';
            allTags.push(`${bpmCategory} (${jsonData.bpm} BPM)`);
        }

        // Agregar tonalidad y escala
        if (jsonData.key && jsonData.scale) {
            allTags.push(`${capitalize(jsonData.key)} ${capitalize(jsonData.scale)}`);
        }

        // Detectar estructura y agregar etiquetas adicionales
        const isNewStructure = !!jsonData.instrumentos_principal?.["es"];
        const tagCategories = isNewStructure ? [
            'instrumentos_principal',
            'estado_animo',
            'genero_posible',
            'artista_posible',
            'tags_posibles'
        ] : [
            'Instrumentos posibles',
            'Estado de animo',
            'Genero posible',
            'Artista posible',
            'Tags posibles'
        ];

        tagCategories.forEach(category => {
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