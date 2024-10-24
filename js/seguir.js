/* esto funciona pero la alerta falla en el sentido que deja de seguir inmediatamente y debería de esperar la confirmacion del usuario 

ejemplo algo asi, porque mi funcion de alertas es personalizada
                const confirmed = await confirm(confirmMessage); // Cuadro de confirmación.

                if (confirmed) {
*/
function seguir() {
    async function manejarSeguimiento(seguidor_id, seguido_id, esDejarDeSeguir) {
        // Guardar el estado anterior para poder revertir si hay error
        const estadoAnterior = esDejarDeSeguir;

        try {
            const action = esDejarDeSeguir ? 'dejar_de_seguir_usuario' : 'seguir_usuario';

            // Actualizar UI solo después de la confirmación
            actualizarBotones(seguido_id, !esDejarDeSeguir);

            const response = await enviarAjax(action, {
                seguidor_id: seguidor_id,
                seguido_id: seguido_id
            });

            if (!response.success) {
                // Si la petición falla, revertir al estado anterior
                actualizarBotones(seguido_id, estadoAnterior);
            }
        } catch (error) {
            // Si hay un error, revertir al estado anterior
            actualizarBotones(seguido_id, estadoAnterior);
            console.error('Error:', error);
        }
    }

    // Función para actualizar los botones
    function actualizarBotones(seguido_id, esDejarDeSeguir) {
        const botones = document.querySelectorAll(`.seguir[data-seguido-id="${seguido_id}"], .dejar-de-seguir[data-seguido-id="${seguido_id}"]`);

        botones.forEach(boton => {
            if (esDejarDeSeguir) {
                boton.innerHTML = `<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM5 7.25H4.25V8.75H5H11H11.75V7.25H11H5Z" fill="currentColor"></path></svg>`;
                boton.classList.remove('seguir');
                boton.classList.add('dejar-de-seguir');
            } else {
                boton.innerHTML = `<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM8.75 4.25V5V7.25H11H11.75V8.75H11H8.75V11V11.75L7.25 11.75V11V8.75H5H4.25V7.25H5H7.25V5V4.25H8.75Z" fill="currentColor"></path></svg>`;
                boton.classList.remove('dejar-de-seguir');
                boton.classList.add('seguir');
            }

            // Volver a agregar el event listener al botón actualizado
            boton.removeEventListener('click', handleClick);
            boton.addEventListener('click', handleClick);
        });
    }

    async function handleClick() {
        const seguidor_id = this.getAttribute('data-seguidor-id');
        const seguido_id = this.getAttribute('data-seguido-id');
        const esDejarDeSeguir = this.classList.contains('dejar-de-seguir');

        if (esDejarDeSeguir) {
            const confirmado = await confirm('¿Estás seguro de que quieres dejar de seguir a este usuario?');
            if (!confirmado) {
                return;
            }
        }

        await manejarSeguimiento(seguidor_id, seguido_id, esDejarDeSeguir);
    }

    // Agregar event listeners iniciales
    document.querySelectorAll('.seguir, .dejar-de-seguir').forEach(function (button) {
        button.addEventListener('click', handleClick);
    });
}

/*

function get_user_id_from_post($key) {
    return isset($_POST[$key]) ? (int) $_POST[$key] : 0;
}

function update_follow_relationship($follower_id, $followed_id, $action) {
    if (!is_numeric($follower_id) || !is_numeric($followed_id)) {
        return false;
    }

    $following = (array) get_user_meta($follower_id, 'siguiendo', true);
    $followers = (array) get_user_meta($followed_id, 'seguidores', true);

    if ($action === 'follow') {
        if (!in_array($followed_id, $following)) {
            $following[] = $followed_id;
            $followers[] = $follower_id;
        } else {
            return false;
        }
    } elseif ($action === 'unfollow') {
        $following = array_diff($following, [$followed_id]);
        $followers = array_diff($followers, [$follower_id]);
    }

    update_user_meta($follower_id, 'siguiendo', $following);
    return update_user_meta($followed_id, 'seguidores', $followers);
}

function seguir_usuario() {
    return update_follow_relationship(
        get_user_id_from_post('seguidor_id'),
        get_user_id_from_post('seguido_id'),
        'follow'
    );
}
add_action('wp_ajax_seguir_usuario', 'seguir_usuario');

function dejar_de_seguir_usuario() {
    return update_follow_relationship(
        get_user_id_from_post('seguidor_id'),
        get_user_id_from_post('seguido_id'),
        'unfollow'
    );
}
add_action('wp_ajax_dejar_de_seguir_usuario', 'dejar_de_seguir_usuario');
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
