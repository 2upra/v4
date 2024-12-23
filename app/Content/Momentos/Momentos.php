<?

function momentos()
{
    ob_start()
?>

    <? echo publicarMomento() ?>
    <? echo publicaciones(['filtro' => 'momento', 'tab_id' => 'Samples', 'posts' => 12]); ?>
<?
    return ob_get_clean();
}

function publicarMomento()
{
    ob_start()
?>
    <div class="publicarMomento">
        <? echo $GLOBALS['momentoIcon']; ?>
    </div>
<?
    return ob_get_clean();
}
