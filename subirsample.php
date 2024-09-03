<?php
/*
Template Name: Subir sample
*/
get_header();
?>

<div id="main">
	<div id="content">
		<input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">
		<div id="formulariosubirrola">
			<?php echo do_shortcode('[sample_form]'); ?>
		</div>

	</div>
</div>

<?php
get_footer();
//ENCOLADORES EN PANEL.PHP
?>