<?

/**
 * The footer template.
 *
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if (!defined('ABSPATH')) {
	exit('Direct script access denied.');
}


?>

<div>
	<? wp_footer(); ?>
</div>

<? get_template_part('templates/to-top'); ?>
</body>

<script>
window.addEventListener('load', function () {
    document.body.classList.add('loaded');
});
</script>
</html>