<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware(['auth', 'auth.session', 'ativo'])->group(function () {
    Route::view('/', 'inicio')->name('inicio');

    Route::get('/conta', [ContaController::class, 'edit'])->name('conta');
    Route::put('/conta/senha', [ContaController::class, 'updateSenha'])->name('conta.senha');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
