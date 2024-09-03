<?php
function noMorePost()
{
    return '<div id="no-more-posts"></div>';
}

function noHayRola()
{
    return <<<HTML
    <div class="aunno rola">
    <p>Aún no has publicado tu primera rola. Estamos emocionados por recibir tu primer proyecto.✨</p>
    <p>Aquí apareceran tus rolas publicadas</p>
    <button class="botones-panel" data-href="https://2upra.com/rola">Subir rola</button>
    <button id="reportarerror" class="reportarerror">Reportar un error</button>
    <div class="aunno1"></div>
    </div>
    <div id="no-more-posts"></div>
    HTML;
}

function noRolaRechazada()
{
    return <<<HTML
    <div class="aunno rola">
    <p>Hola, aquí esta vacío✨</p>
    <button class="botones-panel" data-href="https://2upra.com/rola">Subir rola</button>
    <div class="aunno1"></div>
    </div>
    <div id="no-more-posts"></div>
    HTML;
}

function noRolaLikes()
{
    return <<<HTML
    <div class="aunno rola">
    <p>Aún no has dado like a ninguna rola✨</p>
    <button class="botones-panel" data-href="https://2upra.com/rola">Descubrir rolitas</button>
    <div class="aunno1"></div>
    </div>
    <div id="no-more-posts"></div>
    HTML;
}

function vacio()
{
    $shortcode = do_shortcode('[top_users]');
    return <<<HTML
    <div class="aunno">
    <p>E'to ta vacío ☠️</p>
    <div class="aunno1">   
    <button class="botones-colab" onclick="location.href='#tab1'">Publicar algo</button>
    <button class="botones-colab" onclick="location.href='#tab2'">Explorar</button>
    {$shortcode}
    </div>
    </div>
    HTML;
}
