@extends('layouts.app')

@section('content')
<div class="dashboard-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span>✦</span> roamly</a>
        <nav>
            <a href="{{ route('dashboard') }}"><b>⌂</b> Overview</a>
            <a href="{{ route('trips.index') }}"><b>⌁</b> My trips</a>
            <a href="{{ route('dashboard') }}#activity"><b>◷</b> Activity</a>
            <a class="active" href="{{ route('profile.edit') }}"><b>◯</b> Profile</a>
            @if ($user->is_admin)
                <div class="nav-divider">ADMIN CONTENT</div>
                <a class="admin-link" href="{{ route('admin.legal.edit', 'privacy-policy') }}"><b>§</b> Privacy Policy</a>
                <a class="admin-link" href="{{ route('admin.legal.edit', 'terms') }}"><b>¶</b> Terms &amp; Conditions</a>
            @endif
        </nav>
        <div class="sidebar-note"><span>✦</span><strong>Travel lighter</strong><small>Good plans leave room for wonder.</small></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">↪ <span>Sign out</span></button></form>
    </aside>

    <main class="dash-main profile-main">
        <header class="dash-head"><div><p class="kicker">YOUR SPACE</p><h1>Profile &amp; settings</h1><p class="sub">Keep your account and travel preferences up to date.</p></div><span class="profile-hero-avatar" style="background:{{ $user->avatar_color }}">{{ $user->initials }}</span></header>
        @if (session('status'))<div class="save-message">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif

        <div class="profile-grid">
            <section class="workspace-card"><div class="card-head"><div><p class="kicker">PERSONAL DETAILS</p><h2>Account profile</h2></div></div>
                <form method="POST" action="{{ route('profile.update') }}" class="expense-form">
                    @csrf @method('PUT')
                    <label>Full name<input name="name" value="{{ old('name', $user->name) }}" required></label>
                    <label>Email address<input type="email" name="email" value="{{ old('email', $user->email) }}" required></label>
                    <div class="two-fields"><label>Currency code<input name="currency" value="{{ old('currency', $user->currency) }}" required></label><label>Currency symbol<input name="currency_symbol" value="{{ old('currency_symbol', $user->currency_symbol) }}" maxlength="5" required></label></div>
                    <label>Avatar colour<input type="color" name="avatar_color" value="{{ old('avatar_color', $user->avatar_color) }}" class="color-input"></label>
                    <label class="setting-check"><input type="checkbox" name="notifications_enabled" value="1" @checked(old('notifications_enabled', $user->notifications_enabled))> Receive trip and expense notifications</label>
                    <button class="primary" type="submit">Save profile <span>→</span></button>
                </form>
            </section>
            <section class="workspace-card"><div class="card-head"><div><p class="kicker">SECURITY</p><h2>Change password</h2></div></div>
                <form method="POST" action="{{ route('profile.password') }}" class="expense-form">
                    @csrf @method('PUT')
                    <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
                    <label>New password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                    <label>Confirm new password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
                    <button class="primary" type="submit">Update password <span>→</span></button>
                </form>
                <p class="security-copy">Use at least 8 characters. Changing your password keeps your web session secure.</p>
            </section>
        </div>
    </main>
</div>
@endsection
