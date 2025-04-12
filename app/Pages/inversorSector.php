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
        <? if (is_user_logged_in()) : ?>
            <div class="WZEFLA">
                <p>¡Hola <? echo esc_html($user_name); ?>! 👋</p>
            </div>

            <div class="OIEODG">
                <p>¡Ayúdanos a seguir construyendo herramientas libres y accesibles para artistas y productores musicales! Juntos, podemos potenciar la creatividad y hacer que la música llegue más lejos. Este proyecto tiene un futuro brillante, ¡y tú puedes ser parte de él! 🚀</p>
            </div>
        <? endif; ?>

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
                <h3 class="XXD1"><strong>Conviértete en patrocinador</strong> </h3>
                <h3 class="XXD1 XXGE3D">Tu apoyo puede darte participación creativa, acceso anticipado, contenido exclusivo, reconocimiento y hasta acciones mensuales del proyecto. ¡Es una oportunidad increíble!</h3>
                <div class="DZYSQD DZYSQF">
                    <? echo botonSponsor(); ?>
                    <? echo botonComprarAcciones(); ?>
                </div>

            </div>
            <div class="XXDD IUNRBL">
                <h3 class="XXD1"><strong>Únete como desarrollador</strong></h3>
                <h3 class="XXD1 XXGE3D">Tu talento será recompensado. Podrás obtener reconocimiento, acciones del proyecto o incluso unirte al equipo principal y disfrutar de las ganancias futuras. ¡Anímate a crear con nosotros!</h3>
                <a href="https://chat.whatsapp.com/JOduGKvWGR9KbYfBS9BWGL" class="no-ajax">
                    <div class="DZYSQD DZYSQF">
                        <button class="DZYBQD unirteproyecto<? if (!is_user_logged_in()) echo ' boton-sesion'; ?>"><? echo $GLOBALS['randomIcono']; ?>¡Sumarme al proyecto!</button>
                    </div>
                </a>
            </div>
        </div>

        <div class="TTVMWQ SERVICSED">
            <div class="XXDD IUNRBL">
                <h3 class="XXD1"><strong>Servicios Profesionales</strong> </h3>
                <h3 class="XXD1 XXGE3D">Contribuye al proyecto con nuestros servicios. El equipo de 2upra te brinda soluciones expertas y confiables, con resultados en tiempo récord. Cada servicio contratado impulsa directamente el crecimiento y desarrollo continuo de nuestras herramientas.</h3>

                <div class="SGEDIGME">

                    <div class="RLSDSAE" data-url="https://es.fiverr.com/andoryu_art/design-a-modern-and-minimalist-brand-identity">
                        <img src="<? echo get_template_directory_uri(); ?>/assets/img/p1.jpg" alt="p1">
                        <h3>Diseño de páginas web minimalistas y profesionales</h3>
                        <div class="PDASG">
                            <button>Desde 80$</button>
                            <p>150$</p>
                        </div>
                    </div>

                    <div class="RLSDSAE" data-url="https://es.fiverr.com/andoryu_art/make-amazing-studio-style-anime-character">
                        <img src="<? echo get_template_directory_uri(); ?>/assets/img/d1.jpg" alt="d1">
                        <h3>Diseño de identidad visual y logotipos minimalistas</h3>
                        <div class="PDASG">
                            <button>Desde 5$</button>
                            <p>30$</p>
                        </div>
                    </div>

                    <div class="RLSDSAE" data-url="https://es.fiverr.com/andoryu_art/create-a-minimalist-and-modern-wordpress-website">
                        <img src="<? echo get_template_directory_uri(); ?>/assets/img/pd1.jpg" alt="pd1">
                        <h3>Pintura digital de personajes o escenarios, estilo de pintura/anime</h3>
                        <div class="PDASG">
                            <button>Desde 5$</button>
                            <p>30$</p>
                        </div>
                    </div>
                    
                    <div class="RLSDSAE">
                        <img src="<? echo get_template_directory_uri(); ?>/assets/img/f1.jpg" alt="f1">
                        <h3>Diseño de flyers, portadas, estilo personalizado</h3>
                        <div class="PDASG">
                            <button>Desde 10$</button>
                            <p>50$</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="XFBZWO MLJOFR">
            <div class="flex">

                <div class="QSBVLN">
                    <p class="ZTHAWI">Total recaudado</p>
                    <p class="BFUUUL">722$</p>
                </div>

                <div class="MDOKUH">
                    <p class="ZTHAWI">¡Vamos por!</p>
                    <p class="BFUUUL">5000$</p>
                </div>

            </div>

            <div class="progress-containerA1">
                <div class="progress-barA1"></div>
            </div>

            <div class="GTVVIG">

                <div class="XFBZWO">
                    <div class="flex justify-between items-center">
                        <p class="ZTHAWI">¡Tu valor actual!</p>
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
            <h3>Noticias y avances 📰</h3>
            <? echo publicaciones(['filtro' => 'nada', 'post_type' => 'post', 'tab_id' => 'Proyecto', 'posts' => 12]); ?>
        </div>

        <div class="WLOZDD">
            <p>¡Mil gracias por tu apoyo! 🙌</p>
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
