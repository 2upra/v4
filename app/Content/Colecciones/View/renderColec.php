<?

function htmlColec($filtro)
{
    ob_start();
    $postID = get_the_ID();
    $vars = variablesPosts($postID);
    extract($vars);
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($author_id); ?>">
        
    </li>
<?
    return ob_get_clean();
}
