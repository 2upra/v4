//////////////////////////////////////////////
//ACTIVAR O DESACTIVAR LOGS
const A02 = false; // Cambia a true para activar los logs

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
        alert('Formulario en proceso de envío...');

        // Advertir al usuario antes de que intente salir de la página
        window.onbeforeunload = function () {
            return 'Hay una carga en progreso. ¿Estás seguro de que deseas salir de esta página?';
        };

        var formData = new FormData(form);

        var hiddenInput = document.getElementById('postTagsHidden');
        if (hiddenInput && hiddenInput.value) {
            formData.set('post_tags', hiddenInput.value);
            log02('Tags añadidos al FormData:', hiddenInput.value);
        }

        log02(`Se encontraron ${postAudios.length} archivos de audio.`);

        var archivoUrlKey = 'archivo_url';
        var maxRetries = 5; // Número máximo de reintentos
        var retryDelay = 1000; // Tiempo de espera entre reintentos en milisegundos (1 segundo)

        var archivoUrlKey = 'archivo_url';
        var maxRetries = 5; // Número máximo de reintentos
        var retryDelay = 1000; // Tiempo de espera entre reintentos en milisegundos (1 segundo)

        function intentarGuardarArchivoURL(reintentosRestantes) {
            if (window.formState.archivoURL) {
                log02(`Usando archivoURL para ${archivoUrlKey}`);
                formData.set(archivoUrlKey, window.formState.archivoURL);
                // Continuar con el resto del proceso
                procesarPostAudiosYImagenes();
            } else if (reintentosRestantes > 0) {
                log02(`No se encontró archivoURL, reintentando en ${retryDelay / 1000} segundos...`);
                setTimeout(function () {
                    intentarGuardarArchivoURL(reintentosRestantes - 1);
                }, retryDelay);
            } else {
                log02('No se seleccionó ningún archivoURL después de varios intentos');
                procesarPostAudiosYImagenes();
            }
        }

        function procesarPostAudiosYImagenes() {
            postAudios.forEach(function (postAudio, index) {
                var key = 'post_audio' + (index + 1);
                if (window.formState.uploadedFileUrls[index + 1]) {
                    log02(`Usando uploadedFileUrl para ${key}`);
                    formData.set(key, window.formState.uploadedFileUrls[index + 1]);
                } else if (postAudio.files && postAudio.files.length > 0) {
                    log02(`Usando postAudio.files[0] para ${key}`);
                    formData.set(key, postAudio.files[0]);
                } else {
                    log02(`No se seleccionó ningún archivo para ${key}`);
                }
            });

            if (window.formState.selectedImage) {
                log02('Usando selectedImage:', window.formState.selectedImage);
                formData.set('post_image', window.formState.selectedImage);
            } else if (postImage.files && postImage.files.length > 0) {
                log02('Usando postImage.files[0]:', postImage.files[0]);
                formData.set('post_image', postImage.files[0]);
            } else {
                log02('No se seleccionó ninguna imagen');
            }
        }

        // Iniciar el proceso con el número máximo de reintentos
        intentarGuardarArchivoURL(maxRetries);

        log02('Contenido de FormData:');
        for (let [key, value] of formData.entries()) {
            log02(key, value);
        }

        try {
            var messages = await sendFormData(formData);
            alert(messages);
            setTimeout(() => {
                window.location.href = 'https://2upra.com';
            }, 99999999);
        } catch (error) {
            alert('Error: ' + error);
            submitBtn.disabled = false; // Rehabilitar el botón en caso de error
        } finally {
            submitBtn.textContent = 'Enviar';
            // Remover el listener de beforeunload
            window.onbeforeunload = null;
        }
    }

    // Agregar nuevos event listeners
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

    form.addEventListener('submit', function (e) {
        e.preventDefault(); 

        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'proyectoForm',
                usernameReal: document.getElementById('usernameReal').value,
                number: document.getElementById('number').value,
                programmingExperience: document.getElementById('programmingExperience').value,
                reasonToJoin: document.getElementById('reasonToJoin').value,
                country: document.getElementById('country').value,
                projectAttitude: document.getElementById('projectAttitude').value,
                wordpressAttitude: document.getElementById('wordpressAttitude').value,
                projectInitiative: document.getElementById('projectInitiative').value,
                projectInitiativeOther: document.getElementById('projectInitiativeOther').value,
            })
        }).then(response => response.json())
          .then(data => {
              alert('Formulario enviado correctamente.');
              setTimeout(() => {
                  location.reload(); // Reinicia la página después de 1 segundo
              }, 1000);
          })
          .catch(error => {
              console.log(error);
          });
    });
}
