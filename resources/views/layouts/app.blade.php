<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'Roamly' }} · Trip expenses</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ secure_asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('css/legal.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('css/admin-panel.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('css/trips.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('css/profile.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('css/visuals.css') }}">
</head>
<body>
    @yield('content')
    <script>
        document.querySelectorAll('.password-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.parentElement.querySelector('input');
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
                button.setAttribute('aria-pressed', String(!visible));
                const eyeIcon = button.querySelector('.icon-eye');
                const eyeOffIcon = button.querySelector('.icon-eye-off');
                if (eyeIcon && eyeOffIcon) {
                    eyeIcon.hidden = !visible;
                    eyeOffIcon.hidden = visible;
                } else {
                    button.textContent = visible ? '◉' : '◌';
                }
            });
        });
        document.querySelectorAll('form').forEach((form) => {
            const search = form.querySelector('input[type="search"]');
            if (!search) return;
            search.addEventListener('input', () => {
                if (search.value.trim() === '' && new URLSearchParams(window.location.search).has(search.name)) {
                    window.setTimeout(() => form.submit(), 80);
                }
            });
        });
    </script>
</body>
</html>
