<?php

// Funcion movida desde app/AlgoritmoPost/algoritmoPosts.php
function getDecayFactor($days, $useDecay = false)
{
    static $decaimientoF = [];
    static $use_decay = false;

    if (func_num_args() > 1) {
        $use_decay = $useDecay;
    }

    if (!$use_decay) {
        return 1;
    }

    if (empty($decaimientoF)) {
        for ($d = 0; $d <= 365; $d++) {
            $decaimientoF[$d] = pow(0.99, $d);
        }
    }

    $days = min(max(0, (int) $days), 365);

    return $decaimientoF[$days];
}
