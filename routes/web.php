<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

    
Route::view('items', 'pages.items.all-items')
    ->name('items');

require __DIR__.'/admin.php';

require __DIR__.'/settings.php';
