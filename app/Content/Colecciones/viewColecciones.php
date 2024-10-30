<?

/*

#VIEW

1) Un modal donde aparezca la lista de colecciones, por defecto todos los usuarios tienen una coleccion privada de "Favoritos", y "Usar mas tarde" por defecto aparece un boton de crear nueva coleccion, el modal se abre con un boton para crear una nueva coleccion. #Yo me encarog del js mas adelante

1.1) En el modal por defecto va aparecer "Favoritos", y "Usar mas tarde" pero no va a existir fisicamente en la base datos sino despues de que un usuario guarde algo alli

2) Hay que hacer el formularuio dinamico que crea una nueva coleccion dentro del mismo modal, tiene que ser sencillo, tiene que pedir una foto, un nombre, descripcion y tags. #Yo me encargo del js mas adelante

3) Tambien hay que hacer un view para las colecciones 
    Un view que tiene todas las colecciones propia
    Un view para las colecciones publicas 
    Un view dentro de la coleccion con los audios


*/

function modalColeccion($postId)
{
    
    ob_start();
?>
    <div class="modalColec modal">
        <div class="colecciones">
            <ul class="listaColeccion">
                <li class="coleccion" id="favoritos">

                </li>
                <li class="coleccion" id="despues">

                </li>
            </ul>
        </div>
    </div>


<?
}

function botonGuardarColeccion() {

    ob_start();
    ?>

    <div>

    </div>
    
    <?
}
