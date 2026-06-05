<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// All non-API requests return the Vue SPA shell
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
