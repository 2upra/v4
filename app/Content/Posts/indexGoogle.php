<?php

//Evitar que wp genere los titulos por defecto 
remove_action('wp_head', '_wp_render_title_tag', 1);

// Refactor(Org): Moved function regenerate_colecciones_sitemap() and its hook to SEOService.php

?>