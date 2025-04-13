<?php

function minutos55($schedules)
{
    $schedules['cada55'] = array(
        'interval' => 3300,
        'display' => __('Cada 55 minutos')
    );
    return $schedules;
}

function intervalo_cada_seis_horas($schedules)
{
    $schedules['cada_seis_horas'] = array(
        'interval' => 21600,
        'display' => __('Cada 6 Horas')
    );
    return $schedules;
}
