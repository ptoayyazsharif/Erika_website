<x-mail::message>
# {{ $isUpdate ? 'An application was updated' : 'Somebody applied' }}

@if ($isUpdate)
**{{ $application->name }}** has replaced their earlier answers. What follows is
the current version — the one they applied with is gone.
@else
**{{ $application->name }}** has applied to the private beta.
@endif

{{-- Explicit <br>, because markdown collapses a single newline and these two
     ran together on one line the first time this was rendered and looked at. --}}
**Email:** {{ $application->email }}<br>
**{{ $isUpdate ? 'Updated' : 'Applied' }}:** {{ $application->updated_at->format('j F Y, H:i') }}

---

{{--
    Questions come from Application::answers(), which reads its labels from
    App\Support\Copy — the same place the form and the admin screen read them.
    Rewording a question in the admin panel therefore cannot leave an answer in
    this email sitting under a sentence it was never asked.
--}}
@foreach ($application->answers() as $question => $answer)
**{{ $question }}**

{{ $answer }}

@endforeach
---

<x-mail::button :url="route('admin.applications.show', $application)">
Select or decline
</x-mail::button>

You are getting this because you are an administrator. It can be switched off in
**Settings → Who gets in**.

<br>
Escalate
</x-mail::message>
