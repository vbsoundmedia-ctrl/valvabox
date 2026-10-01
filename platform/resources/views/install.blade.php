<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Valvabox</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/logo-mark.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card" style="max-width:720px">
        <div class="brand">@include('partials.logo', ['size' => 40])<span>valva<b>box</b></span></div>
        <h1 class="center" style="font-size:24px">Install Valvabox</h1>
        <p class="muted center small mb">This takes about a minute. You only do it once.</p>
        @include('partials.flash')

        <h3>1. Server check</h3>
        <div class="card mt mb" style="padding:12px 16px">
            @foreach ($requirements as $label => $ok)
                <div class="list-row"><span>{{ $label }}</span><span class="badge {{ $ok ? 'ok' : (str_contains($label, 'optional') ? 'warn' : 'bad') }}">{{ $ok ? 'OK' : (str_contains($label, 'optional') ? 'Missing' : 'Fix this') }}</span></div>
            @endforeach
            <div class="list-row"><span>Max upload size (upload_max_filesize)</span><span class="badge {{ (int) $uploadLimit >= 128 || str_ends_with(strtoupper($uploadLimit), 'G') ? 'ok' : 'warn' }}">{{ $uploadLimit }}</span></div>
        </div>
        @if ((int) $uploadLimit < 128 && ! str_ends_with(strtoupper($uploadLimit), 'G'))
            <div class="alert alert-warn small">WAV files are often 30–100 MB. In cPanel open <b>Select PHP Version → Options</b> (or <b>MultiPHP INI Editor</b>) and set <code>upload_max_filesize</code> and <code>post_max_size</code> to <b>512M</b>, <code>max_execution_time</code> to <b>300</b>. You can do this after installing.</div>
        @endif

        <form method="post" action="{{ route('install.run') }}">@csrf
            <h3 class="mt">2. Website address</h3>
            <div class="mt"><label>Site URL</label><input type="url" name="app_url" value="{{ old('app_url', $appUrl) }}" required>
                <div class="help">Use https:// once SSL is active. You can change it later in the .env file (e.g. when you move to valvabox.net).</div></div>

            <h3 class="mt2">3. MySQL database</h3>
            <p class="help">Create these in cPanel → <b>MySQL® Databases</b>: a database, a user, then “Add user to database” with <b>ALL PRIVILEGES</b>. cPanel adds your username as a prefix, e.g. <code>cpuser_valvabox</code>.</p>
            <div class="form-grid mt">
                <div><label>Database host</label><input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}" required></div>
                <div><label>Port</label><input type="number" name="db_port" value="{{ old('db_port', 3306) }}" required></div>
                <div class="full"><label>Database name</label><input type="text" name="db_database" value="{{ old('db_database') }}" required></div>
                <div><label>Database user</label><input type="text" name="db_username" value="{{ old('db_username') }}" required></div>
                <div><label>Database password</label><input type="password" name="db_password"></div>
            </div>

            <h3 class="mt2">4. Your admin account</h3>
            <div class="form-grid mt">
                <div><label>Name</label><input type="text" name="admin_name" value="{{ old('admin_name') }}" required></div>
                <div><label>Email</label><input type="email" name="admin_email" value="{{ old('admin_email') }}" required></div>
                <div><label>Password <span class="hint">(min 8)</span></label><input type="password" name="admin_password" required minlength="8"></div>
                <div><label>Confirm password</label><input type="password" name="admin_password_confirmation" required></div>
            </div>
            <button class="btn btn-green btn-block mt2" type="submit" onclick="this.textContent='Installing… please wait'">Install Valvabox</button>
        </form>
    </div>
</div>
</body>
</html>
