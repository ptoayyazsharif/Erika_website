{{-- The prose is editable; everything below it is not.

     The code, the button and the expiry line stay here on purpose. An admin
     rewording this email cannot accidentally send a selection with no code in
     it or no way to sign up — which is the one failure that would make this
     particular email useless to the person who waited for it. --}}
<x-mail::message>
{!! $body !!}

<x-mail::panel>
{{ $invite->code }}
</x-mail::panel>

<x-mail::button :url="$url">
Set up your account
</x-mail::button>

@if ($invite->expires_at)
The code is good until **{{ $invite->expires_at->format('j F Y') }}**, and it is
tied to this email address.
@else
The code is tied to this email address.
@endif

<br>
Escalate<br>
<span style="color:#6E7891">Imagine it forward. Understand it backward. Remember what came true.</span>
</x-mail::message>
