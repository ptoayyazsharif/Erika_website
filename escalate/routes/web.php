<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AffirmationController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplications;
use App\Http\Controllers\Admin\BetaController as AdminBeta;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedback;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\InviteController as AdminInvites;
use App\Http\Controllers\Admin\PlanController as AdminPlans;
use App\Http\Controllers\Admin\SettingsController as AdminSettings;
use App\Http\Controllers\Admin\UserController as AdminUsers;
use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DesireController;
use App\Http\Controllers\GratitudeController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\RewindController;
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
|   - Exactly four routes are public here: login, register, the offline page,
|     and the privacy disclosure — which must be readable before signing up.
|     Cashier registers one more outside this file, POST /stripe/webhook, which
|     is public by necessity: Stripe is not a signed-in user. It authenticates
|     by signature instead — see STRIPE_WEBHOOK_SECRET — and is exempted from
|     CSRF in bootstrap/app.php.
|     Everything else lives inside the 'auth' group.
|   - Route model binding is deliberately NOT scoped. /desires/{desire} will
|     resolve any user's row, and every action re-checks user_id itself — see
|     the mine() helpers. That is the more robust of the two patterns: it keeps
|     working when a route is renamed or a binding is changed, whereas scoped
|     bindings silently stop protecting you.
|   - Admin routes sit behind 'auth' AND 'admin'. The admin middleware 404s
|     rather than 403s, so the area is invisible to anyone without the role.
|   - The four routes that call a paid provider — writing a reading, narrating
|     one, rewriting one, writing a Rewind — additionally carry
|     'verified-email'. Nothing else does: an unconfirmed account can still
|     read, plan and write, because locking someone out of their own journal
|     over a spam filter is a worse outcome than the one being prevented.
|     That middleware reads the config flag per request rather than here, so
|     the setting cannot be frozen into `route:cache` — see the class.
|
*/

Route::view('/offline', 'offline')->name('offline');

/* Public because someone must be able to read it BEFORE creating an account —
   that is the whole point of a disclosure. */
Route::view('/privacy', 'privacy')->name('privacy');

/* The private beta application.
 *
 * Deliberately outside the 'guest' group: these links go out on social, and
 * somebody already signed in who taps one should read the page rather than be
 * bounced to Today with no explanation.
 *
 * Throttled because it writes a row and sends mail on behalf of someone who has
 * proved nothing — but not as hard as /forgot-password, and the difference is
 * deliberate.
 *
 * These limits key on IP, and mobile carriers put thousands of unrelated people
 * behind one address. A password reset is rare per person, so five an hour
 * there never collides. An application form during a launch push is the
 * opposite: a burst of strangers arriving from Instagram, most of them on
 * mobile, many sharing a carrier NAT. At five an hour the sixth real applicant
 * meets a 429 and does not come back.
 *
 * Twenty still stops a script, and what a flood could achieve is bounded
 * anyway: the honeypot catches the naive case, a repeat address updates its
 * own row rather than making a new one, and everything that lands is a row an
 * administrator reads before it grants anything. */
Route::get('/apply', [ApplicationController::class, 'create'])->name('apply');
Route::post('/apply', [ApplicationController::class, 'store'])
    ->middleware('throttle:20,60')->name('apply.store');

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

/* The front door.
 *
 * A signed-in person still goes straight to Today — the app is the thing they
 * came for. Everyone else now gets the landing page instead of being dropped
 * on a login form with no idea what this is. */
Route::get('/', fn () => auth()->check()
    ? redirect()->route('today')
    : response()->view('landing'))->name('landing');

/* ── signed in ───────────────────────────────────────────────────────────── */

Route::middleware(['auth', 'not-suspended'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /* Confirming an email address. 'verification.notice' is the name the
       'verified' middleware redirects to, so it is not free to rename. The
       verify link is signed by the framework and checked by
       EmailVerificationRequest; the resend sends mail, so it is throttled. */
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,60'])->name('verification.verify');
    Route::post('/email/verify', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:5,60')->name('verification.send');

    /*
     | Billing.
     |
     | Three routes, and none of them touches a card: Checkout and the Billing
     | Portal are Stripe's own pages. The Stripe webhook is NOT here — it is
     | registered by Cashier outside the 'auth' group, because Stripe is not
     | signed in. See bootstrap/app.php for its CSRF exemption.
     |
     | Throttled because each one is an outbound API call to Stripe, and a
     | hammered checkout route creates real customer records.
     */
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])
        ->middleware('throttle:12,60')->name('billing.checkout');
    // Undo a scheduled downgrade. Same throttle: it is a Stripe write too.
    Route::post('/billing/keep-plan', [BillingController::class, 'keepPlan'])
        ->middleware('throttle:12,60')->name('billing.keep');
    Route::get('/billing/portal', [BillingController::class, 'portal'])
        ->middleware('throttle:12,60')->name('billing.portal');

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
        ->middleware(['verified-email', 'throttle:12,60'])->name('stories.store');
    /* The day-seven survey. No verified-email gate and no throttle beyond the
       ordinary: answering costs nothing and reaches no provider. */
    Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::post('/feedback/later', [FeedbackController::class, 'defer'])->name('feedback.defer');

    /* Daily affirmation cards. `verified-email` and the same throttle as the
       other two paid routes: drawing a set calls the writing provider. */
    Route::get('/affirmations', [AffirmationController::class, 'index'])->name('affirmations');
    Route::post('/affirmations', [AffirmationController::class, 'store'])
        ->middleware(['verified-email', 'throttle:12,60'])->name('affirmations.store');
    Route::get('/affirmations/state', [AffirmationController::class, 'state'])->name('affirmations.state');
    Route::post('/affirmations/{affirmation}/favourite', [AffirmationController::class, 'favourite'])
        ->name('affirmations.favourite');

    Route::get('/stories/{story}', [StoryController::class, 'show'])->name('stories.show');
    Route::get('/stories/{story}/state', [StoryController::class, 'state'])->name('stories.state');
    Route::post('/stories/{story}/narrate', [StoryController::class, 'narrate'])
        ->middleware(['verified-email', 'throttle:12,60'])->name('stories.narrate');
    Route::post('/stories/{story}/regenerate', [StoryController::class, 'regenerate'])
        ->middleware(['verified-email', 'throttle:12,60'])->name('stories.regenerate');
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

    /* Rewinds — looking back at a desire once it has finished moving. The
       reflection belongs to a desire; the Rewind it produces stands alone. */
    Route::get('/rewinds', [RewindController::class, 'index'])->name('rewinds.index');
    Route::get('/desires/{desire}/rewind', [RewindController::class, 'create'])->name('rewinds.create');
    Route::post('/desires/{desire}/rewind', [RewindController::class, 'store'])->name('rewinds.store');
    Route::get('/rewinds/{rewind}', [RewindController::class, 'show'])->name('rewinds.show');
    Route::post('/rewinds/{rewind}/write', [RewindController::class, 'generate'])
        ->middleware(['verified-email', 'throttle:12,60'])->name('rewinds.generate');
    Route::delete('/rewinds/{rewind}', [RewindController::class, 'destroy'])->name('rewinds.destroy');

    /* admin door — reachable only by an admin, invisible to everyone else */
    Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminSessionController::class, 'store'])
        ->middleware('throttle:10,15')
        ->name('admin.login.store');
    Route::post('/admin/leave', [AdminSessionController::class, 'destroy'])->name('admin.leave');

    /*
     | The admin area.
     |
     | Behind 'auth', 'not-suspended' AND 'admin' — and 'admin' means the role
     | plus a second password confirmation with a two-hour idle expiry, so an
     | admin who leaves a laptop open on Today has not left this open. A failed
     | check 404s rather than 403s; there is no reason to confirm to a prober
     | that any of this exists.
     |
     | Nothing here reads user content. See Admin\UserController on why that is
     | a rule rather than an oversight.
     */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboard::class)->name('dashboard');

        Route::get('/settings', [AdminSettings::class, 'edit'])->name('settings');
        Route::put('/settings', [AdminSettings::class, 'update'])->name('settings.update');
        Route::post('/settings/reset', [AdminSettings::class, 'reset'])->name('settings.reset');
        // Read-only against Stripe; throttled because it is an outbound call.
        Route::post('/settings/stripe-check', [AdminSettings::class, 'stripe'])
            ->middleware('throttle:20,10')->name('settings.stripe');
        // Sends real mail, to the admin's own address only. Throttled hard.
        Route::post('/settings/mail-test', [AdminSettings::class, 'testMail'])
            ->middleware('throttle:6,10')->name('settings.mail');

        Route::get('/users', [AdminUsers::class, 'index'])->name('users');
        Route::get('/users/{user}', [AdminUsers::class, 'show'])->name('users.show');
        Route::patch('/users/{user}/plan', [AdminUsers::class, 'plan'])->name('users.plan');
        Route::post('/users/{user}/suspend', [AdminUsers::class, 'suspend'])->name('users.suspend');

        Route::get('/plans', [AdminPlans::class, 'index'])->name('plans');
        Route::get('/plans/create', [AdminPlans::class, 'create'])->name('plans.create');
        Route::post('/plans', [AdminPlans::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [AdminPlans::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [AdminPlans::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [AdminPlans::class, 'destroy'])->name('plans.destroy');

        Route::get('/beta', AdminBeta::class)->name('beta');
        Route::get('/feedback', AdminFeedback::class)->name('feedback');

        Route::get('/applications', [AdminApplications::class, 'index'])->name('applications');
        Route::get('/applications/{application}', [AdminApplications::class, 'show'])->name('applications.show');
        Route::post('/applications/{application}/select', [AdminApplications::class, 'select'])->name('applications.select');
        Route::post('/applications/{application}/decline', [AdminApplications::class, 'decline'])->name('applications.decline');

        Route::get('/invites', [AdminInvites::class, 'index'])->name('invites');
        Route::post('/invites', [AdminInvites::class, 'store'])->name('invites.store');
        Route::delete('/invites/{invite}', [AdminInvites::class, 'destroy'])->name('invites.destroy');
    });
});
