<?php

// Refactor(Org): Función infoPost() movida desde app/Content/Posts/View/componentPost.php
function infoPost($autId, $autAv, $autNom, $postF, $postId, $block, $colab)
{
    $postAut = get_post_meta($postId, 'postAut', true);
    $ultEd = get_post_meta($postId, 'ultimoEdit', true);
    $verif = get_post_meta($postId, 'Verificado', true);
    $rec = get_post_meta($postId, 'recortado', true);
    $usrAct = (int)get_current_user_id();
    $autId = (int)$autId;
    $esUsrAct = ($usrAct === $autId);

    ob_start();
?>
    <div class="SOVHBY <? echo ($esUsrAct ? 'miContenido' : ''); ?>">
        <div class="CBZNGK">
            <a href="<? echo esc_url(get_author_posts_url($autId)); ?>"> </a>
            <img src="<? echo esc_url($autAv); ?>">
            <? echo botonseguir($autId); ?>
        </div>
        <div class="ZVJVZA">
            <div class="JHVSFW">
                <a href="<? echo esc_url(home_url('/perfil/' .  get_the_author_meta('user_nicename', $autId))); ?>" class="profile-link">
                    <? echo esc_html($autNom); ?>
                    <? if (get_user_meta($autId, 'pro', true) || user_can($autId, 'administrator') || get_user_meta($autId, 'Verificado', true)) : ?>
                        <? echo $GLOBALS['verificado']; ?>
                    <? endif; ?>
                </a>
            </div>
            <div class="HQLXWD">
                <a href="<? echo esc_url(get_permalink()); ?>" class="post-link">
                    <? echo esc_html($postF); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="verificacionPost">
        <? if ($verif == '1') : ?>
            <? echo $GLOBALS['check']; ?>
        <? elseif ($postAut == '1' && current_user_can('administrator')) : ?>
            <? echo $GLOBALS['robot']; ?>
        <? endif; ?>
    </div>

    <div class="OFVWLS">
        <? if ($rec) : ?>
            <div><? echo "Preview"; ?></div>
        <? endif; ?>
        <? if ($block) : ?>
            <div><? echo "Exclusivo"; ?></div>
        <? elseif ($colab) : ?>
            <div><? echo "Colab"; ?></div>
        <? endif; ?>
    </div>

    <div class="spin"></div>

    <div class="YBZGPB">
        <? echo opcionesPost($postId, $autId); ?>
    </div>
<?
    return ob_get_clean();
}
