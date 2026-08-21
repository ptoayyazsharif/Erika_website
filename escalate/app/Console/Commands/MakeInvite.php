<?php

namespace App\Console\Commands;

use App\Models\Invite;
use Illuminate\Console\Command;

/**
 * Mint invites for the beta.
 *
 * A CLI command for the same reason MakeAdmin is one: handing out access is
 * something the person running the server does, not something reachable from a
 * form. There is no web route that creates an invite, so there is no web route
 * to find, guess or abuse.
 */
class MakeInvite extends Command
{
    protected $signature = 'escalate:invite
        {--count=1 : How many to mint}
        {--email= : Bind the invite to one address, so a forwarded code is useless}
        {--note= : A reminder for you about who this is for; never shown to them}
        {--days= : Days until it expires; 0 for never. Defaults to config.}';

    protected $description = 'Create invite codes for the closed beta';

    public function handle(): int
    {
        $count = max(1, (int) $this->option('count'));
        $email = $this->option('email');

        if ($email && $count > 1) {
            $this->error('An email-bound invite is for one person. Drop --email or set --count=1.');

            return self::FAILURE;
        }

        $days = $this->option('days') === null
            ? (int) config('escalate.beta.invite_days')
            : (int) $this->option('days');

        $invites = collect(range(1, $count))->map(
            fn () => Invite::mint($email, $this->option('note'), $days ?: null),
        );

        $this->newLine();
        $this->table(
            ['Code', 'For', 'Expires', 'Link'],
            $invites->map(fn (Invite $i) => [
                $i->code,
                $i->email ?? 'anyone',
                $i->expires_at?->format('j M Y') ?? 'never',
                $i->url(),
            ])->all(),
        );

        if (! config('escalate.beta.invite_only')) {
            $this->newLine();
            $this->warn('Note: INVITE_ONLY is false, so registration is currently open to anyone.');
            $this->warn('These codes will work, but nothing is requiring them.');
        }

        return self::SUCCESS;
    }
}
