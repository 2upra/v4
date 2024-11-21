<?


function inversorSector()
{
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;
    $user_id = get_current_user_id();
    $acciones = get_user_meta($user_id, 'acciones', true);
    $pro = get_user_meta($user_id, 'user_pro', true);
    $resultados = calc_ing();

    // Asegurarse de que los valores sean numéricos antes de usarlos
    $valEmp = "$" . number_format((float) $resultados['valEmp'], 2, '.', '.');
    $valAcc = "$" . number_format((float) $resultados['valAcc'], 2, '.', '.');
    $user = wp_get_current_user();
    $acc = (float) get_user_meta($user->ID, 'acciones', true); // Convertir a float para evitar errores
    $valD = $acc * (float) $resultados['valAcc'];
    $name = ($user->display_name);

    ob_start();
?>
    <div class="UIKMYM">

        <div class="WZEFLA">
            <p>Hola <? echo esc_html($user_name); ?></p>
        </div>

        <div class="OIEODG">
            <p>Gracias por participar, estamos trabajando en mejorar la experiencia de entorno.</p>
        </div>

        <div class="JUJRQG">
            <a href="https://github.com/1ndoryu" class="no-ajax">
                <button class="DZYBQD" id="github-button">
                    <? echo $GLOBALS['Github']; ?> GitHub
                </button>
            </a>

            <a href="https://chat.whatsapp.com/G8hH7Gytfn5D2uYPibZT7N" class="no-ajax">
                <button class="DZYBQD" id="whatsapp-button">
                    <? echo $GLOBALS['Whatsapp']; ?> WhatsApp
                </button>
            </a>
        </div>

    </div>

    <div class="DAEOXT">

        <div class="TTVMWQ">
            <div class="XXDD IUNRBL">
                <h3 class="XXD1"><strong>Conviértete en patrocinador:</strong> Puedes colaborar obteniendo participación creativa, acceso anticipado, contenido exclusivo, reconocimiento y acciones mensuales del proyecto.</h3>

                <div class="DZYSQD DZYSQF">
                    <? echo botonSponsor(); ?>
                    <? echo botonComprarAcciones(); ?>
                </div>

            </div>
            <div class="XXDD IUNRBL">
                <h3 class="XXD1"><strong>Colabora como desarrollador:</strong> Recibirás una compensación acorde a tu participación, que puede incluir reconocimiento, acciones del proyecto o la posibilidad de formar parte del equipo principal y beneficiarte de las ganancias futuras.</h3>
                <a href="https://chat.whatsapp.com/JOduGKvWGR9KbYfBS9BWGL" class="no-ajax">
                    <div class="DZYSQD DZYSQF">
                        <button class="DZYBQD unirteproyecto<? if (!is_user_logged_in()) echo ' boton-sesion'; ?>"><? echo $GLOBALS['randomIcono']; ?>Unirte al proyecto</button>
                    </div>
                </a>
            </div>
        </div>

        <div class="XFBZWO MLJOFR">
            <div class="flex">

                <div class="QSBVLN">
                    <p class="ZTHAWI">Total recaudado</p>
                    <p class="BFUUUL">722$</p>
                </div>

                <div class="MDOKUH">
                    <p class="ZTHAWI">Meta</p>
                    <p class="BFUUUL">5000$</p>
                </div>

            </div>

            <div class="progress-containerA1">
                <div class="progress-barA1"></div>
            </div>

            <div class="GTVVIG">

                <div class="XFBZWO">
                    <div class="flex justify-between items-center">
                        <p class="ZTHAWI">Tu valor actual</p>
                    </div>
                    <p class="BFUUUL">$<? echo number_format($valD, 2, '.', '.'); ?></p>
                    <div class="GraficoCapital">
                        <? echo graficoHistorialAcciones(); ?>
                    </div>
                </div>

                <div class="XFBZWO">
                    <p class="ZTHAWI">Valor 2upra</p>
                    <p class="BFUUUL"><? echo $valEmp; ?></p>
                    <div class="GraficoCapital">
                        <? echo capitalValores(); ?>
                    </div>
                </div>

                <div class="XFBZWO">
                    <p class="ZTHAWI">Valor Acción</p>
                    <p class="BFUUUL"><? echo $valAcc; ?></p>
                    <div class="GraficoCapital">
                        <? echo bolsavalores(); ?>
                    </div>
                </div>

            </div>

        </div>
        <div class="articulosPost">
            <h3>Avances e información</h3>
            <? echo publicaciones(['filtro' => 'nada', 'post_type' => 'post', 'tab_id' => 'Proyecto', 'posts' => 12]); ?>
        </div>

        <div class="WLOZDD">
            <p>Muchas gracias</p>
            <? echo calcularAccionPorUsuario(); ?>
        </div>

        <? if (current_user_can('administrator')) : ?>
            <div class="YXJWYY flex">
                <div class="XFBZWO">
                    <? echo formCompraAcciones(); ?>
                </div>
            </div>
        <? endif; ?>

        <? echo modalComprarAcciones(); ?>

    </div>

<?
    return ob_get_clean();
}
