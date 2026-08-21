{{-- The admin area's own nav. Not in the tabbar: that is the seven things a
     person uses their journal for, and an admin link there would be visible in
     the markup of every ordinary user's page. --}}
<div class="chips" style="margin-bottom:var(--s-6)">
    @foreach ([
        ['admin.dashboard', 'Overview'],
        ['admin.users', 'People'],
        ['admin.invites', 'Invites'],
        ['admin.settings', 'Settings'],
    ] as [$route, $label])
        <a class="chip {{ request()->routeIs($route) || request()->routeIs($route.'.*') ? 'is-on' : '' }}"
           href="{{ route($route) }}">{{ $label }}</a>
    @endforeach

    <form method="POST" action="{{ route('admin.leave') }}" style="margin-left:auto">
        @csrf
        <button class="chip" type="submit">Leave admin</button>
    </form>
</div>
