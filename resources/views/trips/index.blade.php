@extends('layouts.app')

@section('content')
<div class="dashboard-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span>✦</span> roamly</a>
        <nav><a href="{{ route('dashboard') }}"><b>⌂</b> Overview</a><a class="active" href="{{ route('trips.index') }}"><b>⌁</b> My trips</a><a href="{{ route('dashboard') }}#activity"><b>◷</b> Activity</a><a href="{{ route('profile.edit') }}"><b>◯</b> Profile</a>@if($user->is_admin)<div class="nav-divider">ADMIN CONTENT</div><a class="admin-link" href="{{ route('admin.index') }}"><b>⌘</b> Admin control center</a>@endif</nav>
        <div class="sidebar-note"><span>✦</span><strong>Travel lighter</strong><small>Good plans leave room for wonder.</small></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">↪ <span>Sign out</span></button></form>
    </aside>
    <main class="dash-main">
        <header class="dash-head"><div><p class="kicker">YOUR JOURNEYS</p><h1>My trips</h1><p class="sub">Keep every plan, person, and payment in one place.</p></div><a class="primary trip-create" href="{{ route('trips.create') }}">+ New trip</a></header>
        @if(session('status'))<div class="save-message">{{ session('status') }}</div>@endif
        <form class="dashboard-filters" method="GET"><label class="search-box">⌕ <input type="search" name="q" value="{{ $search }}" placeholder="Search trips or groups..."></label><label class="period-box">Status<select name="status" onchange="this.form.submit()"><option value="all" @selected($status === 'all')>All trips</option><option value="active" @selected($status === 'active')>Active</option><option value="completed" @selected($status === 'completed')>Completed</option></select></label><button class="filter-button" type="submit">Search</button>@if($search !== '' || $status !== 'all')<a class="clear-filter" href="{{ route('trips.index') }}">Clear</a>@endif</form>
        <div class="trip-grid trips-page-grid">
            @forelse($trips as $trip)
                <article class="trip-card" style="--accent:{{ $trip->cover_color ?? '#5148e5' }}"><div class="trip-top"><span class="trip-mark">⌁</span><span class="pill">{{ $trip->status }}</span></div><h3>{{ $trip->trip_name }}</h3><p>{{ $trip->group_name }} · {{ $trip->members->count() }} members</p><footer><span>{{ $trip->currency_symbol }}{{ number_format($trip->expenses_sum_amount ?? 0) }} · {{ $trip->expenses_count }} expenses</span><strong>{{ $trip->start_date?->format('M j') ?? 'Anytime' }}</strong></footer><a class="record-link" href="{{ route('trips.show', $trip) }}">Open trip →</a>@if($trip->user_id === $user->id)<details class="trip-actions"><summary>Manage trip</summary><form method="POST" action="{{ route('trips.update', $trip) }}">@csrf @method('PUT')<input name="trip_name" value="{{ $trip->trip_name }}" required><input name="group_name" value="{{ $trip->group_name }}" required><select name="status"><option value="active" @selected($trip->status === 'active')>Active</option><option value="completed" @selected($trip->status === 'completed')>Completed</option></select><button class="small-button" type="submit">Save changes</button></form><form method="POST" action="{{ route('trips.destroy', $trip) }}" onsubmit="return confirm('Delete this trip and its data?')">@csrf @method('DELETE')<button class="delete-button" type="submit">Delete trip</button></form></details>@endif</article>
            @empty
                <div class="empty">No trips found. Create your first adventure.</div>
            @endforelse
        </div>
        @if($trips->hasPages())<div class="pagination-wrap">{{ $trips->links() }}</div>@endif
    </main>
</div>
@endsection
