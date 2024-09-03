<?php

function img($url, $quality = 40, $strip = 'all') {
    $parsed_url = parse_url($url);
    
    // Check if the URL is already a CDN URL
    if (strpos($url, 'https://i0.wp.com/') === 0) {
        $cdn_url = $url;
    } else {
        // If 'host' is not present, use the entire path
        $path = isset($parsed_url['host']) ? $parsed_url['host'] . $parsed_url['path'] : ltrim($parsed_url['path'], '/');
        $cdn_url = 'https://i0.wp.com/' . $path;
    }
    
    $query = [
        'quality' => $quality,
        'strip' => $strip,
    ];
    
    return add_query_arg($query, $cdn_url);
}