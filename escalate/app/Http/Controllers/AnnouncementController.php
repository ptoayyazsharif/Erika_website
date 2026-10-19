<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * What a reader can do with an announcement: close it, or stop the emails.
 */
class AnnouncementController extends Controller
{
    /**
     * Close the banner, for this person only.
     *
     * insertOrIgnore against the unique index rather than a check-then-insert:
     * two tabs, or a double tap, would otherwise race and one of them would
     * fail on the constraint with a 500 in front of somebody who only wanted to
     * close a banner.
     */
    public function dismiss(Request $request, Announcement $announcement): RedirectResponse
    {
        DB::table('announcement_dismissals')->insertOrIgnore([
            'announcement_id' => $announcement->id,
            'user_id'         => $request->user()->id,
            'created_at'      => now(),
        ]);

        return back();
    }

    /**
     * Stop announcement emails.
     *
     * No auth: this is clicked from a mail client, often on a device nobody is
     * signed in on, and an unsubscribe that first demands a password is an
     * unsubscribe that does not work. The `signed` middleware is what stands in
     * for authentication — the URL carries the app's own signature, so the id
     * in it cannot be edited to opt somebody else out.
     *
     * GET rather than POST because mail clients only make links. That means a
     * scanner prefetching the URL can unsubscribe somebody, which is why the
     * page that follows offers one press to undo it rather than treating the
     * click as final.
     */
    public function unsubscribe(Request $request, User $user): View
    {
        $user->profile()->firstOrCreate([])->forceFill([
            'announcement_emails' => false,
        ])->save();

        return view('unsubscribed', ['person' => $user]);
    }

    /** Put it back, from the page above. Same signature, so the same rules. */
    public function resubscribe(Request $request, User $user): View
    {
        $user->profile()->firstOrCreate([])->forceFill([
            'announcement_emails' => true,
        ])->save();

        return view('unsubscribed', ['person' => $user, 'resubscribed' => true]);
    }
}
