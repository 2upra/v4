<?php
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>

	<div id="main">
		<p style="text-align: center;">404</p>
	</div>

	<div id="content">
	</div>

	<?php wp_footer(); ?>
</body>

</html>