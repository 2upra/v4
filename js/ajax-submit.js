const A02 = true;
const log02 = A02 ? console.log : function () {};

window.getPostAudios = function () {
    const postAudios = [];
    for (let i = 1; i <= 20; i++) {
        const postAudio = document.getElementById(`postAudio${i}`);
        if (postAudio) {
            postAudios.push(postAudio);
        }
    }
    return postAudios;
};

window.getfile = function () {
    const fileInput = document.getElementById('flp');
    return fileInput?.files?.length > 0 ? fileInput.files : null;
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
    function removeExistingListeners() {
        form.removeEventListener('submit', handleSubmit);
    }
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

        const paginaActual = document.getElementById('pagina_actual')?.value || '';
        const isSample = paginaActual === 'subir sample';
        const isPost = paginaActual === '2upra Records';

        const mensajesError = verificarCampos(isPost, isSample);

        if (mensajesError.length > 0) {
            alert('Por favor, complete los siguientes pasos antes de continuar:\n\n' + mensajesError.join('\n'));
            return;
        }

        window.procesarTagsSiExisten();
        window.procesarTagsSiExistenRs();

        const postAudios = window.getPostAudios();
        const formData = new FormData(form);

        const hiddenTags = document.getElementById('postTagsHidden')?.value;
        if (hiddenTags) formData.set('post_tags', hiddenTags);

        postAudios.forEach((postAudio, index) => {
            const key = `post_audio${index + 1}`;
            const uploadedUrl = window.formState.uploadedFileUrls[index + 1];

            if (uploadedUrl) {
                formData.set(key, uploadedUrl);
            } else if (postAudio.files?.length > 0) {
                formData.set(key, postAudio.files[0]);
            } else {
                log02(`No se encontró URL cargada ni archivo para ${key}`);
            }
        });

        const selectedImage = window.formState.selectedImage || postImage.files?.[0];
        if (selectedImage) formData.set('post_image', selectedImage);

        log02('Contenido de FormData:');
        for (let [key, value] of formData.entries()) {
            log02(key, value);
        }

        submitBtn.textContent = 'Enviando...';
        submitBtn.disabled = true;
        window.onbeforeunload = () => 'Hay una carga en progreso. ¿Estás seguro de que deseas salir de esta página?';

        try {
            const messages = await sendFormData(formData);
            alert(messages);
            setTimeout(() => (window.location.href = 'https://2upra.com'), 99999999);
        } catch (error) {
            alert('Error: ' + error);
            submitBtn.disabled = false;
        } finally {
            submitBtn.textContent = 'Enviar';
            window.onbeforeunload = null;
        }
    }

    function verificarCampos(isPost, isSample) {
        const mensajesError = [];

        const verificar = (condicion, mensaje) => {
            if (!condicion) mensajesError.push(mensaje);
        };

        if (isPost) {
            verificar(window.formState.postCampos, window.formState.postErrorMessage || '- Por favor verifica todos los campos del post');
            verificar(todosArchivosSubidos(window.formState.uploadedFiles), '- Esperar a que se complete la carga de los archivos');
            verificar(window.formState.archivo, '- Espera que se cargue el archivo');
        } else {
            verificar(isSample ? window.formState.sampleCampos : window.formState.camposRellenos, '- Rellenar todos los campos');
            verificar(window.formState.isAudioUploaded, '- Subir un archivo de audio');
            verificar(window.formState.archivo, '- Espera que se cargue el archivo');
            if (!isSample) verificar(window.formState.isImageUploaded, '- Subir una imagen');
            verificar(todosArchivosSubidos(window.formState.uploadedFiles), '- Esperar a que se complete la carga de los archivos');
        }

        return mensajesError;
    }

    form.addEventListener('submit', handleSubmit);
}

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
