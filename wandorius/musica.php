<?php

function musica() {
    
    $user_id = get_current_user_id(); 
    saberSi($user_id);
    ob_start();
    ?>

    <div class="tabs">
        <div class="tab-content">
            <div class="tab active ZYBVGE" id="Music" data-post-id="tab1-posts" ajax="no">
                
                <?php if (get_user_meta($user_id, 'leGustaAlMenosUnaRola', true)) : ?>
                    <div class="SAOEXP">
                        <div class="XZCZLA">
                            <p class="titulorolasenviadas">Rolas que te gustan</p>
                            <button class="TDMZDD"></button>
                        </div>
                        <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="likes" tab_id="tab1-posts" posts="6"]'); ?>
                    </div>
                <?php endif; ?>

                <div class="SAOEXP">
                    <div class="XZCZLA">
                        <p class="titulorolasenviadas">Últimas rolas</p>
                        <button class="TDMZDD"></button>
                    </div>
                    <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="rola" tab_id="tab1-posts" posts="6"]'); ?>
                </div>

                <div class="LGEMLK">
                </div>

            </div>
        </div>
    </div>

    <?php
    // Retorna el contenido generado
    return ob_get_clean();
}

