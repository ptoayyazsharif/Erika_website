<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Notes that somebody opened the app today.
 *
 * Deliberately the smallest thing that answers the question. No path, no time,
 * no user agent — a date and a person. Analytics this is not; it exists so a
 * beta can say whether people came back, and it should never grow into
 * something that could say what they came back to look at.
 *
 * Three things keep it off the critical path:
 *
 *   - GET only. A form post is a page the person already reached by a GET, so
 *     counting both would double the writes and change nothing.
 *   - A cache key per person per day, so this is one INSERT a day rather than
 *     one a request. On a cache miss the insert is ignored by the unique index
 *     anyway; the cache is a cost optimisation, never the correctness.
 *   - Wrapped. Somebody's journal must not fail to load because a metrics row
 *     could not be written.
 */
class RecordActivityDay
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user || ! $request->isMethod('GET')) {
            return $response;
        }

        // After the response is built, so the write never delays a render.
        try {
            $today = now()->toDateString();
            $seen = "activity:{$user->id}:{$today}";

            if (Cache::get($seen)) {
                return $response;
            }

            DB::table('activity_days')->insertOrIgnore([
                'user_id' => $user->id,
                'day'     => $today,
            ]);

            // Until the end of the day, not for a fixed span: a key that
            // outlived midnight would swallow the first visit of the next day,
            // which is precisely the row the habit measure is counting.
            Cache::put($seen, true, now()->endOfDay());
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
