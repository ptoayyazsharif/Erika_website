{{-- {!! !!} on $body is deliberate and narrow: it is parser output over
     already-escaped input, from App\Support\SafeMarkdown. --}}
<x-mail::message>
# {{ $announcement->title }}

{!! $body !!}

<br>
Escalate<br>
<span style="color:#6E7891">Imagine it forward. Understand it backward.</span>

<span style="color:#98A2B3;font-size:12px">
You are getting this because you have an Escalate account.
[Stop announcement emails]({{ $unsubscribeUrl }}) — invites, password resets and
anything you asked for still reach you.
</span>
</x-mail::message>
