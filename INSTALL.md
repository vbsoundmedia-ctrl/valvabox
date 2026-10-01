# Installing Valvabox on cPanel shared hosting

You need: a cPanel hosting account with **PHP 8.2 or newer** and **MySQL/MariaDB**, and your domain (e.g. `valvabox.name.ng`) pointing to it.
No SSH, Composer or Node.js is needed. Everything is in the ZIP.

---

## 1. Set PHP to 8.2+
cPanel → **Select PHP Version** (or **MultiPHP Manager**) → choose **PHP 8.2** or **8.3** for your domain.
Under **Extensions**, make sure these are ticked: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `xml`/`dom`, `gd`, `zip`, `intl` (most are on by default).

Under **Options** (or **MultiPHP INI Editor**) set:
| Setting | Value |
|---|---|
| upload_max_filesize | 512M |
| post_max_size | 520M |
| max_execution_time | 300 |
| memory_limit | 256M |

(The ZIP also includes `.user.ini` and `.htaccess` lines that try to set these automatically.)

## 2. Create the database
cPanel → **MySQL® Databases**:
1. Create a database, e.g. `valvabox` → it becomes `cpuser_valvabox`.
2. Create a user with a strong password, e.g. `cpuser_vbuser`.
3. **Add user to database** → tick **ALL PRIVILEGES**.

Write down the database name, user and password.

## 3. Upload and extract
cPanel → **File Manager** → open `public_html` (or your domain's folder).
1. Delete the default `index.html` / `default.php` if there is one.
2. **Upload** `valvabox-1.0.0.zip`.
3. Right-click the ZIP → **Extract** into `public_html`.
4. Click **Settings** (top right) → tick **Show Hidden Files**. Check that `.htaccess` and `.env.example` are there.

Your `public_html` should now contain `app`, `bootstrap`, `config`, `public`, `storage`, `vendor`, `.htaccess`, …

> **Optional, more secure:** if your host lets you change the domain's *Document Root* (cPanel → **Domains** → Manage), point it to `public_html/public`. The included `.htaccess` already protects the app either way.

## 4. Run the installer
Visit **https://valvabox.name.ng/install** (it opens automatically on first visit).
1. Check every server requirement shows **OK**.
2. Enter the site URL, database name, user and password from step 2.
3. Create your **admin account**.
4. Click **Install**. You'll land in the admin **Settings** page.

The installer locks itself afterwards (`storage/app/installed.lock`).

## 5. Turn on SSL
cPanel → **SSL/TLS Status** → **Run AutoSSL**. Then make sure the site URL in `.env` starts with `https://`.

## 6. Connect Paystack (Naira payments)
1. Sign up at **paystack.com** and complete business verification (CAC documents) to get live keys.
2. In Valvabox: **Admin → Settings** → paste your **Public key** and **Secret key**. Start with test keys (`sk_test_…`).
3. In Paystack: **Settings → API Keys & Webhooks** → set **Webhook URL** to `https://valvabox.name.ng/webhooks/paystack`.
4. Test: register as an artist, create a release, pay with a Paystack test card (`4084 0840 8408 4081`, any future date, CVV `408`, PIN `0000`, OTP `123456`).
5. Switch to live keys when ready.

**Payouts to artists:** withdrawals appear in **Admin → Withdrawals**. Click **Send via Paystack** (needs a funded Paystack balance and *Transfers* enabled on your Paystack account), or pay by bank transfer yourself and click **Mark paid**. Tick *Send withdrawals automatically* in Settings to skip the manual step.

Flutterwave works the same way (optional): add its keys, then set its webhook URL to `/webhooks/flutterwave` with the same secret hash.

## 7. Email (password resets)
cPanel → **Email Accounts** → create `hello@valvabox.name.ng`. Then edit `.env` in File Manager:
```
MAIL_HOST=mail.valvabox.name.ng
MAIL_PORT=465
MAIL_USERNAME=hello@valvabox.name.ng
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS="hello@valvabox.name.ng"
```

---

## Daily workflow (admin)
1. **Releases → In review**: listen, check artwork and metadata.
2. Enter the **UPC** and **ISRCs** (from your distribution partner or your own ISRC/UPC ranges), set status **Approved**.
3. Click **Download delivery pack (ZIP)**: artwork, WAVs, lyrics (.txt/.lrc) and metadata (CSV + JSON), ready to upload to your distribution partner.
4. When delivered, set **Delivered**; when it appears in stores, set **Live** and paste the store links.
5. Monthly: **Royalty import** → upload the partner's sales CSV (`isrc`, `amount_usd`, optional `store`, `country`, `units`) with the USD→NGN rate. Wallets are credited automatically.

## Moving to valvabox.net later
1. Add `valvabox.net` in cPanel (as the main domain or an alias of this folder) and run AutoSSL.
2. Edit `.env`: `APP_URL=https://valvabox.net`.
3. Update the Paystack/Flutterwave webhook URLs.
4. Redirect the old domain: add this to the **top** of `.htaccess` in `public_html`:
   ```
   RewriteEngine On
   RewriteCond %{HTTP_HOST} ^(www\.)?valvabox\.name\.ng$ [NC]
   RewriteRule ^(.*)$ https://valvabox.net/$1 [R=301,L]
   ```

## Backups
cPanel → **Backup** → download a **MySQL database** backup and a **home directory** backup regularly. Artist uploads live in `storage/app/private`.

## Troubleshooting
| Problem | Fix |
|---|---|
| “500 Internal Server Error” | Check PHP is 8.2+. Look at `storage/logs/laravel.log`. Make sure `storage` and `bootstrap/cache` are writable (755 or 775). |
| Page shows a list of files | `.htaccess` is missing: enable *Show Hidden Files* and re-extract. |
| Uploads fail for big WAVs | Raise `upload_max_filesize`/`post_max_size` (step 1). For huge videos, artists can paste a Google Drive/Dropbox link instead. |
| Payment says “not set up” | Add the Paystack secret key in Admin → Settings. |
| Need to re-run the installer | Delete `storage/app/installed.lock` (your data stays; the installer won't drop tables). |
| Turn on error details briefly | In `.env` set `APP_DEBUG=true`, reload, then set it back to `false`. |
