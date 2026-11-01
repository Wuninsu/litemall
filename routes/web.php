<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Customers;
use App\Livewire\Dashboard;
use App\Livewire\Payments;
use App\Livewire\Products;
use App\Livewire\Sales;
use App\Livewire\Users;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::livewire('/dashboard', Dashboard::class)->name('dashboard');
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::livewire('/customers', Customers\Index::class)
        ->middleware('can:viewAny,'.Customer::class)
        ->name('customers.index');

    Route::livewire('/products', Products\Index::class)
        ->middleware('can:viewAny,'.Product::class)
        ->name('products.index');

    Route::livewire('/products/{product}', Products\Show::class)
        ->middleware('can:viewAny,'.Product::class)
        ->name('products.show');

    Route::livewire('/sales', Sales\Index::class)
        ->middleware('can:viewAny,'.Sale::class)
        ->name('sales.index');

    Route::livewire('/sales/new', Sales\Create::class)
        ->middleware('can:create,'.Sale::class)
        ->name('sales.create');

    Route::livewire('/sales/{sale}', Sales\Show::class)
        ->middleware('can:viewAny,'.Sale::class)
        ->name('sales.show');

    Route::livewire('/payments', Payments\Index::class)
        ->middleware('can:viewAny,'.Payment::class)
        ->name('payments.index');

    Route::middleware('can:viewAny,'.User::class)->group(function () {
        Route::livewire('/users', Users\Index::class)->name('users.index');
    });
});
