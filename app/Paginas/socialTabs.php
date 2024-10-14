<?

function socialTabs()
{
    ob_start();
?>

    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Proyecto"></div>
        <div data-tab="inicio"></div>
    </div>

    <div class="tabs">
        <div class="tab-content">

            <!-- Solo visible para administradores -->
            <div class="tab INICIO S4K7I3" id="inicio">
                <div class="OXMGLZ">
                    <div class="OAXRVB">
                        <div class="K51M22">

                            <div class="PODOVV">
                                <? // echo momentosfijos() 
                                ?>
                            </div>
                        </div>
                        <div class="M0883I">
                            <? echo formRs(); ?>
                        </div>

                        <div class="FEDAG5">
                            <? echo publicaciones(['filtro' => 'nada', 'posts' => 10]); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido visible para todos los usuarios -->
            <div class="tab S4K7I3" id="Proyecto">
                <? echo devlogin(); ?>
            </div>

        </div>
    </div>

<?
    return ob_get_clean();
}



function momentosfijos()
{
    ob_start();

    $imagenUno = "https://images.ctfassets.net/kftzwdyauwt9/2CPrXUZS0yLGo894hU24zv/b9e1759c6f213a8888e17852266c515b/apple-art-2a-3x4.jpg?w=640&q=90&fm=webp";
    $imagenDos = "https://images.ctfassets.net/kftzwdyauwt9/1ZTOGp7opuUflFmI2CsATh/df5da4be74f62c70d35e2f5518bf2660/ChatGPT_Carousel1.png?w=640&q=90&fm=webp";
    $imagenTres = "https://images.ctfassets.net/kftzwdyauwt9/3XDJfuQZLCKWAIOleFIFZn/14b93d23652347ee7706eca921e3a716/enterprise.png?w=640&q=90&fm=webp";

?>
    <div class="ZCOPHT" style="background-image: url('<? echo esc_url($imagenUno); ?>');" onclick="window.location.href='https://2upra.com/quehacer';">
        <p>Que hacer en 2upra</p>
    </div>
    <div class="ZCOPHT" style="background-image: url('<? echo esc_url($imagenDos); ?>');" onclick="window.location.href='https://2upra.com/descubrir2upra';">
        <p>Descubre el proyecto</p>
    </div>
    <div class="ZCOPHT" style="background-image: url('<? echo esc_url($imagenTres); ?>');" onclick="window.location.href='https://2upra.com/reglas';">
        <p>Normas y Políticas</p>
    </div>
<?

    $contenido = ob_get_clean();
    return $contenido;
}
