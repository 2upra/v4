<?php 



function menu1() {
  register_nav_menu('menu1',__('menu1'));
}
add_action('init', 'menu1');


