<x-mail::message>
# You’re in.

{{ $application->name }} — you have a seat in the Escalate private beta.

Your invite code is:

<x-mail::panel>
{{ $invite->code }}
</x-mail::panel>

<x-mail::button :url="$url">
Set up your account
</x-mail::button>

The link above fills the code in for you. If you would rather type it, the
code goes in the last field on the sign-up form.

@if ($invite->expires_at)
The code is good until **{{ $invite->expires_at->format('j F Y') }}**, and it is
tied to this email address.
@else
The code is tied to this email address.
@endif

**What we would like from you:** use it at least four times over the next seven
days, and then tell us the truth about it. Not what is nice about it — what is
missing, what is confusing, and what you would not miss.

<br>
Escalate<br>
<span style="color:#6E7891">Imagine it forward. Understand it backward. Remember what came true.</span>
</x-mail::message>
