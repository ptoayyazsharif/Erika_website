<?php

use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| Rules this file follows, so that "is this route protected?" is answerable by
| reading it rather than by tracing controllers:
|
|   - Exactly three routes are public: login, register, and the offline page.
|     Everything else lives inside the 'auth' group.
|   - Route model binding is scoped wherever a model belongs to a user, so
|     /desires/{desire} cannot load someone else's row even before the policy
|     runs.
|   - Admin routes sit behind 'auth' AND 'admin'. The admin middleware 404s
|     rather than 403s, so the area is invisible to anyone without the role.
|
*/

Route::view('/offline', 'offline')->name('offline');

/* ── guests ──────────────────────────────────────────────────────────────── */

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,60')
        ->name('register.store');
});

Route::get('/', fn () => redirect()->route(auth()->check() ? 'today' : 'login'));

/* ── signed in ───────────────────────────────────────────────────────────── */

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Placeholders so the navigation resolves while the feature slices land.
    // Each is replaced by its real controller in the commit that builds it.
    Route::view('/today', 'placeholder', ['heading' => 'Today'])->name('today');
    Route::view('/stories', 'placeholder', ['heading' => 'My Stories'])->name('stories.index');
    Route::view('/desires', 'placeholder', ['heading' => 'Desires'])->name('desires.index');
    Route::view('/gratitude', 'placeholder', ['heading' => 'Gratitude'])->name('gratitude.index');
    Route::view('/rewinds', 'placeholder', ['heading' => 'My Rewinds'])->name('rewinds.index');
    Route::view('/journey', 'placeholder', ['heading' => 'My Journey'])->name('journey');
    Route::view('/world', 'placeholder', ['heading' => 'My World'])->name('world.edit');

    /* admin door — reachable only by an admin, invisible to everyone else */
    Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminSessionController::class, 'store'])
        ->middleware('throttle:10,15')
        ->name('admin.login.store');
    Route::post('/admin/leave', [AdminSessionController::class, 'destroy'])->name('admin.leave');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('/', 'placeholder', ['heading' => 'Admin'])->name('dashboard');
    });
});
