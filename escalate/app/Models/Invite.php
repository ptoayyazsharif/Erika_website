<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One invitation to the beta.
 *
 * Nothing here is mass-assignable. Every field is set by the CLI command that
 * mints the invite or by claim() below — none of it ever comes from a request,
 * and `claimed_by` in particular decides who an invite is spent on.
 */
class Invite extends Model
{
    protected $fillable = [];

    /**
     * The alphabet codes are drawn from.
     *
     * No O, 0, I, 1 or L. These get read down a phone, typed off a screenshot,
     * and copied out of a text message, and "was that an oh or a zero" is a
     * support conversation nobody needs during a beta. 30 characters over 12
     * places is about 59 bits — unguessable against a register route capped at
     * six attempts an hour.
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    /* ── minting ─────────────────────────────────────────────────────────── */

    /** A fresh invite, with a code in ABCD-EFGH-JKMN form. */
    public static function mint(?string $email = null, ?string $note = null, ?int $expiresInDays = 30): self
    {
        $invite = new self;

        $invite->forceFill([
            'code'       => self::freshCode(),
            'email'      => $email ? Str::lower(trim($email)) : null,
            'note'       => $note,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ])->save();

        return $invite;
    }

    private static function freshCode(): string
    {
        do {
            $body = collect(range(1, 12))
                ->map(fn () => self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)])
                ->implode('');

            $code = implode('-', str_split($body, 4));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * The canonical form of a code as typed.
     *
     * People paste these with stray spaces, in lower case, and with the dashes
     * dropped. All three should work — refusing them teaches the person that
     * they were sent a broken code, which is a miserable first impression of an
     * app they were invited to.
     */
    public static function normalise(string $code): string
    {
        $bare = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code));

        return strlen($bare) === 12
            ? implode('-', str_split($bare, 4))
            : $bare;
    }

    /* ── spending ────────────────────────────────────────────────────────── */

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Bound invites only open for the address they were minted for. */
    public function matchesEmail(string $email): bool
    {
        return $this->email === null || $this->email === Str::lower(trim($email));
    }

    public function isUsable(?string $email = null): bool
    {
        return ! $this->isClaimed()
            && ! $this->isExpired()
            && ($email === null || $this->matchesEmail($email));
    }

    /**
     * Spend this invite on a user, once and only once.
     *
     * A conditional UPDATE rather than `if (! $this->isClaimed()) { … save(); }`.
     * The read-then-write version loses the race that actually happens: a code
     * posted to a group chat, two people pressing Create at the same moment,
     * both reading claimed_at as null, both saving. The `whereNull` puts the
     * check and the write in one statement, so the database decides, and the
     * loser gets false rather than a second account.
     *
     * @return bool true if this call is the one that spent it
     */
    public function claim(User $user): bool
    {
        $won = static::query()
            ->whereKey($this->getKey())
            ->whereNull('claimed_at')
            ->update([
                'claimed_by' => $user->id,
                'claimed_at' => now(),
                'updated_at' => now(),
            ]) === 1;

        if ($won) {
            $this->forceFill([
                'claimed_by' => $user->id,
                'claimed_at' => now(),
            ])->syncOriginal();
        }

        return $won;
    }

    /** The signup link to send someone. */
    public function url(): string
    {
        return route('register', ['invite' => $this->code]);
    }
}
