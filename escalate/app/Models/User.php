<?php

namespace App\Models;

use App\Support\Plan;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

/**
 * MustVerifyEmail is what makes `event(new Registered($user))` in the register
 * controller actually send something — the framework's own listener is bound to
 * that event and checks for this interface.
 *
 * It does NOT gate the whole app. The 'verified' middleware is applied to the
 * four routes that spend money and nowhere else, so an unverified person can
 * still sign in, fill in My World and name a desire. Locking someone out of
 * their own account because a confirmation email went to spam is a worse
 * failure than the one being prevented.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable;

    /**
     * `role` is absent on purpose. Privilege must never be assignable from
     * request input — an admin is made by `php artisan escalate:make-admin`,
     * never by a form. Same for the suspension and login-tracking columns, and
     * for `plan_override`, which is an administrator granting paid access and
     * so is exactly as sensitive as `role`.
     */
    protected $fillable = ['name', 'email', 'password'];

    /*
    | `role`, `suspended_at` and `last_login_ip` are hidden as well as the
    | credentials. Nothing serialises a User to a browser today, but `role` is
    | the field the entire admin model rests on and a client has no business
    | seeing it or its own last login IP.
    */
    protected $hidden = [
        'password', 'remember_token', 'role', 'plan_override', 'suspended_at', 'last_login_ip',
        // Cashier's columns. `stripe_id` is a customer handle rather than a
        // secret, but it is the key to a Stripe dashboard record and nothing in
        // this app has any reason to render it.
        'stripe_id', 'pm_type', 'pm_last_four',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_login_at'     => 'datetime',
            'suspended_at'      => 'datetime',
        ];
    }

    /* ── role ────────────────────────────────────────────────────────────── */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * The administrators who can actually act.
     *
     * Suspended is excluded deliberately. Suspending an administrator is how
     * their access is taken away, so continuing to post applicants' answers to
     * their inbox would leave a hole open that the admin panel has already
     * closed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     */
    public function scopeAdmins($query): void
    {
        $query->where('role', 'admin')->whereNull('suspended_at');
    }

    /**
     * Whether they want announcement emails.
     *
     * Only announcements. An invite, a password reset or a confirmation is a
     * reply to something the person did, and still reaches somebody who opted
     * out of the newsletter — conflating the two is how an opt-out ends up
     * swallowing the email a person is actually waiting for.
     *
     * Defaults to true when there is no profile row, which is the state a very
     * old account or a half-finished sign-up can be in.
     */
    public function wantsAnnouncementEmails(): bool
    {
        return (bool) ($this->profile?->announcement_emails ?? true);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /* ── relationships ───────────────────────────────────────────────────── */

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function circle(): HasMany
    {
        return $this->hasMany(CirclePerson::class)->orderBy('position');
    }

    public function desires(): HasMany
    {
        return $this->hasMany(Desire::class)->latest();
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class)->latest();
    }

    public function narrations(): HasMany
    {
        return $this->hasMany(Narration::class);
    }

    public function desireImages(): HasMany
    {
        return $this->hasMany(DesireImage::class);
    }

    public function affirmationSets(): HasMany
    {
        return $this->hasMany(AffirmationSet::class)->latest('for_date');
    }

    /** Their answers to the day-seven survey, if they have given any. */
    public function feedbackResponse(): HasOne
    {
        return $this->hasOne(FeedbackResponse::class);
    }

    public function affirmations(): HasMany
    {
        return $this->hasMany(Affirmation::class);
    }

    public function gratitudeEntries(): HasMany
    {
        return $this->hasMany(GratitudeEntry::class)->latest('for_date');
    }

    public function rewinds(): HasMany
    {
        return $this->hasMany(Rewind::class)->latest();
    }

    public function reflections(): HasMany
    {
        return $this->hasMany(Reflection::class)->latest('year')->latest('quarter');
    }

    public function aiEvents(): HasMany
    {
        return $this->hasMany(AiEvent::class);
    }

    /* ── convenience ─────────────────────────────────────────────────────── */

    /**
     * The profile, created on demand so no screen has to null-check it.
     *
     * firstOrCreate plus setRelation, because create() does not populate the
     * cached relation — so a second world() call on the same instance tried a
     * second INSERT and hit the unique index on profiles.user_id. StoryWriter
     * calls this twice in one job run.
     */
    public function world(): Profile
    {
        if ($this->profile) {
            return $this->profile;
        }

        $profile = $this->profile()->firstOrCreate([]);
        $this->setRelation('profile', $profile);

        return $profile;
    }

    /* ── billing ─────────────────────────────────────────────────────────── */

    /** The plan key this user is entitled to right now. See App\Support\Plan. */
    public function planKey(): string
    {
        return Plan::for($this);
    }

    public function plan(): array
    {
        return Plan::config($this->planKey());
    }

    public function onFreePlan(): bool
    {
        return $this->planKey() === Plan::FREE;
    }

    /**
     * The email Stripe should have on file.
     *
     * Cashier defaults to `$this->email`, which is right, but stating it means
     * a future change to how this app stores email cannot silently desynchronise
     * the customer record from the account.
     */
    public function stripeEmail(): ?string
    {
        return $this->email;
    }

    public function stripeName(): ?string
    {
        return $this->name;
    }

    /** What the app should call this person in generated text. */
    public function callMe(): string
    {
        return trim((string) ($this->profile?->preferred_name ?: $this->name)) ?: 'friend';
    }
}
