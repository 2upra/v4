<?php

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
	<?php wp_footer(); ?>
</div>

<?php get_template_part('templates/to-top'); ?>
</body>
<svg style="display:none;">
  <filter id="pixelate" x="0" y="0">
    <feFlood x="4" y="4" height="2" width="2"/>
    <feComposite width="10" height="10"/>
    <feTile result="a"/>
    <feComposite in="SourceGraphic" in2="a" operator="in"/>
    <feMorphology operator="dilate" radius="5"/>
  </filter>
</svg>
<script>
window.addEventListener('load', function () {
    document.body.classList.add('loaded');
});
</script>
</html>