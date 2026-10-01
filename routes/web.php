<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'Visor TV Enterprise',
        'author' => 'Ashly Nicole',
        'docs' => url('/docs/index.html'),
    ]);
});

Route::get('/docs', function () {
    return redirect('/docs/index.html');
});

Route::get('/api/docs', function () {
    return redirect('/docs/index.html');
});
