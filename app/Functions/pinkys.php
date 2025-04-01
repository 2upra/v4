<?

add_action('wp_ajax_procesarDescarga', 'procesarDescarga');





function botonDescargaPrueba()
{
    ob_start();
    ?>
    <div class="ZAQIBB ASDGD8">
        <button aria-label="Descarga ejemplo">
            <? echo $GLOBALS['descargaicono']; ?>
        </button>
    </div>
<?
    return ob_get_clean();
}
