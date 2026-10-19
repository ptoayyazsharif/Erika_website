{{-- The admin area's destinations live in the main nav bar now — see
     layouts/app.blade.php, which swaps the customer sections for these when
     you are inside /admin. All that is left here is the way out, which is an
     action rather than a destination and so does not belong in a nav list. --}}
<div class="row-between wrap" style="gap:var(--s-3);margin-bottom:var(--s-6)">
    <p class="small faint" style="margin:0">
        Signed in as {{ auth()->user()->email }}@if (config('escalate.admin.confirm_password')) · admin session expires after two hours idle @endif
    </p>

    <form method="POST" action="{{ route('admin.leave') }}">
        @csrf
        <button class="btn btn-quiet btn-sm" type="submit">Leave admin</button>
    </form>
</div>
