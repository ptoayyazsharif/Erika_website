<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
    use HasFactory, Notifiable;

    /**
     * `role` is absent on purpose. Privilege must never be assignable from
     * request input — an admin is made by `php artisan escalate:make-admin`,
     * never by a form. Same for the suspension and login-tracking columns.
     */
    protected $fillable = ['name', 'email', 'password'];

    /*
    | `role`, `suspended_at` and `last_login_ip` are hidden as well as the
    | credentials. Nothing serialises a User to a browser today, but `role` is
    | the field the entire admin model rests on and a client has no business
    | seeing it or its own last login IP.
    */
    protected $hidden = ['password', 'remember_token', 'role', 'suspended_at', 'last_login_ip'];

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

    /** What the app should call this person in generated text. */
    public function callMe(): string
    {
        return trim((string) ($this->profile?->preferred_name ?: $this->name)) ?: 'friend';
    }
}
