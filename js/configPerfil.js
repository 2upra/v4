function IniciadoresConfigPerfil() {
    var paginaActualElement = document.getElementById('pagina_actual');
    
    if (paginaActualElement) {
        var paginaActual = paginaActualElement.value;
        
        if (paginaActual === 'Config') {
            SubidaImagenPerfil();
            cambiarNombre();
            cambiarDescripcion();
            cambiarEnlace();
        }
    } else {
        return
    }
}



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


function cambiarNombre() {
    const usernameInput = document.getElementById('username');
    if (!usernameInput) {
        return;
    }

    const originalUsername = usernameInput.value;
    const maxCharacters = 20;
    if (!originalUsername) {
        return;
    }

    usernameInput.addEventListener('keydown', async function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const newUsername = usernameInput.value.trim();
            if (newUsername === originalUsername || newUsername === '') {
                return;
            }
            if (newUsername.length > maxCharacters) {
                alert(`El nombre de usuario no puede tener más de ${maxCharacters} caracteres.`);
                return;
            }
            const confirmMessage = `¿Estás seguro que quieres cambiar el nombre de usuario a "${newUsername}"?`;
            const confirmed = await new Promise(resolve => resolve(confirm(confirmMessage)));
            if (confirmed) {
                const data = new URLSearchParams();
                data.append('action', 'cambiar_nombre');
                data.append('new_username', newUsername);
                try {
                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: data.toString(),
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Nombre de usuario actualizado con éxito.');
                        usernameInput.value = newUsername;
                    } else {
                        alert('Error: ' + result.data);
                    }
                } catch (error) {
                    console.error('Error al cambiar el nombre de usuario:', error);
                    alert('Hubo un error al intentar cambiar el nombre de usuario.');
                }
            }
        }
    });
}


function cambiarDescripcion() {
    const descripcionInput = document.getElementById('description');
    const originalDescripcion = descripcionInput.value;

    if (!descripcionInput || !originalDescripcion) return;

    // Limitar a 300 caracteres
    descripcionInput.addEventListener('input', function() {
        if (descripcionInput.value.length > 300) {
            descripcionInput.value = descripcionInput.value.slice(0, 300);
        }
    });

    descripcionInput.addEventListener('keydown', async function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();

            const nuevaDescripcion = descripcionInput.value; // No usar trim() aquí
            if (nuevaDescripcion === originalDescripcion || nuevaDescripcion === '') {
                return;
            }

            const confirmMessage = `¿Estás seguro que quieres cambiar la descripción a:\n\n"${nuevaDescripcion}"?`;
            const confirmed = await new Promise(resolve => resolve(confirm(confirmMessage)));

            if (confirmed) {
                const data = new URLSearchParams();
                data.append('action', 'cambiar_descripcion');
                data.append('new_description', nuevaDescripcion);
                try {
                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: data.toString(),
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Descripción actualizada con éxito.');
                        descripcionInput.value = nuevaDescripcion;
                    } else {
                        alert('Error: ' + result.data);
                    }
                } catch (error) {
                    console.error('Error al cambiar la descripción:', error);
                    alert('Hubo un error al intentar cambiar la descripción.');
                }
            }
        }
    });
}

function cambiarEnlace() {
    const linkInput = document.getElementById('link');
    const originalLink = linkInput.value;
    const maxCharacters = 100;

    if (!linkInput) return;

    linkInput.addEventListener('keydown', async function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();

            const newLink = linkInput.value.trim();
            if (newLink === originalLink || newLink === '') {
                return;
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
                const data = new URLSearchParams();
                data.append('action', 'cambiar_enlace');
                data.append('new_link', newLink);

                try {
                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: data.toString(),
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Enlace actualizado con éxito.');
                        linkInput.value = newLink;
                    } else {
                        alert('Error: ' + result.data);
                    }
                } catch (error) {
                    console.error('Error al cambiar el enlace:', error);
                    alert('Hubo un error al intentar cambiar el enlace.');
                }
            }
        }
    });

    // Si el enlace original está vacío, muestra un placeholder
    if (originalLink === '') {
        linkInput.placeholder = 'Ingresa un enlace (opcional)';
    }
}



