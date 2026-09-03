{{-- See application-received.blade.php on why {!! !!} is correct here. --}}
<x-mail::message>
{!! $body !!}

<br>
Escalate<br>
<span style="color:#6E7891">Imagine it forward. Understand it backward.</span>
</x-mail::message>
