<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes an account, and everything it left behind.
 *
 * The reason this is a service rather than `$user->delete()`:
 *
 * Every child table is `cascadeOnDelete` at the DATABASE level. Database
 * cascades do not fire Eloquent model events — so `Narration::booted()` and
 * `Rewind::booted()`, the two hooks that delete files from disk, never run. A
 * plain delete would wipe every row and leave every mp3 sitting in
 * `storage/app/escalate/audio/{user_id}/` forever: spoken recordings of a
 * deleted person's journal, in a directory named after them.
 *
 * So files go first, explicitly, and the directory is removed wholesale
 * afterwards to catch anything orphaned by earlier bugs.
 *
 * Sessions are handled by hand too: `sessions.user_id` has an index but no
 * foreign key, so those rows survive a cascade carrying the user id, IP and
 * user agent.
 */
class AccountEraser
{
    public function erase(User $user): void
    {
        $id = $user->id;

        // 1. Files first. If the transaction below fails we would rather have
        //    orphaned rows pointing at missing audio than orphaned audio with
        //    no row to find it by.
        $this->deleteFiles($user);

        // 2. Rows. The cascades do the work; this only has to remove what they
        //    cannot reach.
        DB::transaction(function () use ($user, $id) {
            DB::table('sessions')->where('user_id', $id)->delete();

            // ai_events.user_id is nullOnDelete, so the ledger keeps costs and
            // counts with no way back to a person. That is deliberate — it is
            // needed for billing reconciliation and holds no user content.
            $user->delete();
        });

        // 3. Whatever the directory still holds, whether we knew about it or
        //    not. Earlier versions of the app orphaned files on story deletion.
        Storage::disk('private')->deleteDirectory("audio/{$id}");
        Storage::disk('private')->deleteDirectory("images/{$id}");
    }

    private function deleteFiles(User $user): void
    {
        $disk = Storage::disk('private');

        foreach ($user->narrations()->pluck('path')->filter() as $path) {
            $disk->delete($path);
        }

        foreach ($user->rewinds()->get(['before_image_path', 'after_image_path']) as $rewind) {
            foreach ([$rewind->before_image_path, $rewind->after_image_path] as $path) {
                if (filled($path)) {
                    $disk->delete($path);
                }
            }
        }

        foreach ($user->desireImages()->pluck('path')->filter() as $path) {
            $disk->delete($path);
        }
    }

    /**
     * Everything the app holds about a user, as a plain array.
     *
     * Decrypted on the way out — the point of an export is that the person can
     * read it. This is what makes a subject-access request answerable without
     * anyone at the agency opening a database.
     */
    public function export(User $user): array
    {
        $profile = $user->profile;

        return [
            'exported_at' => now()->toIso8601String(),
            'note' => 'Everything Escalate holds about you. Audio files are not included; download those from the app.',

            'account' => [
                'name'          => $user->name,
                'email'         => $user->email,
                'joined'        => $user->created_at?->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'cohort'        => $user->cohort,

                // The dates the app was opened. Held so a beta can tell whether
                // people came back; exported because it is data about them, and
                // "everything Escalate holds about you" has to mean it. Removed
                // with the account by the cascade on activity_days.user_id.
                'days_you_opened_the_app' => DB::table('activity_days')
                    ->where('user_id', $user->id)
                    ->orderBy('day')
                    ->pluck('day')
                    ->all(),
            ],

            'my_world' => $profile ? [
                'preferred_name' => $profile->preferred_name,
                'city'           => $profile->city,
                'life_context'   => $profile->life_context,
                'anchor'         => $profile->anchor,
                'values'         => $profile->values,
                'voice'          => $profile->voice,
                'theme'          => $profile->theme,
                'story_style'    => $profile->story_style,
                'faith_language' => $profile->faith_language,
                'perspective'    => $profile->perspective,
                'tone'           => $profile->tone,
                'default_length' => $profile->default_length,
                'consented_at'   => $profile->consented_at?->toIso8601String(),

                // A choice they made, so it belongs in "everything Escalate
                // holds about you". Which banners they closed does not — that
                // is interface state nobody would recognise as their data, and
                // padding an export with it buries what matters.
                'announcement_emails' => (bool) $profile->announcement_emails,
            ] : null,

            // details() rather than ->note. `note` is the retired single-detail
            // column: since a person gained a list of details nothing writes to
            // it any more, so this exported an empty field for every person and
            // silently left out everything the user had actually typed about
            // them. An export that quietly omits a category of data is worse
            // than no export — it is what a subject-access request is answered
            // with.
            'circle' => $user->circle->map(fn ($p) => [
                'name'         => $p->name,
                'relationship' => $p->relationship,
                'details'      => $p->details(),
            ])->all(),

            'desires' => $user->desires()->get()->map(fn ($d) => [
                'title'            => $d->title,
                'description'      => $d->description,
                'why_it_matters'   => $d->why_it_matters,
                'non_negotiables'  => $d->non_negotiables,
                'desired_feelings' => $d->desired_feelings,
                'people_involved'  => $d->people_involved,
                'category'         => $d->category,
                'timeframe'        => $d->timeframe,
                'status'           => $d->status,
                'created'          => $d->created_at?->toIso8601String(),
                'completed'        => $d->completed_at?->toIso8601String(),
            ])->all(),

            'stories' => $user->stories()->get()->map(fn ($s) => [
                'title'   => $s->title,
                'text'    => $s->text(),
                'edited'  => $s->isEdited(),
                'words'   => $s->words(),
                'created' => $s->created_at?->toIso8601String(),
                'plays'   => $s->play_count,
            ])->all(),

            'gratitude' => $user->gratitudeEntries()->get()->map(fn ($e) => [
                'date' => $e->for_date?->toDateString(),
                'body' => $e->body,
                'tags' => $e->tags,
            ])->all(),

            'rewinds' => $user->rewinds()->get()->map(fn ($r) => [
                'what_happened'  => $r->what_happened,
                'turning_points' => $r->turning_points,
                'redirections'   => $r->redirections,
                'people'         => $r->people,
                'lessons'        => $r->lessons,
                'gratitude'      => $r->gratitude,
                'narrative'      => $r->narrative,
                'created'        => $r->created_at?->toIso8601String(),
            ])->all(),

            'reflections' => $user->reflections()->get()->map(fn ($r) => [
                'period'        => $r->label(),
                'summary'       => $r->summary,
                'shifts'        => $r->shifts,
                'relationships' => $r->relationships,
                'lessons'       => $r->lessons,
            ])->all(),
        ];
    }
}
