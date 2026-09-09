@extends('layouts.app')

@section('content')
<div class="dashboard-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span>✦</span> roamly</a>
        <nav>
            <a href="{{ route('dashboard') }}"><b>⌂</b> Overview</a>
            <a class="active" href="{{ route('trips.index') }}"><b>⌁</b> My trips</a>
            <a href="#expenses"><b>◷</b> Expenses</a>
            <a href="#balances"><b>◯</b> Balances</a>
        </nav>
        <div class="sidebar-note"><span>✦</span><strong>Travel lighter</strong><small>Good plans leave room for wonder.</small></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">↪ <span>Sign out</span></button></form>
    </aside>

    <main class="dash-main trip-workspace">
        <a class="back-link" href="{{ route('trips.index') }}">← All trips</a>
        <header class="workspace-head">
            <div><p class="kicker">{{ strtoupper($trip->status) }} TRIP · {{ $trip->start_date?->format('M j, Y') ?? 'Flexible dates' }}</p><h1>{{ $trip->trip_name }}</h1><p class="sub">{{ $trip->group_name }} · {{ $trip->description ?: 'Your shared trip workspace.' }}</p></div>
            <span class="workspace-mark" style="background:{{ $trip->cover_color }}">⌁</span>
        </header>
        @if (session('status'))<div class="save-message">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert workspace-alert"><strong>Action needs attention</strong><span>{{ $errors->first() }}</span></div>@endif

        <div class="workspace-grid">
            <section class="workspace-main">
                <div class="workspace-card">
                    <div class="card-head"><div><p class="kicker">TRIP SETTINGS</p><h2>Edit trip details</h2></div></div>
                    <form method="POST" action="{{ route('trips.update', $trip) }}" class="expense-form">
                        @csrf @method('PUT')
                        <div class="two-fields"><label>Trip name<input name="trip_name" value="{{ $trip->trip_name }}" required></label><label>Group name<input name="group_name" value="{{ $trip->group_name }}" required></label></div>
                        <label>Description<textarea name="description" rows="3">{{ $trip->description }}</textarea></label>
                        <div class="two-fields"><label>Status<select name="status"><option value="active" @selected($trip->status === 'active')>Active</option><option value="completed" @selected($trip->status === 'completed')>Completed</option></select></label><label>Currency symbol<input name="currency_symbol" value="{{ $trip->currency_symbol }}" maxlength="5"></label></div>
                        <button class="primary" type="submit">Save trip changes <span>→</span></button>
                    </form>
                </div>

                <div class="workspace-card" id="expenses">
                    <div class="card-head"><div><p class="kicker">SPENDING</p><h2>Expenses</h2></div><button class="primary small-primary" onclick="document.getElementById('expense-form').scrollIntoView()">+ Add expense</button></div>
                    @forelse ($trip->expenses as $expense)
                        <article class="expense-row">
                            <span class="expense-icon">◫</span><div><strong>{{ $expense->title }}</strong><small>{{ $expense->paidBy?->name }} paid · {{ ucfirst($expense->category) }} · {{ $expense->expense_date->format('M j') }}</small></div><b>{{ $trip->currency_symbol }}{{ number_format($expense->amount, 2) }}</b>
                            <details class="inline-edit"><summary>Edit</summary><form method="POST" action="{{ route('trips.expenses.update', [$trip, $expense]) }}" class="mini-form">@csrf @method('PUT')<input name="title" value="{{ $expense->title }}" required><input type="number" step="0.01" name="amount" value="{{ $expense->amount }}" required><select name="category"><option value="food">Food</option><option value="hotel">Hotel</option><option value="transport">Transport</option><option value="shopping">Shopping</option><option value="other">Other</option></select><select name="paid_by_member_id">@foreach ($trip->members as $member)<option value="{{ $member->id }}" @selected($expense->paid_by_member_id === $member->id)>{{ $member->name }}</option>@endforeach</select><input type="date" name="expense_date" value="{{ $expense->expense_date->format('Y-m-d') }}" required>@foreach ($trip->members as $member)<label class="member-check"><input type="checkbox" name="split_member_ids[]" value="{{ $member->id }}" @checked($expense->splits->contains('trip_member_id', $member->id))>{{ $member->name }}</label>@endforeach<button class="small-button">Save expense</button></form></details>
                            <form method="POST" action="{{ route('trips.expenses.delete', [$trip, $expense]) }}" onsubmit="return confirm('Delete this expense?')">@csrf @method('DELETE')<button class="icon-delete">×</button></form>
                        </article>
                    @empty
                        <div class="empty">No expenses yet. Add the first shared cost below.</div>
                    @endforelse
                </div>

                <div class="workspace-card form-card" id="expense-form"><div class="card-head"><div><p class="kicker">SHARED COST</p><h2>Add an expense</h2></div></div><form method="POST" action="{{ route('trips.expenses.add', $trip) }}" class="expense-form">@csrf<label>What was it for?<input name="title" placeholder="Dinner, hotel, taxi..." required></label><div class="two-fields"><label>Amount<input type="number" name="amount" step="0.01" min="0.01" required></label><label>Category<select name="category"><option value="food">Food</option><option value="hotel">Hotel</option><option value="transport">Transport</option><option value="shopping">Shopping</option><option value="entertainment">Entertainment</option><option value="tickets">Tickets</option><option value="fuel">Fuel</option><option value="other">Other</option></select></label></div><div class="two-fields"><label>Paid by<select name="paid_by_member_id">@foreach ($trip->members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></label><label>Date<input type="date" name="expense_date" value="{{ now()->format('Y-m-d') }}" required></label></div><fieldset><legend>Split equally between</legend><div class="member-checks">@foreach ($trip->members as $member)<label class="member-check"><input type="checkbox" name="split_member_ids[]" value="{{ $member->id }}" checked><span style="background:{{ $member->avatar_color }}">{{ $member->initials }}</span>{{ $member->name }}</label>@endforeach</div></fieldset><button class="primary" type="submit">Save expense <span>→</span></button></form></div>
            </section>

            <aside class="workspace-side">
                <div class="workspace-card" id="balances"><div class="card-head"><div><p class="kicker">WHO OWES WHAT</p><h2>Balances</h2></div></div>@foreach ($trip->members as $member) @php($balance = $member->expensesPaid->sum('amount') - $member->expenseSplits->sum('amount'))<div class="balance-row"><span class="avatar-small" style="background:{{ $member->avatar_color }}">{{ $member->initials }}</span><div><strong>{{ $member->name }}</strong><small>{{ $balance > .01 ? 'gets back' : ($balance < -.01 ? 'owes' : 'settled') }}</small></div><b class="{{ $balance > .01 ? 'positive' : ($balance < -.01 ? 'negative' : '') }}">{{ $trip->currency_symbol }}{{ number_format(abs($balance), 2) }}</b></div>@endforeach</div>
                <div class="workspace-card"><div class="card-head"><div><p class="kicker">SETTLE UP</p><h2>Suggested transfers</h2></div></div>@forelse ($settlements as $settlement)<div class="settlement"><strong>{{ $settlement['from']['name'] }}</strong><span>pays</span><strong>{{ $settlement['to']['name'] }}</strong><b>{{ $trip->currency_symbol }}{{ number_format($settlement['amount'], 2) }}</b></div>@empty<div class="empty">Everyone is settled up.</div>@endforelse</div>
                <div class="workspace-card"><div class="card-head"><div><p class="kicker">YOUR CREW</p><h2>Members</h2></div></div>@foreach ($trip->members as $member)<div class="member-row"><span class="avatar-small" style="background:{{ $member->avatar_color }}">{{ $member->initials }}</span>@if ($trip->user_id === $user->id && ! $member->is_owner)<details class="inline-edit"><summary>{{ $member->name }}</summary><form method="POST" action="{{ route('trips.members.update', [$trip, $member]) }}" class="mini-form">@csrf @method('PUT')<input name="name" value="{{ $member->name }}" required><button class="small-button">Save</button></form></details>@else<span>{{ $member->name }}</span>@endif @if ($member->is_owner)<em>Owner</em>@elseif ($trip->user_id === $user->id)<form method="POST" action="{{ route('trips.members.delete', [$trip, $member]) }}" onsubmit="return confirm('Remove this member?')">@csrf @method('DELETE')<button class="icon-delete">×</button></form>@endif</div>@endforeach @if ($trip->user_id === $user->id)<form method="POST" action="{{ route('trips.members.add', $trip) }}" class="add-member">@csrf<input name="name" placeholder="Add a member" required><button class="small-button">Add</button></form>@endif</div>
                @include('trips._paid-summary')
            </aside>
        </div>
    </main>
</div>
@endsection
