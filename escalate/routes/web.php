<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DesireController;
use App\Http\Controllers\GratitudeController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\WorldController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| Rules this file follows, so that "is this route protected?" is answerable by
| reading it rather than by tracing controllers:
|
|   - Exactly four routes are public: login, register, the offline page, and
|     the privacy disclosure — which must be readable before signing up.
|     Everything else lives inside the 'auth' group.
|   - Route model binding is deliberately NOT scoped. /desires/{desire} will
|     resolve any user's row, and every action re-checks user_id itself — see
|     the mine() helpers. That is the more robust of the two patterns: it keeps
|     working when a route is renamed or a binding is changed, whereas scoped
|     bindings silently stop protecting you.
|   - Admin routes sit behind 'auth' AND 'admin'. The admin middleware 404s
|     rather than 403s, so the area is invisible to anyone without the role.
|
*/

Route::view('/offline', 'offline')->name('offline');

/* Public because someone must be able to read it BEFORE creating an account —
   that is the whole point of a disclosure. */
Route::view('/privacy', 'privacy')->name('privacy');

/* ── guests ──────────────────────────────────────────────────────────────── */

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    /* Forgotten passwords. Throttled hard: the email step is an unauthenticated
       endpoint that sends mail, so it is both an enumeration surface and a way
       to use this app to spam a third party. */
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:5,60')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:10,60')->name('password.update');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,60')
        ->name('register.store');
});

Route::get('/', fn () => redirect()->route(auth()->check() ? 'today' : 'login'));

/* ── signed in ───────────────────────────────────────────────────────────── */

Route::middleware(['auth', 'not-suspended'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /* Account — taking a copy, and leaving. Both re-confirm the password. */
    Route::get('/account', [AccountController::class, 'show'])->name('account.index');
    Route::post('/account/export', [AccountController::class, 'export'])
        ->middleware('throttle:6,60')->name('account.export');
    Route::delete('/account', [AccountController::class, 'destroy'])
        ->middleware('throttle:6,60')->name('account.destroy');

    /* My World */
    Route::get('/world', [WorldController::class, 'edit'])->name('world.edit');
    Route::put('/world', [WorldController::class, 'update'])->name('world.update');
    Route::post('/world/theme', [WorldController::class, 'theme'])->name('world.theme');

    /* Desires + the Manifestation Archive */
    Route::get('/desires', [DesireController::class, 'index'])->name('desires.index');
    Route::get('/desires/create', [DesireController::class, 'create'])->name('desires.create');
    Route::post('/desires', [DesireController::class, 'store'])->name('desires.store');
    Route::get('/desires/{desire}', [DesireController::class, 'show'])->name('desires.show');
    Route::get('/desires/{desire}/edit', [DesireController::class, 'edit'])->name('desires.edit');
    Route::put('/desires/{desire}', [DesireController::class, 'update'])->name('desires.update');
    Route::patch('/desires/{desire}/status', [DesireController::class, 'status'])->name('desires.status');
    Route::delete('/desires/{desire}', [DesireController::class, 'destroy'])->name('desires.destroy');

    /* Stories. Generation is rate-limited on top of the per-user daily quota —
       the quota protects the bill, the throttle protects the queue. */
    Route::get('/stories', [StoryController::class, 'index'])->name('stories.index');
    Route::post('/desires/{desire}/stories', [StoryController::class, 'store'])
        ->middleware('throttle:12,60')->name('stories.store');
    Route::get('/stories/{story}', [StoryController::class, 'show'])->name('stories.show');
    Route::get('/stories/{story}/state', [StoryController::class, 'state'])->name('stories.state');
    Route::post('/stories/{story}/narrate', [StoryController::class, 'narrate'])
        ->middleware('throttle:12,60')->name('stories.narrate');
    Route::post('/stories/{story}/regenerate', [StoryController::class, 'regenerate'])
        ->middleware('throttle:12,60')->name('stories.regenerate');
    Route::get('/stories/{story}/edit', [StoryController::class, 'edit'])->name('stories.edit');
    Route::put('/stories/{story}', [StoryController::class, 'update'])->name('stories.update');
    Route::post('/stories/{story}/favourite', [StoryController::class, 'favourite'])->name('stories.favourite');
    Route::post('/stories/{story}/played', [StoryController::class, 'played'])->name('stories.played');
    Route::delete('/stories/{story}', [StoryController::class, 'destroy'])->name('stories.destroy');

    /* Private media. The only route that reads the private disk. */
    Route::get('/media/narration/{narration}', [MediaController::class, 'narration'])->name('media.narration');
    Route::get('/media/image/{image}', [MediaController::class, 'image'])->name('media.image');

    /* Gratitude */
    Route::get('/gratitude', [GratitudeController::class, 'index'])->name('gratitude.index');
    Route::post('/gratitude', [GratitudeController::class, 'store'])->name('gratitude.store');
    Route::put('/gratitude/{entry}', [GratitudeController::class, 'update'])->name('gratitude.update');
    Route::delete('/gratitude/{entry}', [GratitudeController::class, 'destroy'])->name('gratitude.destroy');

    /* The screen every session lands on, so it is the one screen that cannot
       be a placeholder — see TodayController. */
    Route::get('/today', TodayController::class)->name('today');

    /* Everything that has happened, in the order it happened. */
    Route::get('/journey', JourneyController::class)->name('journey');

    /* Still to build — the navigation resolves so the shell stays whole. */
    Route::view('/rewinds', 'placeholder', ['heading' => 'My Rewinds'])->name('rewinds.index');

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
