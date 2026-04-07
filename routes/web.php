<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RentalController;

Route::redirect('/', 'home');
Route::livewire('home', 'pages::frontend.home')->name('home');

Route::livewire('items', 'pages::frontend.all-items')->name('items');

Route::livewire('/items/{slug}', 'pages::frontend.show-item')->name('items.show');

Route::livewire('/checkout', 'pages::frontend.checkout')->name('checkout');

Route::livewire('/my-rentals', 'pages::frontend.my-rentals')->name('rentals')->middleware('auth');

Route::post('/stripe/webhook', [WebhookController::class, 'handle'])
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:60,1');

Route::middleware('auth')->group(function () {
    Route::get('/rentals/{rental}/invoice', [RentalController::class, 'downloadInvoice'])
    ->name('rentals.invoice')
    ->can('view', 'rental');
});
require __DIR__.'/admin.php';

require __DIR__.'/settings.php';
