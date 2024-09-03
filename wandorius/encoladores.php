<?php

function enqueue_fan_script()
{
    wp_enqueue_script('fan-script', get_template_directory_uri() . '/js/fan.js', array('jquery'), '1.0.36', true);
}
add_action('wp_enqueue_scripts', 'enqueue_fan_script');

function enqueue_progreso_script()
{
    wp_enqueue_script('progreso-script', get_template_directory_uri() . '/js/progreso.js', array('jquery'), '1.0.23', true);
}
add_action('wp_enqueue_scripts', 'enqueue_progreso_script');

function enqueue_modal_script()
{
    wp_enqueue_script('modal', get_template_directory_uri() . '/js/modal.js', array('jquery'), '1.0.22', true);
}
add_action('wp_enqueue_scripts', 'enqueue_modal_script');

function enqueue_alert_script()
{
    wp_enqueue_script('alert', get_template_directory_uri() . '/js/alert.js', array('jquery'), '1.0.4', true);
}
add_action('wp_enqueue_scripts', 'enqueue_alert_script');

function enqueue_submenu_script()
{
    wp_enqueue_script('submenu', get_template_directory_uri() . '/js/submenu.js', array('jquery'), '1.2.15', true);
}
add_action('wp_enqueue_scripts', 'enqueue_submenu_script');

function enqueue_pestanas_script()
{
    wp_enqueue_script('pestanas', get_template_directory_uri() . '/js/pestanas.js', array('jquery'), '1.1.10', true);
}
add_action('wp_enqueue_scripts', 'enqueue_pestanas_script');

function grafico_script()
{
    wp_enqueue_script('grafico', get_template_directory_uri() . '/js/grafico.js', array('jquery', 'lightweight-charts'), '1.0.23', true);
}
add_action('wp_enqueue_scripts', 'grafico_script');

function enqueue_lightweight_charts()
{
    wp_enqueue_script('lightweight-charts', 'https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_lightweight_charts');

function configPerfiljs()
{
    wp_enqueue_script('configPerfiljs', get_template_directory_uri() . '/js/configPerfil.js', array('jquery'), '1.0.14', true);
}
add_action('wp_enqueue_scripts', 'configPerfiljs');

function registro()
{
    wp_enqueue_script('registro', get_template_directory_uri() . '/js/registro.js', array('jquery'), '1.0.12', true);
}
add_action('wp_enqueue_scripts', 'registro');

function grain()
{
    wp_enqueue_script('grain', get_template_directory_uri() . '/js/grained.js', array('jquery'), '1.0.3', true);
}
add_action('wp_enqueue_scripts', 'grain');

function enqueue_charts()
{
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
    wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns', array('chart-js'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_charts');






