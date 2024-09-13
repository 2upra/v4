<?php
/*
Template Name: Subir sample
*/
get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
	<div id="content" class="<?php echo esc_attr($nologin_class); ?>">
		<input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">
		<?php if (!is_user_logged_in()):
			// Aqui hace falta una pagina
		?>
		<?php else: ?>
			<div id="formulariosubirrola">
				<?php echo do_shortcode('[sample_form]'); ?>
			</div>

		<?php endif; ?>
	</div>
</div>

<?php
get_footer();
//ENCOLADORES EN PANEL.PHP
?>