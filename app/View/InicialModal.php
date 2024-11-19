<?

/*

Necesito el script de estos modales, va cumplir una funcion,
lo primero es que estarán ocultos inicialmente 
son 2 modales y modalTipoUsuario debe aparecer primero y modalGeneros despues que el usuario termine elegir el tipo de usuario 
el usuario en modalTipoUsuario debe elegir entre fan y artista, al dar click en uno, debe guardar la meta tipoUsuario con el valor Artista o Fan 
en modalgeneros el usuario puede elegir varios generos, debe elegir al menos 1 (y agregar la clase seleccionados) y al dar listo enviar los generos que seleciono pro ajax

y listo, puedes usar esta funcion para facilitar la tarea 

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


*/


function modalTipoUsuario()
{
$userId = get_current_user_id();
$tipoUsuario = get_user_meta($userId, 'tipoUsuario', true);

// Si ya existe un tipo de usuario, no mostramos nada
if (!empty($tipoUsuario)) {
    return '';
}

$fanDiv = img('https://2upra.com/wp-content/uploads/2024/11/aUZjCl0WQ_mmLypLZNGGJA.webp');
$artistaBg = img('https://2upra.com/wp-content/uploads/2024/11/ODuY4qpIReS8uWqwSTAQDg.webp');
ob_start();
?>
<div class="modal selectorModalUsuario" style="display: none;">
    <div class="TIPEARTISTSF">
        <div class="selectorUsuario borde" id="fanDiv">
            <p>Fan</p>
        </div>
        <div class="selectorUsuario borde" id="artistaDiv">
            <p>Artista</p>
        </div>
    </div>
    <style>
        #fanDiv::before {
            background-image: url('<?php echo $fanDiv; ?>');
        }

        #artistaDiv::before {
            background-image: url('<?php echo $artistaBg; ?>');
        }
    </style>
    <button class="botonsecundario" style="display: none;">Siguiente</button>
</div>
<?php
return ob_get_clean();
}

function modalGeneros()
{
$userId = get_current_user_id();
$usuarioPreferencias = get_user_meta($userId, 'usuarioPreferencias', true);

// Si ya existen preferencias, no mostramos nada
if (!empty($usuarioPreferencias)) {
    return '';
}

ob_start();
?>

<div class="modal selectorGeneros" style="display: none;">
    <div class="GNEROBDS">
        <div class="borde">
            <p>Trap</p>
        </div>
        <div class="borde">
            <p>R&B</p>
        </div>
        <div class="borde">
            <p>Pop</p>
        </div>
        <div class="borde">
            <p>Tech House</p>
        </div>
        <div class="borde">
            <p>EDM</p>
        </div>
        <div class="borde">
            <p>Disco</p>
        </div>
        <div class="borde">
            <p>Soul</p>
        </div>
        <div class="borde">
            <p>Techno</p>
        </div>
    </div>
    <button class="botonsecundario">Listo</button>
</div>

<?
return ob_get_clean();
}

/*

        <div class="borde">
            <p>Cinematic</p>
        </div>
        <div class="borde">
            <p>Reggaeton</p>
        </div>
        <div class="borde">
            <p>Hip hop</p>
        </div>
        <div class="borde">
            <p>Drum and Bass</p>
        </div>
        <div class="borde">
            <p>Rock</p>
        </div>
        <div class="borde">
            <p>Jazz</p>
        </div>
        <div class="borde">
            <p>Classical</p>
        </div>
        <div class="borde">
            <p>Funk</p>
        </div>
        <div class="borde">
            <p>Blues</p>
        </div>
        <div class="borde">
            <p>Dubstep</p>
        </div>
        <div class="borde">
            <p>House</p>
        </div>
        <div class="borde">
            <p>Afrobeat</p>
        </div>

        */