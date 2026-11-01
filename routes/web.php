<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard;
use App\Livewire\Users;
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

    Route::middleware('can:viewAny,'.User::class)->group(function () {
        Route::livewire('/users', Users\Index::class)->name('users.index');
    });
});
