<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::redirect('admin', 'admin/dashboard')->name('admin');

    Route::livewire('admin/dashboard', 'pages::admin.dashboard')->name('admin.dashboard');
    Route::livewire('admin/categories', 'pages::admin.categories')->name('categories.edit');
    Route::livewire('admin/inventory', 'pages::admin.inventory')->name('inventory.edit');
    Route::livewire('admin/rentals', 'pages::admin.rentals')->name('rentals.edit');
    Route::livewire('admin/tools', 'pages::admin.tools')->name('tools.edit');
});