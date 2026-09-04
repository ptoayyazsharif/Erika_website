<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAnnouncementEmail;
use App\Jobs\SendAnnouncementPush;
use App\Models\Announcement;
use App\Models\PushSubscription;
use App\Models\User;
use App\Support\Push;
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

            // Devices, not people: one person with a phone and a laptop is two.
            // Shown so an announcement is not sent as a notification into an
            // audience of nobody, which is what a beta where nobody installed
            // the app looks like.
            'devices'       => PushSubscription::query()->reachable()->count(),
            'pushPossible'  => Push::configured(),
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
            'send_push'    => $request->boolean('send_push'),
            'published_at' => now(),
            'created_by'   => $request->user()->id,
        ])->save();

        // Ticking a box records an intention; it does not send anything. Both
        // sending buttons are a separate, deliberate press, so the message says
        // which presses are still waiting rather than implying it has gone.
        $waiting = collect([
            $announcement->send_email ? 'email it' : null,
            $announcement->send_push ? 'send it as a notification' : null,
        ])->filter()->values();

        return redirect()->route('admin.announcements')->with('status',
            $waiting->isNotEmpty()
                ? 'Written. Press the button below to '.$waiting->join(' and ').' — nothing has gone out yet.'
                : ($announcement->show_in_app
                    ? 'Written. It is showing in the app.'
                    : 'Written, but it is not showing anywhere and nothing will be sent.'));
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

    /**
     * Push it, once.
     *
     * Same guard as send(), before the work rather than after it, for a sharper
     * version of the same reason: a duplicate email sits in an inbox looking
     * like a duplicate, a duplicate notification interrupts somebody twice.
     *
     * One job for the whole batch — SendAnnouncementPush says why that differs
     * from the email path.
     */
    public function push(Request $request, Announcement $announcement): RedirectResponse
    {
        if ($announcement->wasPushed()) {
            return back()->withErrors(['announcement' =>
                'That has already gone out as a notification, '.$announcement->pushed_at->diffForHumans().'.']);
        }

        if (! Push::configured()) {
            return back()->withErrors(['announcement' =>
                'Notifications are not set up on this server, so nothing was sent. '.
                'VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY have to be set.']);
        }

        $devices = PushSubscription::query()->reachable()->count();

        if ($devices === 0) {
            return back()->withErrors(['announcement' =>
                'No device has notifications switched on yet, so there is nobody to send to.']);
        }

        $announcement->forceFill(['pushed_at' => now(), 'send_push' => true])->save();

        SendAnnouncementPush::dispatch($announcement);

        return back()->with('status',
            "Queued for {$devices} ".str('device')->plural($devices).'. Anybody who switched notifications off was skipped.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('status', 'Deleted. It has gone from the app; any email already sent has been sent.');
    }
}
