<?php

namespace App\Http\Controllers;

use App\Models\DesireImage;
use App\Models\Narration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The only way to read a file from the private disk.
 *
 * There is no symlink from public/ to storage/, and the 'private' disk has
 * serve=false, so nothing under storage/app/escalate is reachable by URL. Every
 * byte a user hears or sees passes through one of the two methods below, and
 * each one checks ownership before touching the filesystem.
 *
 * Two details that are load-bearing:
 *
 *  - The route is bound to the model id, not to a path. A user cannot ask for
 *    "audio/7/deadbeef.mp3"; they can only ask for narration #12, and #12
 *    either belongs to them or 404s. That removes path traversal as a category
 *    rather than defending against it.
 *
 *  - Responses are Cache-Control: private, no-store. Narration is a recording
 *    of somebody's private journal; it must not sit in a shared proxy or in
 *    the browser's disk cache after they sign out.
 */
class MediaController extends Controller
{
    public function narration(Request $request, Narration $narration): BinaryFileResponse
    {
        // 404 rather than 403: the existence of another user's narration is
        // itself information we do not owe anyone.
        abort_unless($narration->user_id === $request->user()->id, 404);
        abort_unless($narration->isReady(), 404);

        $absolute = Storage::disk('private')->path($narration->path);
        abort_unless(is_file($absolute), 404);

        // BinaryFileResponse handles Range requests, which is what lets the
        // player scrub without downloading the whole file again.
        return $this->private(response()->file($absolute, [
            'Content-Type'        => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="reading.mp3"',
            'Accept-Ranges'       => 'bytes',
        ]));
    }

    public function image(Request $request, DesireImage $image): BinaryFileResponse
    {
        abort_unless($image->user_id === $request->user()->id, 404);

        $absolute = Storage::disk('private')->path($image->path);
        abort_unless(is_file($absolute), 404);

        // Read the type from the file itself rather than trusting the stored
        // extension, and refuse anything that is not one of the three formats
        // the uploader accepts. A file that got onto the disk as .png but is
        // actually SVG or HTML must never be served with a type that would let
        // a browser execute it.
        $mime = @mime_content_type($absolute) ?: 'application/octet-stream';
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return $this->private(response()->file($absolute, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]));
    }

    /**
     * Force a response to be uncacheable by anything but the requesting client.
     *
     * Passing Cache-Control in the headers array is not enough. Symfony's
     * BinaryFileResponse takes $public = true as its fourth constructor
     * argument, and Laravel's response()->file() passes it — so the response
     * calls setPublic() on itself and stamps "public" into Cache-Control,
     * whatever we asked for. The result observed before this fix was
     * "no-store, public", which is both contradictory and exactly the wrong
     * instruction for a recording of somebody's private journal.
     *
     * setPrivate() drops the public flag; the explicit header then leaves no
     * room for interpretation.
     */
    private function private(BinaryFileResponse $response): BinaryFileResponse
    {
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
