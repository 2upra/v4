function minimizarform() {
    // Obtiene todos los elementos 'textarea' dentro de los elementos con la clase 'form-group'
    var postContents = document.querySelectorAll('.form-group textarea');

    if (!postContents.length) return; // Si no hay ningún 'textarea', la función termina

    var formGroups = document.querySelectorAll('.form-group.hidden');

    // Define una función para alternar la visibilidad de los campos
    function toggleFields() {
        if (formGroups.length > 0) {
            // Si hay grupos de formularios ocultos
            var anyNonEmpty = Array.from(postContents).some(function (postContent) {
                return postContent.value.length > 0;
            });

            // Muestra u oculta los grupos de formularios según si algún 'textarea' no está vacío
            formGroups.forEach(function (group) {
                if (anyNonEmpty) {
                    group.classList.remove('hidden');
                } else {
                    group.classList.add('hidden');
                }
            });
        }
    }

    // Agrega event listeners a todos los 'textarea'
    postContents.forEach(function (postContent) {
        postContent.removeEventListener('input', toggleFields);
        postContent.addEventListener('input', toggleFields);
        postContent.removeEventListener('blur', toggleFields);
        postContent.addEventListener('blur', toggleFields);
    });

    const postPrice = document.getElementById('postPrice');

    if (postPrice) {
        postPrice.addEventListener('input', function (e) {
            let input = e.target.value.replace(/\D/g, '');
            let number = parseInt(input, 10);
            if (!isNaN(number) && number >= 0) {
                if (number > 10000) {
                    number = 10000;
                }
                let decimal = (number / 100).toFixed(2);
                e.target.value = '$' + decimal;
            } else {
                e.target.value = '';
            }
        });
    }

    function limitTextarea() {
        var maxLength = 190;
        // Acceder a la variable isAdmin proveniente de PHP
        if (!wpData.isAdmin) {
            postContents.forEach(function (postContent) {
                if (postContent.value.length > maxLength) {
                    postContent.value = postContent.value.substring(0, maxLength);
                    alert('El contenido del post debe ser máximo de 190 caracteres.');
                }
            });
        }
    }

    // Agrega event listeners a todos los 'textarea' para limitar su longitud
    postContents.forEach(function (postContent) {
        postContent.removeEventListener('input', limitTextarea);
        postContent.addEventListener('input', limitTextarea);
    });
}

function selectorformtipo() {
    jQuery(document).on('change', '.custom-checkbox input[type="checkbox"]', function () {
        if (this.checked) {
            jQuery(this).closest('label').css({
                'color': '#ffffff',
                'background': '#131313'
            });
        } else {
            jQuery(this).closest('label').css({
                'color': '#6b6b6b',
                'background': ''
            });
        }
    });
}
