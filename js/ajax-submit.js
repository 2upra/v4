//////////////////////////////////////////////
//ACTIVAR O DESACTIVAR LOGS
const A02 = true; // Cambia a true para activar los logs

const log02 = A02 ? console.log : function () {};
//////////////////////////////////////////////

window.getPostAudios = function () {
    var postAudios = [];
    for (var i = 1; i <= 20; i++) {
        var postAudio = document.getElementById('postAudio' + i);
        if (postAudio) {
            postAudios.push(postAudio);
        }
    }
    return postAudios;
};

window.getfile = function () {
    var fileInput = document.getElementById('flp');
    if (fileInput && fileInput.files && fileInput.files.length > 0) {
        return fileInput.files; // Devuelve el FileList directamente
    }
    return null; // Devuelve null si no hay archivos seleccionados
};

function forms_submit(form, submitBtnId) {
    var submitBtn = document.getElementById(submitBtnId);
    var postImage = document.getElementById('postImage');
    var form = document.getElementById(form);

    log02('submitBtn:', submitBtn);
    log02('postImage:', postImage);
    log02('form:', form);

    if (!form || !submitBtn || !postImage) {
        log02('One or more elements not found.');
        return;
    }

    // Función para limpiar los event listeners existentes
    function removeExistingListeners() {
        form.removeEventListener('submit', handleSubmit);
    }

    // Llamar a la función para limpiar los listeners existentes
    removeExistingListeners();

    async function sendFormData(formData) {
        return new Promise((resolve, reject) => {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', my_ajax_object.ajax_url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resolve(response.message);
                        } else {
                            reject('Formulario enviado, pero el servidor respondió con un error: ' + (response.message || 'Sin mensaje de error'));
                        }
                    } catch (e) {
                        console.error('Failed to parse server response:', xhr.responseText);
                        reject('Error al procesar la respuesta del servidor. Por favor, inténtelo de nuevo o contacte al administrador.');
                    }
                } else {
                    reject('Error en la solicitud: ' + xhr.statusText);
                }
            };

            xhr.onerror = function () {
                reject('Error en la red o en la solicitud.');
            };

            xhr.send(formData);
        });
    }

    function todosArchivosSubidos(uploadedFiles) {
        const start = uploadedFiles[0] === undefined ? 1 : 0;
        return uploadedFiles.slice(start).every(Boolean);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        // Verificar la URL actual}

        const paginaActualElement = document.getElementById('pagina_actual');
        const isSample = paginaActualElement && paginaActualElement.value === 'subir sample';
        const isPost = paginaActualElement && paginaActualElement.value === '2upra Records';

        let mensajesError = [];

        if (isPost) {
            if (!window.formState.postCampos) {
                const errorMessage = window.formState.postErrorMessage || '- Por favor verifica todos los campos del post';
                alert(errorMessage);
            }
            if (!todosArchivosSubidos(window.formState.uploadedFiles)) {
                mensajesError.push('- Esperar a que se complete la carga de los archivos');
            }

            if (!window.formState.archivo) {
                mensajesError.push('- Espera que se cargue el archivo');
            }

            if (mensajesError.length > 0) {
                let mensaje = 'Por favor, complete los siguientes pasos antes de continuar:\n\n';
                mensaje += mensajesError.join('\n');
                alert(mensaje);
                return;
            }
        } else {
            if (isSample && !window.formState.sampleCampos) {
                mensajesError.push('- Rellenar todos los campos');
            }

            if (!window.formState.isAudioUploaded) {
                mensajesError.push('- Subir un archivo de audio');
            }

            if (!window.formState.archivo) {
                mensajesError.push('- Espera que se cargue el archivo');
            }

            if (!isSample && !window.formState.isImageUploaded) {
                mensajesError.push('- Subir una imagen');
            }

            if (!isSample && !window.formState.camposRellenos) {
                mensajesError.push('- Rellenar todos los campos del formulario');
            }

            if (!todosArchivosSubidos(window.formState.uploadedFiles)) {
                mensajesError.push('- Esperar a que se complete la carga de los archivos');
            }

            if (mensajesError.length > 0) {
                let mensaje = 'Por favor, complete los siguientes pasos antes de continuar:\n\n';
                mensaje += mensajesError.join('\n');
                alert(mensaje);
                return;
            }
        }

        window.procesarTagsSiExisten();
        window.procesarTagsSiExistenRs();

        var postAudios = window.getPostAudios();
        var fileRs = window.getfile();
        submitBtn.textContent = 'Enviando...';
        submitBtn.disabled = true; // Deshabilitar el botón para evitar múltiples envíos

        // Advertir al usuario antes de salir de la página durante la carga
        window.onbeforeunload = () => 'Hay una carga en progreso. ¿Estás seguro de que deseas salir de esta página?';

        var formData = new FormData(form);

        // Añadir post_tags si existe
        var hiddenInput = document.getElementById('postTagsHidden');
        if (hiddenInput?.value) {
            formData.set('post_tags', hiddenInput.value);
            log02('Tags añadidos al FormData:', hiddenInput.value);
        }

        // Guardar archivoURL o reintentar
        function intentarGuardarArchivoURL(reintentosRestantes = 5, delay = 1000) {
            if (window.formState.archivoURL) {
                formData.set('archivo_url', window.formState.archivoURL);
                procesarPostAudiosYImagenes();
            } else if (reintentosRestantes > 0) {
                setTimeout(() => intentarGuardarArchivoURL(reintentosRestantes - 1), delay);
            } else {
                log02('No se seleccionó ningún archivoURL después de varios intentos');
                procesarPostAudiosYImagenes();
            }
        }

        // Procesar audios e imágenes
        function procesarPostAudiosYImagenes() {
            postAudios.forEach((postAudio, index) => {
                var key = `post_audio${index + 1}`;
                var audioURL = window.formState.uploadedFileUrls[index + 1];
                if (audioURL) {
                    formData.set(key, audioURL);
                } else if (postAudio?.files?.length > 0) {
                    formData.set(key, postAudio.files[0]);
                }
            });

            if (window.formState.selectedImage) {
                formData.set('post_image', window.formState.selectedImage);
            } else if (postImage?.files?.length > 0) {
                formData.set('post_image', postImage.files[0]);
            }
        }

        // Iniciar el proceso
        intentarGuardarArchivoURL();

        // Log del FormData
        for (let [key, value] of formData.entries()) {
            log02(key, value);
        }

        try {
            var messages = await sendFormData(formData);
            alert(messages);
            setTimeout(() => (window.location.href = 'https://2upra.com'), 99999999); // Evitar cierre en desarrollo
        } catch (error) {
            alert('Error: ' + error);
            submitBtn.disabled = false;
        } finally {
            submitBtn.textContent = 'Enviar';
            window.onbeforeunload = null; // Remover el listener
        }
    }
    // Event listener para el submit
    form.addEventListener('submit', handleSubmit);
}
//
function ajax_submit() {
    var formsAndButtons = [
        {formId: 'postFormRola', btnId: 'submitBtn'},
        {formId: 'postFormRs', btnId: 'submitBtnRs'},
        {formId: 'postFormSample', btnId: 'submitBtnSample'}
    ];

    formsAndButtons.forEach(function (item) {
        var form = document.getElementById(item.formId);
        var btn = document.getElementById(item.btnId);

        if (form && btn && document.body.contains(form) && document.body.contains(btn)) {
            log02('Executing forms_submit for:', item.formId, item.btnId);
            forms_submit(item.formId, item.btnId);
        } else {
            log02('Element(s) not found or not in DOM for:', item.formId, item.btnId);
        }
    });
}

function proyectoForm() {
    const form = document.getElementById('proyectoUnirte');

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = {
            action: 'proyectoForm',
            usernameReal: document.getElementById('usernameReal')?.value || '',
            number: document.getElementById('number')?.value || '',
            programmingExperience: document.getElementById('programmingExperience')?.value || '',
            reasonToJoin: document.getElementById('reasonToJoin')?.value || '',
            country: document.getElementById('country')?.value || '',
            projectAttitude: document.getElementById('projectAttitude')?.value || '',
            wordpressAttitude: document.getElementById('wordpressAttitude')?.value || '',
            projectInitiative: document.getElementById('projectInitiative')?.value || '',
            projectInitiativeOther: document.getElementById('projectInitiativeOther')?.value || ''
        };

        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
            .then(response => response.json())
            .then(data => {
                alert('Formulario enviado correctamente.');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            })
            .catch(error => {
                console.error('Error al enviar el formulario:', error);
            });
    });
}
