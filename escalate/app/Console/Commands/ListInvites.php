<?php

namespace App\Console\Commands;

use App\Models\Invite;
use Illuminate\Console\Command;

/** Who has been invited, and who has actually walked through the door. */
class ListInvites extends Command
{
    protected $signature = 'escalate:invites {--open : Only ones still unclaimed and unexpired}';

    protected $description = 'List beta invites and their state';

    public function handle(): int
    {
        $all = Invite::with('claimant')->latest()->get();
        $invites = $this->option('open') ? $all->filter->isUsable() : $all;

        if ($invites->isEmpty()) {
            $this->info($this->option('open')
                ? 'No invites left to hand out. Mint more with escalate:invite.'
                : 'No invites yet. Mint some with escalate:invite.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Code', 'State', 'For', 'Claimed by', 'Note'],
            $invites->map(fn (Invite $i) => [
                $i->code,
                match (true) {
                    $i->isClaimed() => 'used '.$i->claimed_at->diffForHumans(),
                    $i->isExpired() => 'expired',
                    default         => 'open',
                },
                $i->email ?? 'anyone',
                // The account may since have been deleted; the invite stays used.
                $i->isClaimed() ? ($i->claimant?->email ?? 'deleted account') : '—',
                $i->note ?? '',
            ])->all(),
        );

        // Counted off the unfiltered set, so --open does not report its own
        // filtered count as the total and make it look like the used invites
        // have vanished.
        $this->line('  '.$all->filter->isUsable()->count()." still open, {$all->count()} total.");

        return self::SUCCESS;
    }
}
