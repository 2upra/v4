<?php
/*
Template Name: Inicio Prueba
*/
get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">

        <div class="bloquesChatTest">
            <?php echo conversacionesUsuario($user_id) ?>
            <?php echo renderChat() ?>
            <div>
                <button class="mensajeBoton" data-receptor="44">Enviar a 44</button>
                <button class="mensajeBoton" data-receptor="3">Enviar a 3</button>
            </div>
        </div>




    </div>
</div>

<?php
get_footer();
?>