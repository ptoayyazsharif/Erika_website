{{--
    The "show password" control.

    Rendered hidden and unhidden by app.js, the same way [data-install] is in the
    topbar: with JS blocked the field stays a plain password field rather than
    carrying a button that does nothing when pressed. The app is meant to work
    without this file, so a dead control would be a regression, not a detail.

    type="button" is load-bearing. A bare <button> inside a form defaults to
    submit, so without it pressing "show password" on the sign-in form would
    attempt a sign-in with whatever is typed so far, and on the register form
    would fire validation over a half-filled one. There is a test for it.

    Both icons ship and CSS shows one, so revealing does not depend on a second
    request landing.

    @param  string  $for   id of the password input this controls
--}}
<button type="button" class="reveal-toggle" data-reveal="{{ $for }}"
        aria-controls="{{ $for }}" aria-pressed="false"
        aria-label="Show password" title="Show password" hidden>
    @include('partials.icon', ['name' => 'eye', 'size' => 19])
    @include('partials.icon', ['name' => 'eye-off', 'size' => 19])
</button>
