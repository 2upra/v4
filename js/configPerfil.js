function SubidaImagenPerfil() {
    const previewAreaImagen = document.getElementById('previewAreaImagenPerfil');
    const postImage = document.getElementById('profilePicture');
    const profileImageContainer = document.querySelector('.menu-imagen-perfil');

    if (!previewAreaImagen || !postImage || !profileImageContainer) return;

    function handleImageSelect(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (file && file.type.startsWith('image/')) {
            // Verificar si el tamaño de la imagen es menor a 1 MB
            const maxSizeInBytes = 1048576; // 1 MB en bytes
            if (file.size <= maxSizeInBytes) {
                console.log('Imagen seleccionada:', file);
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                postImage.files = dataTransfer.files;
                updateImagePreview(file);
            } else {
                alert('La imagen seleccionada supera el tamaño máximo de 1 MB. Por favor, seleccione una imagen más pequeña.');
            }
        } else {
            alert('Por favor, seleccione un archivo de imagen');
        }
    }

    function updateImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const imgHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
            previewAreaImagen.innerHTML = imgHTML;
        };
        reader.readAsDataURL(file);
    }

    async function uploadImageToWordPress(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'cambiar_imagen_perfil');

        try {
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                const confirmMessage = 'Imagen subida con éxito. ¿Desea cambiar la imagen de perfil?';
                const confirmed = await new Promise(resolve => resolve(confirm(confirmMessage)));

                if (confirmed) {
                    // Remove the preview image
                    previewAreaImagen.innerHTML = 'Tu imagen de perfil ya se cambio :)';

                    // Update the profile image
                    const imgHTML = `<img src="${result.newImageUrl}" alt="Perfil" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
                    profileImageContainer.innerHTML = imgHTML;
                }
            } else {
                alert('Hubo un problema al subir la imagen.');
            }
        } catch (error) {
            console.error('Error al subir la imagen:', error);
        }
    }

    previewAreaImagen.addEventListener('click', () => postImage.click());
    postImage.addEventListener('change', async event => {
        handleImageSelect(event);
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            await uploadImageToWordPress(file);
        }
    });

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        previewAreaImagen.addEventListener(eventName, e => {
            e.preventDefault();
            if (eventName === 'dragover') {
                previewAreaImagen.classList.add('dragover');
            } else {
                previewAreaImagen.classList.remove('dragover');
                if (eventName === 'drop') handleImageSelect(e);
            }
        });
    });
}

function cambiarNombreUsuario() {
    const inp = document.getElementById('nombreUsuario');
    if (!inp) return;

    let original = inp.value;
    const maxCaracteres = 20;
    const boton = document.querySelector('.guardarConfig');
    if (!boton) return;

    boton.addEventListener('click', async () => {
        const nuevoValor = inp.value.trim();

        if (nuevoValor === original || !nuevoValor) {
            return;
        }

        if (nuevoValor.length < 3) {
            alert('El nombre debe tener al menos 3 caracteres.');
            return;
        }

        if (nuevoValor.length > maxCaracteres) {
            alert(`El nombre no puede superar ${maxCaracteres} caracteres.`);
            return;
        }

        if (/[^a-z0-9._-]/i.test(nuevoValor)) {
            alert('Caracteres inválidos. Usa letras, números, ., _ o -.');
            return;
        }

        const confirmar = await new Promise(resolve => resolve(confirm(`¿Cambiar usuario a "${nuevoValor}"?`)));

        if (confirmar) {
            const respuesta = await enviarAjax('cambiar_username', {new_username: nuevoValor});
            if (respuesta.success) {
                alert('Nombre actualizado.');
                inp.value = nuevoValor;
                original = nuevoValor;
            } else {
                alert('Error: ' + (respuesta.message || 'Error desconocido'));
            }
        }
    });
}

function IniciadoresConfigPerfil() {
    SubidaImagenPerfil();
    selectorFanArtistaTipo();
    cambiarNombre();
    cambiarUsername();
    cambiarDescripcion();
    cambiarEnlace();
    copiarEnlacePerfil();
}

/*
//GENERIC FETCH (NO SE PUEDE CAMBIAR O ALTERAR ) no toques esta funcion ni nada, usalo para simplificar
async function enviarAjax(action, data = {}) {
    try {
        const body = new URLSearchParams({
            action: action,
            ...data
        });
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
        }
        let responseData;
        const responseText = await response.text();
        try {
            responseData = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('No se pudo interpretar la respuesta como JSON:', {
                error: jsonError,
                responseText: responseText,
                action: action,
                requestData: data
            });
            responseData = responseText;
        }
        return responseData;
    } catch (error) {
        console.error('Error en la solicitud AJAX:', {
            error: error,
            action: action,
            requestData: data,
            ajaxUrl: ajaxUrl
        });
        return {success: false, message: error.message};
    }
}

el siguiente codigo ya no deben de depender de que si se da enter, debe depender de un boton con la clase guardarConfig, si se da click a ese boton, se guarda las configuraciones que hayan combiado, dame el codigo completo. 
*/

function cambiarNombre() {
    const usernameInput = document.getElementById('username');
    if (!usernameInput) {
        return;
    }

    const originalUsername = usernameInput.value;
    const maxCharacters = 20;

    // No se requiere el evento 'keydown' para Enter, ya que el botón se encargará de guardar los cambios.

    // Agregamos un evento 'click' al botón con la clase 'guardarConfig'
    const guardarConfigButton = document.querySelector('.guardarConfig');
    if (guardarConfigButton) {
        guardarConfigButton.addEventListener('click', async function () {
            const newUsername = usernameInput.value.trim();
            if (newUsername === originalUsername) {
                return; // No se necesitan cambios
            }
            if (!newUsername) {
                alert('Por favor, ingresa un nombre de usuario.');
                return;
            }
            if (newUsername.length > maxCharacters) {
                alert(`El nombre de usuario no puede tener más de ${maxCharacters} caracteres.`);
                return;
            }

            const confirmMessage = `¿Estás seguro que quieres cambiar el nombre de usuario a "${newUsername}"?`;
            const confirmed = await new Promise(resolve => resolve(confirm(confirmMessage)));

            if (confirmed) {
                try {
                    const response = await enviarAjax('cambiar_nombre', {new_username: newUsername});
                    if (response.success) {
                        alert('Nombre de usuario actualizado con éxito.');
                        usernameInput.value = newUsername;
                        originalUsername = newUsername;
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (error) {
                    console.error('Error al cambiar el nombre de usuario:', error);
                    alert('Hubo un error al intentar cambiar el nombre de usuario.');
                }
            }
        });
    }
}

function cambiarDescripcion() {
    const descripcionInput = document.getElementById('description');
    let originalDescripcion = descripcionInput.value;

    if (!descripcionInput) return;

    // Limitar a 300 caracteres
    descripcionInput.addEventListener('input', function () {
        if (descripcionInput.value.length > 300) {
            descripcionInput.value = descripcionInput.value.slice(0, 300);
        }
    });

    // No se requiere el evento 'keydown' para Enter, ya que el botón se encargará de guardar los cambios.

    // Agregamos un evento 'click' al botón con la clase 'guardarConfig'
    const guardarConfigButton = document.querySelector('.guardarConfig');
    if (guardarConfigButton) {
        guardarConfigButton.addEventListener('click', async function () {
            const nuevaDescripcion = descripcionInput.value;
            if (nuevaDescripcion === originalDescripcion) {
                return; // No se necesitan cambios
            }

            const confirmMessage = `¿Estás seguro que quieres cambiar la descripción a:\n\n"${nuevaDescripcion}"?`;
            const confirmed = await new Promise(resolve => resolve(confirm(confirmMessage)));

            if (confirmed) {
                try {
                    const response = await enviarAjax('cambiar_descripcion', {new_description: nuevaDescripcion});
                    if (response.success) {
                        alert('Descripción actualizada con éxito.');
                        descripcionInput.value = nuevaDescripcion;
                        originalDescripcion = nuevaDescripcion;
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (error) {
                    console.error('Error al cambiar la descripción:', error);
                    alert('Hubo un error al intentar cambiar la descripción.');
                }
            }
        });
    }
}

function cambiarEnlace() {
    const linkInput = document.getElementById('link');
    let originalLink = linkInput.value;
    const maxCharacters = 100;

    if (!linkInput) return;

    // No se requiere el evento 'keydown' para Enter, ya que el botón se encargará de guardar los cambios.

    // Agregamos un evento 'click' al botón con la clase 'guardarConfig'
    const guardarConfigButton = document.querySelector('.guardarConfig');
    if (guardarConfigButton) {
        guardarConfigButton.addEventListener('click', async function () {
            const newLink = linkInput.value.trim();
            if (newLink === originalLink) {
                return; // No se necesitan cambios
            }
            if (newLink.length > maxCharacters) {
                alert(`El enlace no puede tener más de ${maxCharacters} caracteres.`);
                return;
            }

            let confirmMessage;
            if (originalLink === '') {
                confirmMessage = `¿Estás seguro que quieres agregar el enlace "${newLink}"?`;
            } else {
                confirmMessage = `¿Estás seguro que quieres cambiar el enlace de "${originalLink}" a "${newLink}"?`;
            }

            const confirmed = await new Promise(resolve => resolve(confirm(confirmMessage)));

            if (confirmed) {
                try {
                    const response = await enviarAjax('cambiar_enlace', {new_link: newLink});
                    if (response.success) {
                        alert('Enlace actualizado con éxito.');
                        linkInput.value = newLink;
                        originalLink = newLink;
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (error) {
                    console.error('Error al cambiar el enlace:', error);
                    alert('Hubo un error al intentar cambiar el enlace.');
                }
            }
        });
    }

    // Si el enlace original está vacío, muestra un placeholder
    if (originalLink === '') {
        linkInput.placeholder = 'Ingresa un enlace (opcional)';
    }
}

function selectorFanArtistaTipo() {
    const fancheck = document.getElementById('fanTipoCheck');
    const artistacheck = document.getElementById('artistaTipoCheck');

    if (!fancheck || !artistacheck) return;

    let timeoutId = null;

    function updateStyles(checkbox) {
        const label = checkbox.closest('label');
        if (checkbox.checked) {
            label.style.color = '#ffffff';
            label.style.background = '#131313';
        } else {
            label.style.color = '#6b6b6b';
            label.style.background = '';
        }
    }

    function guardarTipoUsuario(tipoUsuario) {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'guardarTipoUsuario',
                tipoUsuario: tipoUsuario
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Recarga la página
                } else {
                    console.error('Error al guardar tipo de usuario:', data.data);
                }
            })
            .catch(error => {
                console.error('Error en la solicitud AJAX:', error);
            });
    }

    function handleChange() {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        const tipoUsuario = fancheck.checked ? 'Fan' : artistacheck.checked ? 'Artista' : null;

        if (tipoUsuario) {
            timeoutId = setTimeout(() => {
                guardarTipoUsuario(tipoUsuario);
            }, 1000); // Espera de 1 segundo
        }
    }

    function applyInitialStyles() {
        updateStyles(fancheck);
        updateStyles(artistacheck);
    }

    fancheck.addEventListener('change', function () {
        if (fancheck.checked) {
            artistacheck.checked = false;
            updateStyles(artistacheck);
        } else if (!artistacheck.checked) {
            fancheck.checked = true;
        }
        updateStyles(fancheck);
        handleChange();
    });

    artistacheck.addEventListener('change', function () {
        if (artistacheck.checked) {
            fancheck.checked = false;
            updateStyles(fancheck);
        } else if (!fancheck.checked) {
            artistacheck.checked = true;
        }
        updateStyles(artistacheck);
        handleChange();
    });

    applyInitialStyles();
}

function copiarEnlacePerfil() {
    const botonesCompartir = document.querySelectorAll('.compartirPerfil');

    botonesCompartir.forEach(boton => {
        boton.addEventListener('click', function () {
            const username = this.getAttribute('data-username');
            const enlacePerfil = `https://2upra.com/perfil/${username}`;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard
                    .writeText(enlacePerfil)
                    .then(() => {
                        alert('Enlace del perfil copiado al portapapeles: ' + enlacePerfil);
                    })
                    .catch(err => {
                        console.error('Error al copiar el enlace: ', err);
                        alert('No se pudo copiar el enlace al portapapeles.');
                    });
            } else {
                // Ofrecer un método alternativo o informar al usuario que no es compatible
                alert('La función de copiar al portapapeles no está disponible en este dispositivo.');
            }
        });
    });
}
