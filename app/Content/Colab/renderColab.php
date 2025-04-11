<?

/*
    TituloColab: 
    [ ] Que al dar click en la imagen se pueda elegir la imagen del proyecto
    [ ] Establecer imagenes de proyecto por defecto 
    [ ] Cambiar el titulo al dar click en el nombre 

    participantesColab:
    [ ] Que al dar click en el nombre se pueda ver la lista de participantes
    [ ] Que al dar click en el nombre de un participante se pueda ver el chat
    [ ] Poder añardir otros miembros
    [ ] El autor puede eliminar miembros
    
*/

// Refactor(Org): Función htmlColab movida a app/View/Renderers/ColabRenderer.php

function colab()
{
    ob_start() ?>

    <div class="FLXVTQ">
        <a href="https://2upra.com/">
            <p>La funcionalidad de colaboración aún no esta disponible</p>
            <button class="borde">Volver</button>
        </a>
    </div>


<? return ob_get_clean();
}

// Refactor(Exec): Función colabTest() movida a app/Test/ColabTest.php

// Refactor(Org): Función chatColab() movida a app/View/Renderers/ChatRenderer.php

// Refactor(Exec): Función colabsResumen() movida a app/View/Helpers/ColabHelper.php
