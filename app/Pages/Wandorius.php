<?php
// Refactor(Org): Moved function freelancer_pestanas() and its shortcode to app/View/Components/Freelancer/FreelancerTabs.php
// Refactor(Org): Moved function html1() and its shortcode to app/View/Components/Wandorius/WandoriusContent.php

add_action( 'wp_enqueue_scripts', 'desactivar_scripts_en_asley' );
function desactivar_scripts_en_asley() {
  if ( is_page( 'asley' ) ) {
    global $wp_scripts;
    $wp_scripts->queue = array();
  }
}
?>