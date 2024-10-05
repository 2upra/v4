<?

/*
    TituloColab: 
    [ ] Que al dar click en la imagen se pueda elegir la imagen del proyecto
    [ ] Establecer imagenes de proyecto por defecto 
    [ ] Cambiar el titulo al dar click en el nombre 

    participantesColab:
    [ ] Que al dar click en el nombre se pueda ver la lista de participantes
    [ ] Que al dar click en el nombre de un participante se pueda ver el chat
    [ ] Poder añardir otros miembros
    [ ] El autor puede eliminar miembros
    
*/

function htmlColab($filtro)
{
    $post_id = get_the_ID();
    $var = variablesColab($post_id);
    extract($var);
    ob_start();

?>

    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($colabColaborador); ?>">

        <div class="colab-content">
            <? if ($filtro === 'colabPendiente'): ?>
                <? echo opcionesColab($var); ?>
                <? echo contenidoColab($var); ?>
            <? else: ?>
                <div class="UICMCG">
                    <? echo tituloColab($var); ?>
                    <? echo participantesColab($var) ?>
                    <? echo opcionesColabActivo($var); ?>
                </div>
                <? //echo chatColab($var); ?>
                <? //echo archivosColab($var); ?>
                <? //echo historialColab($var); ?>
                <? //echo comandosColab($var); ?>
                <? //echo enviarColab($var);?>
            <? endif; ?>

        </div>
    </li>

<?
    return ob_get_clean();
}

function colab()
{
    ob_start() ?>

    <div class="FLXVTQ">
        <a href="https://2upra.com/">
            <p>La funcionalidad de colaboración aún no esta disponible</p>
            <button class="borde">Volver</button>
        </a>
    </div>


<? return ob_get_clean();
}

function colabTest()
{
    ob_start();
?>
    <div class="IBPDFF">
        <div>
            <div>Colab pendientes</div>
            <? echo publicaciones(['post_type' => 'colab', 'filtro' => 'colabPendiente', 'posts' => 20]); ?>
        </div>
        <div>
            <? echo publicaciones(['post_type' => 'colab', 'filtro' => 'colab', 'posts' => 20]); ?>
        </div>
    </div>
<?
    return ob_get_clean();
}
