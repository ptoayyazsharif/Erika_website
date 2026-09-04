{{-- Asking to send notifications.

     Not on first load: a permission prompt fired at somebody who just arrived
     gets refused, and a refusal in the browser is close to permanent — most
     people cannot find the setting to undo it. So this is a card they choose to
     act on, shown only when push could actually work and nobody has decided
     yet.

     Rendered inside <main> like the announcement banner, never as a child of
     body.app-shell — that grid is what the desktop layout regression came from.

     The VAPID public key travels in a data attribute because the CSP forbids
     inline script. It is the public half of the pair; the private half never
     leaves the server. --}}
@if (config('escalate.push.enabled') && \App\Support\Push::configured() && (auth()->user()?->profile?->push_reminders ?? true))
    <div class="card card-quiet push-prompt" data-push-prompt hidden
         data-push-key="{{ config('escalate.push.public_key') }}"
         data-push-url="{{ route('push.store') }}">
        <div class="row-between wrap" style="gap:var(--s-3);align-items:flex-start">
            <div>
                <p style="margin:0"><strong>A quiet nudge each day?</strong></p>
                <p class="small muted" style="margin:var(--s-2) 0 0;max-width:46ch">
                    {{-- "9ish" reads as "gish" in this serif at small sizes;
                         looked at in a browser, not guessed. --}}
                    One notification a day, around {{ (int) config('escalate.push.hour') }} o'clock your time.
                    It never says what you are working on — just that today is here.
                </p>
            </div>

            <div class="row wrap" style="gap:var(--s-2)">
                <button type="button" class="btn btn-sm" data-push-yes>Yes, remind me</button>
                <button type="button" class="btn btn-quiet btn-sm" data-push-no>No thanks</button>
            </div>
        </div>
    </div>
@endif
