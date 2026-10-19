<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * The only way to create an admin.
 *
 * Deliberately a CLI command with no web equivalent: privilege escalation
 * should require shell access to the server, not a form someone might find.
 */
class MakeAdmin extends Command
{
    protected $signature = 'escalate:make-admin {email} {--revoke : Take the role away instead}';

    protected $description = 'Grant or revoke the admin role for a user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user with that email.");

            return self::FAILURE;
        }

        $revoke = $this->option('revoke');
        $user->forceFill(['role' => $revoke ? 'user' : 'admin'])->save();

        $this->info($revoke
            ? "{$user->email} is no longer an admin."
            : "{$user->email} is now an admin. They must confirm their password at /admin/login.");

        return self::SUCCESS;
    }
}
