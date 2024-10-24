
/*

usa la funcion enviar ajax para simplificar seguir() (no cambies nada de enviarAjax), y los iconos no se colocan, falla, le das a seguir y no muestra nada
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
        return { success: false, message: error.message }; 
    }
}
*/

function seguir() {
    // Manejar el clic en el botón "Seguir"
    function seguir_usuario(seguidor_id, seguido_id, button) {
        jQuery.ajax({
            type: "POST",
            url: ajax_params.ajax_url,
            data: {
                action: "seguir_usuario",
                seguidor_id: seguidor_id,
                seguido_id: seguido_id
            },
            success: function(response) {
                console.log(response);
                // Cambiar el contenido del botón a SVG de restar
                button.innerHTML = `<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM5 7.25H4.25V8.75H5H11H11.75V7.25H11H5Z" fill="currentColor"></path></svg>`;
                button.classList.remove('seguir');
                button.classList.add('dejar-de-seguir'); // Cambia la clase a 'dejar-de-seguir'
            }
        });
    }

    function dejar_de_seguir_usuario(seguidor_id, seguido_id, button) {
        jQuery.ajax({
            type: "POST",
            url: ajax_params.ajax_url,
            data: {
                action: "dejar_de_seguir_usuario",
                seguidor_id: seguidor_id,
                seguido_id: seguido_id
            },
            success: function(response) {
                console.log(response);
                // Cambiar el contenido del botón a SVG de sumar
                button.innerHTML = `<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM8.75 4.25V5V7.25H11H11.75V8.75H11H8.75V11V11.75L7.25 11.75V11V8.75H5H4.25V7.25H5H7.25V5V4.25H8.75Z" fill="currentColor"></path></svg>`;
                button.classList.remove('dejar-de-seguir');
                button.classList.add('seguir'); // Cambia la clase a 'seguir'
            }
        });
    }

    document.querySelectorAll('.seguir, .dejar-de-seguir').forEach(function(button) {
        button.addEventListener('click', function() {
            var seguidor_id = this.getAttribute('data-seguidor-id');
            var seguido_id = this.getAttribute('data-seguido-id');

            if (this.classList.contains('seguir')) {
                seguir_usuario(seguidor_id, seguido_id, this); // Llama a seguir
            } else {
                dejar_de_seguir_usuario(seguidor_id, seguido_id, this); // Llama a dejar de seguir
            }
        });
    });
}


/*

php 

function botonseguir($author_id)
{
    $author_id = (int) $author_id;
    $current_user_id = get_current_user_id();

    if ($current_user_id === 0) {
        return ''; // Usuario no autenticado
    }

    // Si el usuario está viendo su propio perfil, añadimos una clase de deshabilitado
    if ($current_user_id === $author_id) {
        ob_start();
        ?>
        <button class="mismo-usuario" disabled>
            <? echo $GLOBALS['iconomisusuario']; ?>
        </button>
        <?php
        return ob_get_clean();
    }

    $siguiendo = get_user_meta($current_user_id, 'siguiendo', true);
    $es_seguido = is_array($siguiendo) && in_array($author_id, $siguiendo);

    $clase_boton = $es_seguido ? 'dejar-de-seguir' : 'seguir';
    $icono_boton = $es_seguido ? $GLOBALS['iconorestar'] : $GLOBALS['iconosumar'];

    ob_start();
    ?>
    <button class="<? echo esc_attr($clase_boton); ?>"
        data-seguidor-id="<? echo esc_attr($current_user_id); ?>"
        data-seguido-id="<? echo esc_attr($author_id); ?>">
        <? echo $icono_boton; ?>
    </button>
    <?php
    return ob_get_clean();
}

*/


