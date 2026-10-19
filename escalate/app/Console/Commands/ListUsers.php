<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Who has an account, and which of them can reach the admin area.
 *
 * This exists because of a dead end: escalate:make-admin needs an exact email,
 * and there was no way to find out what the exact emails were without already
 * being in the admin panel — which is the thing you are trying to get into.
 * Guessing wrong just prints "No user with that email", which reads as though
 * the account is missing rather than the address being slightly off.
 *
 * Account metadata only. Nothing here reads a journal, and nothing should be
 * added to it that does.
 */
class ListUsers extends Command
{
    protected $signature = 'escalate:users {--admins : Only accounts holding the admin role}';

    protected $description = 'List accounts and their roles';

    public function handle(): int
    {
        $all = User::orderBy('created_at')->get();
        $users = $this->option('admins') ? $all->where('role', 'admin') : $all;

        if ($users->isEmpty()) {
            $this->info($this->option('admins')
                ? 'No admins yet. Grant the role with escalate:make-admin <email>.'
                : 'No accounts yet.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Email', 'Name', 'Role', 'Email confirmed', 'Joined'],
            $users->map(fn (User $u) => [
                $u->email,
                $u->name,
                $u->role === 'admin' ? 'admin' : '—',
                $u->email_verified_at ? 'yes' : 'no',
                $u->created_at->toDateString(),
            ])->all(),
        );

        // Off the unfiltered set, so --admins cannot make it look as though
        // the other accounts have gone.
        $admins = $all->where('role', 'admin')->count();

        $this->line("  {$admins} admin".($admins === 1 ? '' : 's').", {$all->count()} account".($all->count() === 1 ? '' : 's').' in total.');

        if ($admins === 0) {
            $this->newLine();
            $this->warn('  Nobody can reach /admin. Grant the role with:');
            $this->line('    php artisan escalate:make-admin <email from the list above>');
        }

        return self::SUCCESS;
    }
}
