<?

function add_pro_modal_to_footer()
{

    $plan_title = 'Patrocinio ';
    $highlight = '✨';
    $modal_content = '
        <p class="priceplan">$5 <span>USD/mensual</span></p>
        <p class="beneficiosplan">+ Participación creativa</p>
        <p class="beneficiosplan">+ Acceso anticipado</p>
        <p class="beneficiosplan">+ Contenido exclusivo</p>
        <p class="beneficiosplan">+ Reconocimiento</p>
        <p class="beneficiosplan">+ Acciones mensuales del proyecto</p>
        <p class="beneficiosplan">+ Sin limites de descarga</p>
        <p class="beneficiosplan">+ Sin limites de almacenamiento</p>
        <button class="DZYBQD MQKUSE">Suscribirte</button>';

?>
    <div class="panelperfilsup modalpro" id="propro">
        <div class="panelperfilsupsec pla1">
            <p class="titulomodal">Apoya el proyecto y recibe beneficios</p>
        </div>
        <div class="panelperfilsupsec plan2">
            <p class="tituloplan"><? echo $plan_title . $highlight; ?></p>
            <? echo $modal_content; ?>
        </div>
    </div>

    <div class="panelperfilsup modalpro" id="proproacciones">
        <div class="panelperfilsupsec pla1">
            <p class="titulomodal">Apoya el proyecto y recibe acciones mensuales</p>
        </div>
        <div class="panelperfilsupsec plan2">
            <p class="tituloplan"><? echo $plan_title . $highlight; ?></p>
            <? echo $modal_content; ?>
        </div>
    </div>
    <div id="modalBackground" class="modal-background"></div>
<?
}
add_action('wp_footer', 'add_pro_modal_to_footer');