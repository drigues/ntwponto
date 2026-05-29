<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\RelatorioPdfController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Livewire\PainelPonto;
use App\Livewire\RelatorioIndividual;
use App\Models\Marcacao;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/login');
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

// Auth — guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/password/forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/forgot', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:3,10');
});

// Auth — authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');
});

// App — authenticated + password changed
Route::middleware(['auth', EnsurePasswordIsChanged::class])->group(function () {
    Route::get('/ponto', PainelPonto::class)->name('ponto');
    Route::get('/relatorio', RelatorioIndividual::class)->name('relatorio');
    Route::get('/relatorio/pdf', [RelatorioPdfController::class, 'inline'])->name('relatorio.pdf');
    Route::post('/relatorio/pdf/async', [RelatorioPdfController::class, 'async'])->name('relatorio.pdf.async');

    Route::get('/pdf/{filename}', [RelatorioPdfController::class, 'download'])
        ->name('pdf.download')
        ->middleware('signed');

    Route::get('/marcacao/{marcacao}/foto', function (Marcacao $marcacao) {
        if (! $marcacao->foto_path || ! Storage::exists($marcacao->foto_path)) {
            abort(404);
        }

        return Storage::download($marcacao->foto_path);
    })->name('marcacao.foto')->middleware('signed');
});
