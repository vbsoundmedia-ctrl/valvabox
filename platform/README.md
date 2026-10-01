# Valvabox platform

Laravel 12 app for music, video and lyrics distribution with Naira payments (Paystack / Flutterwave).

- Install on cPanel: see `../INSTALL.md` (also bundled in the release ZIP).
- Local development: `cp .env.example .env`, set `DB_*`, `php artisan key:generate && php artisan migrate --seed`, then `php artisan serve`.
- Tests: `php artisan test`.
