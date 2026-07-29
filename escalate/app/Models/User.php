<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * `role` is absent on purpose. Privilege must never be assignable from
     * request input — an admin is made by `php artisan escalate:make-admin`,
     * never by a form. Same for the suspension and login-tracking columns.
     */
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

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

    /** The profile, created on demand so no screen has to null-check it. */
    public function world(): Profile
    {
        return $this->profile ?: $this->profile()->create();
    }

    /** What the app should call this person in generated text. */
    public function callMe(): string
    {
        return trim((string) ($this->profile?->preferred_name ?: $this->name)) ?: 'friend';
    }
}
