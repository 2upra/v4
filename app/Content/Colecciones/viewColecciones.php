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

function modalColeccion($postId = null)
{

    ob_start();
?>
    <div class="modalColec modal">
        <div class="colecciones">
            <h3>Colecciones</h3>
            <input type="text" placeholder="Buscar colección">
            
            <ul class="listaColeccion borde">
                <li class="coleccion" id="favoritos">
                    <img src="<? echo img('https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg', 40, 'all') ?>" alt=""><span>Favoritos</span>
                </li>
                <li class="coleccion borde" id="despues">
                    <img src="<? echo img('https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg', 40, 'all') ?>" alt=""><span>Usar mas tarde</span>
                </li>
            </ul>

            <div class="XJAAHB">
                <button class="botonprincipal">Listo</button>
            </div>
        </div>
    </div>
<?
}

function modalCreacionColeccion($postId = null)
{

    ob_start();
?>
    <div class="modalColec crearColec modal">
        <div class="colecciones formColec">
            <h3>Crear colección</h3>
            <div class="previewAreaArchivos previewColec" id="previewImagenColec">
                <label>Agregar imagen (opcional)</label>
            </div>
            <input type="text" placeholder="Nombre de la colección">
            <input type="text" placeholder="Descripción de la colección (opcional)">

            <button class="botonprincipal">Crear</button>
        </div>
    </div>
<?
}

function botonColeccion($postId)
{

    ob_start();
?>

    <div class="ZAQIBB botonColeccion">
        <button class="botonColeccionBtn" data-post_id="<? esc_attr($postId) ?>" data-nonce="<? wp_create_nonce('colec_nonce') ?>">
            <? echo $GLOBALS['iconoGuardar']; ?>
        </button>
    </div>

<?
}
