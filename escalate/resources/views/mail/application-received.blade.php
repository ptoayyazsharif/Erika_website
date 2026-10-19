{{-- Prose from App\Support\EmailTemplates, so Erika can reword it without a
     deploy. {!! !!} is safe and necessary here: $body is an HtmlString from
     Laravel's mail Markdown, which escapes HTML input and strips unsafe links.
     Escaping it again would print the markup instead of rendering it. --}}
<x-mail::message>
{!! $body !!}

<br>
Escalate<br>
<span style="color:#6E7891">Imagine it forward. Understand it backward. Remember what came true.</span>
</x-mail::message>
