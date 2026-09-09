@extends('layouts.app')

@section('content')
<div class="dashboard-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span>✦</span> roamly</a>
        <nav>
            <a class="active" href="{{ route('dashboard') }}"><b>⌂</b> Overview</a>
            <a href="#trips"><b>⌁</b> My trips</a>
            <a href="#activity"><b>◷</b> Activity</a>
            <a href="{{ route('profile.edit') }}"><b>◯</b> Profile</a>
            @if ($user->is_admin)
                <div class="nav-divider">ADMIN CONTENT</div>
                <a class="admin-link" href="{{ route('admin.index') }}"><b>⌘</b> Admin control center</a>
                <a class="admin-link" href="{{ route('admin.legal.edit', 'privacy-policy') }}"><b>§</b> Privacy Policy</a>
                <a class="admin-link" href="{{ route('admin.legal.edit', 'terms') }}"><b>¶</b> Terms &amp; Conditions</a>
            @endif
        </nav>
        <div class="sidebar-note"><span>✦</span><strong>Travel lighter</strong><small>Good plans leave room for wonder.</small></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">↪ <span>Sign out</span></button></form>
    </aside>

    <main class="dash-main">
        <form class="dashboard-filters" method="GET" action="{{ route('dashboard') }}">
            <label class="search-box">⌕ <input type="search" name="q" value="{{ $search }}" placeholder="Search trips or expenses..."></label>
            <label class="period-box">Period<select name="period" onchange="this.form.submit()"><option value="7" @selected($period === '7')>Last 7 days</option><option value="30" @selected($period === '30')>Last 30 days</option><option value="90" @selected($period === '90')>Last 90 days</option><option value="all" @selected($period === 'all')>All time</option></select></label>
            <button class="filter-button" type="submit">Apply</button>
        </form>
        <header class="dash-head"><div><p class="kicker">{{ now()->format('l, F j') }}</p><h1>Good {{ now()->hour < 12 ? 'morning' : 'afternoon' }}, {{ Str::before($user->name, ' ') }} <span>✦</span></h1><p class="sub">Here’s the shape of your adventures.</p></div><div class="profile"><span style="background:{{ $user->avatar_color }}">{{ $user->initials }}</span><div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div></div></header>
        <section class="hero-card"><div><p class="kicker light">YOUR TRAVEL, SIMPLIFIED</p><h2>Make memories.<br><em>Not spreadsheets.</em></h2><p>Track, split, and settle expenses with your crew.</p></div><div class="hero-orbit">✦</div></section>
        <section class="stat-grid"><article><span class="stat-icon purple">◒</span><small>Total spent</small><strong>{{ $user->currency_symbol ?? '₹' }}{{ number_format($stats['spent']) }}</strong><em>Across {{ $stats['trips'] }} trips</em></article><article><span class="stat-icon green">↗</span><small>You get back</small><strong class="positive">{{ $user->currency_symbol ?? '₹' }}{{ number_format($stats['owed']) }}</strong><em>From your friends</em></article><article><span class="stat-icon amber">↘</span><small>You owe</small><strong class="negative">{{ $user->currency_symbol ?? '₹' }}{{ number_format($stats['owes']) }}</strong><em>To your friends</em></article></section>
        <section id="trips"><div class="section-head"><div><p class="kicker">YOUR JOURNEYS</p><h2>Active trips</h2></div><a href="#trips">View all <span>→</span></a></div><div class="trip-grid">@forelse($trips as $trip)<article class="trip-card" style="--accent:{{ $trip->cover_color ?? '#5148e5' }}"><div class="trip-top"><span class="trip-mark">⌁</span><span class="pill">{{ $trip->status }}</span></div><h3>{{ $trip->trip_name }}</h3><p>{{ $trip->group_name }} · {{ $trip->members->count() }} members</p><footer><span>{{ $trip->currency_symbol }}{{ number_format($trip->expenses->sum('amount')) }} spent</span><strong>{{ $trip->start_date?->format('M j') ?? 'Anytime' }}</strong></footer></article>@empty<div class="empty">No active trips yet. Your next adventure starts here.</div>@endforelse</div></section>
        <section id="activity"><div class="section-head"><div><p class="kicker">RECENT ACTIVITY</p><h2>Latest expenses</h2></div></div><div class="activity-list">@forelse($expenses as $expense)<article><span class="expense-icon">◫</span><div><strong>{{ $expense->title }}</strong><small>{{ $expense->paidBy?->name ?? 'Someone' }} paid · {{ $expense->trip_name }}</small></div><b>{{ $expense->currency_symbol }}{{ number_format($expense->amount) }}</b></article>@empty<div class="empty">No expenses yet. Add your first one through the app.</div>@endforelse</div></section>
    </main>
</div>
<script>
    const tripLinks = @json($trips->pluck('id')->values());
    document.querySelectorAll('.trip-card').forEach((card, index) => {
        if (!tripLinks[index]) return;
        card.classList.add('is-clickable');
        card.setAttribute('role', 'link');
        card.tabIndex = 0;
        const open = () => window.location.href = `/trips/${tripLinks[index]}`;
        card.addEventListener('click', (event) => { if (!event.target.closest('a,button,details,form')) open(); });
        card.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') open(); });
    });
</script>
<script>
    const greetingHour = new Date().getHours();
    const greeting = greetingHour < 12 ? 'morning' : (greetingHour < 17 ? 'afternoon' : 'evening');
    document.querySelectorAll('.dash-head h1').forEach((heading) => {
        heading.innerHTML = heading.innerHTML.replace(/Good (morning|afternoon|evening)/i, `Good ${greeting}`);
    });
</script>
<script>
    document.querySelectorAll('.section-head a[href="#trips"]').forEach((link) => {
        link.href = @json(route('trips.index'));
    });
</script>
@endsection
