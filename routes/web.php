<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/admin/{path?}', function (?string $path = null) {
    return redirect('/dashboard'.($path === null ? '' : '/'.$path));
})->where('path', '.*');
