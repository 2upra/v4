<?

/*

*/

// Refactor(Org): Funcion verificarSampleEnColec movida a app/Services/CollectionService.php

// aqui puedes hacer que se ordene por ulima modificacion update_post_meta($collection_id, 'ultimaModificacion', current_time('mysql'));

// Refactor(Org): Funcion obtenerListaColec() y su hook movidos a app/Services/CollectionService.php

function modalCreacionColeccion()
{

    ob_start();
?>
    <div class="modalColec crearColec modalCrearColec modal" id="modalCrearColec" style="display: none;">
        <div class="colecciones formColec">
            <h3>Crear colección</h3>
            <div class="previewAreaArchivos previewColec" id="previewImagenColec">
                <label>Agregar imagen (opcional)</label>
            </div>
            <input type="text" placeholder="Nombre de la colección" id="tituloColec">
            <input type="text" placeholder="Descripción de la colección (opcional)" id="descripColec">

            <div class="bloque flex-row"" id=" opcionesColec" style="display: flex">
                <p>Opciones de post</p>
                <div class="flex flex-row gap-2">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="privadoColec" name="privadoColec" value="1">
                        <span class="checkmark"></span>
                        <? echo $GLOBALS['iconoPrivado']; ?>
                    </label>
                </div>
            </div>
            <div class="XJAAHB">
                <button class="botonsecundario" id="btnVolverColec">Volver</button>
                <button class="botonprincipal" id="btnCrearColec">Crear</button>
            </div>
        </div>
    </div>
<?
}
