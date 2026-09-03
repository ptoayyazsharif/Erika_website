<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAnnouncementEmail;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Telling everybody something.
 *
 * Writing and publishing are one step: a draft nobody can see is a feature for
 * a newsroom, not for two people running a beta of twenty-five. Emailing is a
 * separate, deliberate second press, because it is the only irreversible thing
 * on this screen.
 */
class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::with('author')
                ->withCount('dismissals')
                ->latest()
                ->get(),
            'audience' => User::whereHas('profile', fn ($q) => $q->where('announcement_emails', true))
                ->orWhereDoesntHave('profile')
                ->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body'  => ['required', 'string', 'max:4000'],
        ]);

        $announcement = new Announcement;

        $announcement->forceFill([
            'title'        => scalar_input($data['title']),
            'body'         => scalar_input($data['body']),
            'show_in_app'  => $request->boolean('show_in_app'),
            'send_email'   => $request->boolean('send_email'),
            'published_at' => now(),
            'created_by'   => $request->user()->id,
        ])->save();

        return redirect()->route('admin.announcements')->with('status',
            $announcement->send_email
                ? 'Written. Press Send to email it — nothing has gone out yet.'
                : 'Written. It is showing in the app.');
    }

    /**
     * Email it, once.
     *
     * `emailed_at` is set BEFORE the jobs are dispatched and checked first.
     * Two people pressing this at the same moment, or one person
     * double-clicking, is the failure with no undo — a hundred people getting
     * the same email twice — so the guard comes before the work rather than
     * after it.
     *
     * One job per recipient: a single bad address must not stop the rest.
     */
    public function send(Request $request, Announcement $announcement): RedirectResponse
    {
        if ($announcement->wasEmailed()) {
            return back()->withErrors(['announcement' =>
                'That has already been emailed, '.$announcement->emailed_at->diffForHumans().'.']);
        }

        $announcement->forceFill(['emailed_at' => now(), 'send_email' => true])->save();

        $sent = 0;

        User::with('profile')
            ->whereNull('suspended_at')
            ->chunkById(200, function ($users) use ($announcement, &$sent) {
                foreach ($users as $user) {
                    if (! $user->wantsAnnouncementEmails()) {
                        continue;
                    }

                    SendAnnouncementEmail::dispatch($announcement, $user);
                    $sent++;
                }
            });

        return back()->with('status',
            "Queued for {$sent} ".str('person')->plural($sent).'. Anybody who opted out was skipped.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('status', 'Deleted. It has gone from the app; any email already sent has been sent.');
    }
}
