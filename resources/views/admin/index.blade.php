@extends('layouts.app')

@section('content')
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span>✦</span> roamly</a>
        <p class="admin-eyebrow">ADMIN CONTROL CENTER</p>
        <nav>
            <a href="#overview">Overview</a>
            <a href="#users">Users</a>
            <a href="#trips">Trips</a>
            <a href="#expenses">Expenses</a>
            <a href="#members">Members</a>
            <a href="#activity">Audit activity</a>
            <a href="{{ route('admin.legal.edit', 'privacy-policy') }}">Privacy Policy</a>
            <a href="{{ route('admin.legal.edit', 'terms') }}">Terms &amp; Conditions</a>
        </nav>
        <a class="admin-back" href="{{ route('dashboard') }}">← Back to app</a>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div><p class="kicker">SYSTEM MANAGEMENT</p><h1>Admin control center</h1><p class="sub">Manage people, trips, expenses, and platform activity from one place.</p></div>
            <div class="admin-user"><strong>{{ $admin->name }}</strong><small>{{ $admin->email }}</small></div>
        </header>

        @if (session('status')) <div class="admin-alert success">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="admin-alert error">{{ $errors->first() }}</div> @endif
        <form class="admin-search" method="GET" action="{{ route('admin.index') }}"><span>⌕</span><input type="search" name="q" value="{{ $search }}" placeholder="Search users, trips, expenses, members, or activity..."><button type="submit">Search</button>@if($search !== '')<a href="{{ route('admin.index') }}">Clear</a>@endif</form>

        <section id="overview" class="admin-stats">
            <article><span>Users</span><strong>{{ number_format($stats['users']) }}</strong><small>{{ number_format($stats['active_users']) }} active</small></article>
            <article><span>Trips</span><strong>{{ number_format($stats['trips']) }}</strong><small>All journeys</small></article>
            <article><span>Expenses</span><strong>{{ number_format($stats['expenses']) }}</strong><small>Recorded payments</small></article>
            <article><span>Total volume</span><strong>₹{{ number_format($stats['volume'], 2) }}</strong><small>Across all expenses</small></article>
        </section>

        <section id="users" class="admin-card">
            <div class="admin-card-head"><div><p class="kicker">PEOPLE</p><h2>All users</h2></div><span>{{ $users->total() }} records</span></div>
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>User</th><th>Status</th><th>Role</th><th>Actions</th></tr></thead><tbody>
            @forelse ($users as $user)
                <tr><td><form class="inline-edit" method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')<input name="name" value="{{ $user->name }}" aria-label="Name"><input name="email" value="{{ $user->email }}" aria-label="Email"><button class="text-button" type="submit">Save</button></form></td><td><span class="status-badge {{ $user->is_active ? 'active' : 'disabled' }}">{{ $user->is_active ? 'Active' : 'Disabled' }}</span></td><td>{{ $user->is_admin ? 'Admin' : 'User' }}</td><td class="action-row">
                    @if ($user->id !== $admin->id)
                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}">@csrf<button class="small-button" type="submit">{{ $user->is_active ? 'Disable' : 'Enable' }}</button></form>
                        <form method="POST" action="{{ route('admin.users.admin', $user) }}">@csrf<button class="small-button" type="submit">{{ $user->is_admin ? 'Demote' : 'Promote' }}</button></form>
                        <form method="POST" action="{{ route('admin.users.delete', $user) }}" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')<button class="small-button danger" type="submit">Delete</button></form>
                    @else <span class="muted">Current account</span> @endif
                </td></tr>
            @empty <tr><td colspan="4" class="empty-cell">No users found.</td></tr> @endforelse
            </tbody></table></div><div class="pagination">{{ $users->fragment('users')->links() }}</div>
        </section>

        <section id="trips" class="admin-card"><div class="admin-card-head"><div><p class="kicker">JOURNEYS</p><h2>All trips</h2></div><span>{{ $trips->total() }} records</span></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Trip</th><th>Owner</th><th>Members</th><th>Expenses</th><th>Action</th></tr></thead><tbody>
        @forelse ($trips as $trip)<tr><td><a class="record-link" href="{{ route('trips.show', $trip) }}">{{ $trip->trip_name }}</a><small>{{ $trip->status }}</small></td><td>{{ $trip->user?->name ?? 'Deleted user' }}</td><td>{{ $trip->members_count }}</td><td>{{ $trip->expenses_count }}</td><td><form method="POST" action="{{ route('admin.trips.delete', $trip) }}" onsubmit="return confirm('Delete this trip and its expenses?')">@csrf @method('DELETE')<button class="small-button danger" type="submit">Delete</button></form></td></tr>@empty<tr><td colspan="5" class="empty-cell">No trips found.</td></tr>@endforelse
        </tbody></table></div><div class="pagination">{{ $trips->fragment('trips')->links() }}</div></section>

        <section id="expenses" class="admin-card"><div class="admin-card-head"><div><p class="kicker">FINANCE</p><h2>All expenses</h2></div><span>{{ $expenses->total() }} records</span></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Expense</th><th>Trip</th><th>Paid by</th><th>Amount</th><th>Action</th></tr></thead><tbody>
        @forelse ($expenses as $expense)<tr><td>{{ $expense->title }}<small>{{ $expense->category }}</small></td><td>{{ $expense->trip?->trip_name ?? 'Deleted trip' }}</td><td>{{ $expense->paidBy?->name ?? 'Unknown' }}</td><td>₹{{ number_format($expense->amount, 2) }}</td><td><form method="POST" action="{{ route('admin.expenses.delete', $expense) }}" onsubmit="return confirm('Delete this expense?')">@csrf @method('DELETE')<button class="small-button danger" type="submit">Delete</button></form></td></tr>@empty<tr><td colspan="5" class="empty-cell">No expenses found.</td></tr>@endforelse
        </tbody></table></div><div class="pagination">{{ $expenses->fragment('expenses')->links() }}</div></section>

        <section id="members" class="admin-card"><div class="admin-card-head"><div><p class="kicker">CREW</p><h2>Manage members</h2></div><span>{{ $members->total() }} records</span></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Member</th><th>Trip</th><th>Type</th><th>Action</th></tr></thead><tbody>
        @forelse ($members as $member)<tr><td>{{ $member->name }}</td><td>{{ $member->trip?->trip_name ?? 'Deleted trip' }}</td><td>{{ $member->is_owner ? 'Owner' : 'Member' }}</td><td>@if (!$member->is_owner)<form method="POST" action="{{ route('admin.members.delete', $member) }}" onsubmit="return confirm('Remove this member?')">@csrf @method('DELETE')<button class="small-button danger" type="submit">Remove</button></form>@else<span class="muted">Protected</span>@endif</td></tr>@empty<tr><td colspan="4" class="empty-cell">No members found.</td></tr>@endforelse
        </tbody></table></div><div class="pagination">{{ $members->fragment('members')->links() }}</div></section>

        <section id="activity" class="admin-card"><div class="admin-card-head"><div><p class="kicker">SECURITY</p><h2>Audit activity</h2></div><span>{{ $activities->total() }} records</span></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Action</th><th>Performed by</th><th>Description</th><th>IP</th><th>When</th></tr></thead><tbody>@forelse ($activities as $activity)<tr><td><span class="action-tag">{{ $activity->action }}</span></td><td>{{ $activity->user?->name ?? 'System' }}</td><td>{{ $activity->description }}</td><td>{{ $activity->ip_address ?? '—' }}</td><td>{{ $activity->created_at->diffForHumans() }}</td></tr>@empty<tr><td colspan="5" class="empty-cell">No admin activity recorded yet.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $activities->fragment('activity')->links() }}</div></section>
    </main>
</div>
@endsection
