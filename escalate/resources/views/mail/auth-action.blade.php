{{-- Password reset and email confirmation.

     One template for both, because both are the same shape: some prose an
     admin may reword, and one button that must work. The button and its URL
     are here rather than in the editable body — a reset email whose link an
     edit removed is worse than no reset email at all.

     {!! !!} on $body is deliberate: it is an HtmlString from Laravel's mail
     Markdown, which escapes HTML input and strips unsafe links. --}}
<x-mail::message>
{!! $body !!}

<x-mail::button :url="$url">
{{ $action }}
</x-mail::button>

<br>
Escalate<br>
<span style="color:#6E7891">Imagine it forward. Understand it backward.</span>
</x-mail::message>
