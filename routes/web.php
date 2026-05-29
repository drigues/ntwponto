<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/robots.txt', function () {
    $content = implode("\n", [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /admin/',
        'Disallow: /filament',
        'Disallow: /horizon',
        'Disallow: /telescope',
        'Disallow: /_debugbar',
        'Disallow: /storage/',
        'Disallow: /vendor/',
        'Disallow: /*.env',
        'Disallow: /*.log',
        'Allow: /',
    ]);

    return response($content, 200, ['Content-Type' => 'text/plain']);
});
