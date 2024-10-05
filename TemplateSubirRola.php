<?
/*
Template Name: Subir Rola
*/
get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
	<div id="content" class="<? echo esc_attr($nologin_class); ?>">

		<input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">
		<? if (!is_user_logged_in()):
			// Aqui hace falta una pagina que indique debe iniciar seccion para ver sus rolas
		?>

		<? else: ?>
			<div id="formulariosubirrola">
				<? echo postRolaForm() ?>
			</div>

		<? endif; ?>
	</div>
</div>

<?
get_footer();
?>