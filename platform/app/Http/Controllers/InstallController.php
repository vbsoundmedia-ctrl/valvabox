<?php

namespace App\Http\Controllers;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/** One-time web installer, so the app can be set up on shared hosting without SSH or Composer. */
class InstallController extends Controller
{
    public static function lockFile(): string
    {
        return storage_path('app/installed.lock');
    }

    /** Tests can force the installed state. */
    public static ?bool $installedOverride = null;

    public static function isInstalled(): bool
    {
        return self::$installedOverride ?? file_exists(self::lockFile());
    }

    public static function requirements(): array
    {
        $writable = fn ($p) => is_writable($p);

        return [
            'PHP 8.2 or newer (you have '.PHP_VERSION.')' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'PDO MySQL extension' => extension_loaded('pdo_mysql'),
            'OpenSSL extension' => extension_loaded('openssl'),
            'Mbstring extension' => extension_loaded('mbstring'),
            'cURL extension' => extension_loaded('curl'),
            'Fileinfo extension' => extension_loaded('fileinfo'),
            'XML / DOM extensions' => extension_loaded('dom') && extension_loaded('xml'),
            'GD extension (artwork thumbnails, optional)' => extension_loaded('gd'),
            'Zip extension (admin download packs, optional)' => class_exists(\ZipArchive::class),
            '"storage" folder is writable' => $writable(storage_path()) && $writable(storage_path('framework')) && $writable(storage_path('app')),
            '"bootstrap/cache" folder is writable' => $writable(base_path('bootstrap/cache')),
            '".env" file is writable' => $writable(base_path('.env')),
        ];
    }

    public function show(Request $request)
    {
        return view('install', [
            'requirements' => self::requirements(),
            'uploadLimit' => ini_get('upload_max_filesize'),
            'appUrl' => $request->getSchemeAndHttpHost().rtrim(str_replace('/index.php', '', $request->getBaseUrl()), '/'),
        ]);
    }

    public function install(Request $request)
    {
        $optional = ['GD extension (artwork thumbnails, optional)', 'Zip extension (admin download packs, optional)'];
        foreach (self::requirements() as $label => $ok) {
            if (! $ok && ! in_array($label, $optional, true)) {
                return back()->withInput()->withErrors(['requirements' => "Requirement not met: {$label}"]);
            }
        }

        $data = $request->validate([
            'app_url' => ['required', 'url'],
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'integer'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            new PDO("mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']}",
                $data['db_username'], $data['db_password'] ?? '', [PDO::ATTR_TIMEOUT => 10]);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['db_host' => 'Could not connect to the database: '.$e->getMessage()]);
        }

        self::writeEnv([
            'APP_URL' => rtrim($data['app_url'], '/'),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
        ]);

        config([
            'app.url' => rtrim($data['app_url'], '/'),
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username' => $data['db_username'],
            'database.connections.mysql.password' => $data['db_password'] ?? '',
        ]);
        DB::purge('mysql');

        try {
            @set_time_limit(300);
            Artisan::call('migrate', ['--force' => true]);
            (new DatabaseSeeder)->run();

            $admin = User::updateOrCreate(['email' => $data['admin_email']], [
                'name' => $data['admin_name'],
                'password' => $data['admin_password'],
                'role' => 'admin',
                'status' => 'active',
            ]);
            $admin->forceFill(['email_verified_at' => now()])->save();
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['db_host' => 'Installation failed: '.$e->getMessage()]);
        }

        file_put_contents(self::lockFile(), 'Installed '.now()->toDateTimeString());
        Cache::flush();
        Auth::login($admin);

        return redirect()->route('admin.settings')->with('success', 'Valvabox is installed! Add your Paystack keys below to start accepting Naira payments.');
    }

    /** Update (or add) keys in the .env file. */
    public static function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $env = file_exists($path) ? file_get_contents($path) : '';
        foreach ($values as $key => $value) {
            $value = (string) $value;
            if ($value === '' || preg_match('/[\s#"\'\\\\$=]/', $value)) {
                $value = '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
            }
            $line = "{$key}={$value}";
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $env = preg_match($pattern, $env)
                ? preg_replace_callback($pattern, fn () => $line, $env)
                : rtrim($env)."\n{$line}\n";
        }
        file_put_contents($path, $env);
    }
}
