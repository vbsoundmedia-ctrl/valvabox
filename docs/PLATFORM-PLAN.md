# Valvabox — Platform Plan

Music, video and lyrics distribution for African artists, priced and paid in Naira.
Like DittoMusic or DistroKid in scope. Built to run on **cPanel shared hosting** first, at `valvabox.name.ng`, and to move to `valvabox.net` later.

---

## 1. How distribution actually works (read this first)

A distributor sits between artists and stores (DSPs). You can't upload straight to Spotify or Apple Music. They only take content from companies they have signed agreements with, sent as **DDEX ERN** XML feeds over SFTP or API. Getting those agreements takes time, a catalogue, and usually a track record.

So Valvabox launches in two stages:

| Stage | How releases reach the stores | Why |
|---|---|---|
| **Phase 1 (launch)** | Through a **white-label / B2B distribution partner** with a delivery API (for example Revelator, SonoSuite, FUGA, Labelgrid, or an African aggregator). Valvabox owns the brand, the artists, the payments and the dashboard. The partner handles DDEX delivery and gathers royalty reports. | Live in weeks, not years. No DSP contracts needed on day one. |
| **Phase 3 (scale)** | **Direct DDEX deals** with Audiomack, Boomplay, YouTube and others, one at a time, while the partner keeps covering the rest. | Better margins and faster payouts once volume justifies it. |

> Compare partners on: store coverage (Audiomack and Boomplay matter in Nigeria), whether video and lyrics delivery are included, API quality, revenue share, minimums, how often reports come, and whether they pay in USD to a domiciliary account.

**Lyrics:** Musixmatch and LyricFind supply lyrics to Spotify, Apple Music, Instagram, Amazon and others. Apply for a distributor or publisher partnership. Until then, deliver lyrics through your distribution partner if they support it.
**Video:** YouTube (Official Artist Channel, Content ID), Apple Music video, TIDAL and Vevo. In Phase 1 this goes through the partner. Content ID needs a YouTube CMS partner.

---

## 2. Tech stack (works on shared hosting)

| Layer | Choice | Why |
|---|---|---|
| Backend | **Laravel 12 (PHP 8.2+)** | Runs on any cPanel host. Has auth, queues, mail, storage and scheduling built in. Huge community in Nigeria. |
| Database | **MySQL / MariaDB** | Included with shared hosting. |
| Frontend | **Blade + Livewire 3 + Alpine.js + Tailwind CSS** | No separate Node server needed. Build CSS/JS locally and upload `public/build`. |
| Admin panel | **Filament 3** | Gives you a full admin (review queue, users, payouts) almost for free. |
| File storage | **Cloudflare R2** (or Backblaze B2 / Wasabi), all S3-compatible | Shared hosting can't hold multi-GB WAV and video files. R2 has no egress fees. |
| Uploads | Browser → R2 **direct multipart upload** using presigned URLs | Avoids PHP's `upload_max_filesize` limits and timeouts. |
| Queue | Laravel `database` queue + cron | Shared hosting has no Supervisor. See §7. |
| Payments | **Paystack** (main) + **Flutterwave** (backup) | Naira cards, bank transfer, USSD, and payouts to Nigerian banks. |
| Email | Zoho Mail / Brevo / Resend over SMTP | Shared-host `mail()` lands in spam. |
| Media processing | Partner-side, or a small VPS worker later | Shared hosting usually blocks `ffmpeg`. |

**When to leave shared hosting:** at around 1,000 active artists, or when you need ffmpeg or long-running workers. Move to a ₦15k–₦40k/month VPS (Hetzner, DigitalOcean, Contabo) with Laravel Forge or Ploi. The code stays the same.

---

## 3. Feature modules

### Artist side
1. **Accounts and onboarding:** email or phone sign-up, email verification, artist profile (stage name, genre, socials, Spotify/Apple artist IDs). Optional KYC (NIN/BVN through Dojah, Prembly or Smile ID) before the first payout.
2. **Music releases:** single, EP or album wizard:
   - Metadata: title, version, primary and featured artists, genre, language, explicit flag, ℗ and © lines, label name, release date, territories.
   - Audio: WAV/FLAC, 16/24-bit, 44.1 kHz or higher. Check duration and format.
   - Artwork: 3000×3000 JPG/PNG in RGB, no URLs, logos or blur. Check in the browser and the server.
   - Codes: free **ISRC** per track and **UPC** per release (from the partner pool or your own GS1 Nigeria range).
   - Store picker: Spotify, Apple, Audiomack, Boomplay, YouTube Music, TikTok/CapCut, Deezer, Amazon, TIDAL, Shazam and others.
   - Songwriters and composers (needed for publishing and lyrics).
3. **Video releases:** link a video to a track, upload MP4/MOV (ProRes or H.264, 1080p+), thumbnail, video ISRC, and target platforms.
4. **Lyrics:** a plain lyrics editor plus a **tap-to-sync LRC editor** (play the audio, tap each line). Language tags, including Pidgin, Yoruba, Igbo and Hausa.
5. **Royalty splits:** invite collaborators by email, assign percentages that total 100%, and split earnings automatically into each person's wallet.
6. **Smart links and pre-save:** `valvabox.net/l/lagos-nights` landing pages with store buttons.
7. **Analytics:** streams, views and earnings by store, country and month, based on imported partner reports.
8. **Wallet and payouts:** balance in ₦, transaction history, verified bank account (Paystack Resolve Account), and withdrawals by Paystack Transfer.
9. **Takedowns and edits:** request metadata changes or store removal.
10. **Support:** tickets plus a WhatsApp Business link.

### Admin side (Filament)
- **Review queue:** approve or reject releases with reasons (bad artwork, copyright risk, metadata errors). Then send to the partner API.
- Users, artists, labels, subscriptions, coupons.
- **Royalty import:** upload or fetch the partner's monthly CSV, map rows by ISRC/UPC → release → user, apply the split, and credit wallets. This runs in a queued job, in chunks.
- **Payout approvals:** auto-approve below a threshold; manual review above it or when fraud is suspected.
- FX rate setting (USD→NGN) used when crediting royalties.
- Copyright claims and takedowns, CMS pages, email broadcasts.

---

## 4. Naira payments design

### Collecting money (subscriptions and per-release fees)
- **Paystack Plans / Subscriptions** for yearly plans (Artist, Label). One-off **Paystack Transactions** for pay-per-release, video add-ons and lyrics add-ons.
- Payment channels: card (Visa, Mastercard, Verve), bank transfer, USSD, Apple Pay.
- Flow: `POST /transaction/initialize` → redirect to Paystack checkout → callback page → **always confirm on the server** with `GET /transaction/verify/:reference` **and** the webhook.
- **Webhook** `POST /webhooks/paystack`: check the `x-paystack-signature` header (HMAC-SHA512 of the raw body using your secret key). Handle `charge.success`, `subscription.create`, `subscription.disable`, `invoice.payment_failed` and `transfer.success/failed/reversed`. Make processing **idempotent** by storing the event reference and skipping repeats.
- Flutterwave uses the same interface behind a `PaymentGateway` contract, so you can switch providers or add one with a config change.

```php
interface PaymentGateway {
    public function initialize(Order $order): string;         // returns checkout URL
    public function verify(string $reference): PaymentResult;
    public function verifyWebhook(Request $request): bool;
    public function transfer(Payout $payout): TransferResult;  // artist withdrawals
    public function resolveAccount(string $acct, string $bankCode): ?string;
}
```

### Paying artists (royalties)
- DSPs pay in **USD**. The partner pays Valvabox in USD, usually 2–3 months after streams happen.
- When importing, convert USD→NGN at the admin-set rate (shown to the artist) and add a **ledger entry**. Never edit a balance directly; the balance is always `SUM(ledger)`.
- Withdrawals: minimum ₦5,000, verified bank account, Paystack **Transfer Recipient** + **Transfer**. Lock the ledger row while processing and reverse it if the transfer fails.
- Later, offer USD payouts to domiciliary accounts or Payoneer for bigger artists.

### Suggested launch pricing (adjust after checking your partner's costs)
| Plan | Price | Includes |
|---|---|---|
| Starter | ₦0/yr | ₦5,000 per single, ₦12,000 per EP/album, artist keeps 85% |
| Artist | ₦18,000/yr | Unlimited music for 1 artist, 2 videos/yr, lyrics, keeps 100% |
| Label | ₦75,000/yr | Up to 20 artists, 10 videos/yr, splits and team, keeps 100% |
| Add-ons | ₦7,500 per extra video · ₦2,000 lyrics sync service · ₦10,000 YouTube OAC request |

Store all money as **integer kobo** (`₦18,000 = 1800000`) so nothing is lost to rounding.

---

## 5. Database (core tables)

```
users                (id, name, email, phone, password, role, kyc_status, ...)
artists              (id, user_id, stage_name, spotify_id, apple_id, bio, avatar)
labels               (id, user_id, name)
plans                (id, name, price_kobo, interval, limits_json, paystack_plan_code)
subscriptions        (id, user_id, plan_id, status, gateway, gateway_ref, renews_at)
releases             (id, user_id, label_id, type[single|ep|album], title, upc, genre,
                      language, release_date, status[draft|paid|in_review|approved|
                      delivered|live|rejected|takedown], partner_release_id, artwork_path)
tracks               (id, release_id, position, title, version, isrc, audio_path,
                      duration_sec, explicit, language)
track_artists        (track_id, artist_id, role[primary|featured|remixer])
contributors         (id, track_id, name, role[composer|lyricist|producer], share)
videos               (id, track_id, release_id, isrc, video_path, thumbnail_path, status)
lyrics               (id, track_id, language, plain_text, lrc_text, status)
release_stores       (release_id, store_id, status, store_url)
stores               (id, name, code, supports_video, supports_lyrics)
splits               (id, track_id, user_id|invite_email, percent, accepted_at)
orders               (id, user_id, type, amount_kobo, status, gateway, reference)
payments             (id, order_id, gateway, reference, raw_payload, paid_at)
webhook_events       (id, gateway, event, reference UNIQUE, payload, processed_at)
royalty_imports      (id, period, source, file_path, fx_rate, status)
royalty_lines        (id, import_id, isrc, upc, store, country, units, usd_micros,
                      ngn_kobo, track_id, user_id)
ledger_entries       (id, user_id, type[royalty|withdrawal|reversal|adjustment],
                      amount_kobo (+/-), ref_type, ref_id, created_at)
bank_accounts        (id, user_id, bank_code, account_no, account_name, recipient_code)
payouts              (id, user_id, amount_kobo, fee_kobo, status, transfer_code)
smart_links          (id, release_id, slug, clicks)
tickets, notifications, audit_logs
```

---

## 6. Project structure

```
valvabox/
├── app/
│   ├── Models/                 Release, Track, Video, Lyric, Payout, LedgerEntry ...
│   ├── Payments/               PaymentGateway.php, PaystackGateway.php, FlutterwaveGateway.php
│   ├── Distribution/           DistributorClient.php (partner API), DdexBuilder.php (phase 3)
│   ├── Jobs/                   DeliverRelease, ImportRoyaltyReport, ProcessPayout, SyncStoreStatus
│   ├── Livewire/               ReleaseWizard, LyricsSyncEditor, Wallet, Analytics
│   ├── Filament/               Admin resources (ReviewQueue, Payouts, Royalties)
│   └── Http/Controllers/Webhooks/  PaystackWebhookController, PartnerWebhookController
├── database/migrations/
├── resources/views/            Blade + Tailwind (from design/ mockups)
├── routes/web.php, routes/api.php
├── public/                     → becomes public_html on cPanel
└── brand/, design/, docs/      (this repo, today)
```

---

## 7. Deploying on cPanel shared hosting

1. **PHP 8.2+** in *Select PHP Version*. Enable `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `bcmath`, `intl`, `gd`/`imagick`, `zip`, `curl`.
2. Upload the app **outside** `public_html` (for example `~/valvabox`). Then either point the domain's document root at `~/valvabox/public`, or put `public/` into `public_html` and fix the paths in `index.php`.
3. Create the MySQL DB and user in cPanel and put them in `.env`. Run `php artisan migrate --force` (through cPanel Terminal, SSH, or a one-time protected route if neither exists).
4. **Cron** (cPanel → Cron Jobs), every minute:
   ```
   * * * * * cd ~/valvabox && php artisan schedule:run >> /dev/null 2>&1
   ```
   In `routes/console.php`, schedule `queue:work --stop-when-empty --max-time=50` every minute. That replaces Supervisor.
5. **SSL:** AutoSSL / Let's Encrypt in cPanel. Force HTTPS.
6. Set `APP_ENV=production`, `APP_DEBUG=false`, then run `config:cache`, `route:cache` and `view:cache`.
7. **Backups:** daily DB dump to R2 with `spatie/laravel-backup`.

### Domain move: `valvabox.name.ng` → `valvabox.net`
- Never hard-code the domain. Use `APP_URL` and `route()` everywhere, and store file **paths** (not URLs) in the DB.
- When `.net` is ready: add it as the main domain or an addon, issue SSL, change `APP_URL`, update the Paystack callback and webhook URLs, the partner webhook, OAuth redirect URIs, and email SPF/DKIM/DMARC.
- Keep `.name.ng` renewed and **301 redirect** every path to `.net` (`RewriteRule ^(.*)$ https://valvabox.net/$1 [R=301,L]`) so old smart links keep working.

---

## 8. Roadmap

| Phase | Time | Deliverables |
|---|---|---|
| **0. Foundations** | Weeks 1–2 | Register the business with CAC. Open a corporate NGN + USD dom account. Sign up for Paystack (business KYC). Shortlist and sign a distribution partner. Brand (done: `brand/`). |
| **1. MVP** | Weeks 3–10 | Laravel app: auth, artist profile, release wizard (audio + artwork + metadata), Paystack checkout + webhooks, admin review queue, delivery through partner API, release status page. Landing page from `design/landing.html`. |
| **2. Money** | Weeks 11–14 | Royalty import, ledger, wallet, bank verification, Paystack transfers, splits. Dashboard from `design/dashboard.html`. |
| **3. Video + lyrics** | Weeks 15–18 | Video upload and delivery, lyrics and LRC sync editor, Musixmatch/LyricFind onboarding, smart links and pre-saves. |
| **4. Grow** | Month 5+ | Label accounts and teams, analytics, Flutterwave fallback, referral program, mobile-friendly PWA, then direct DSP deals and the VPS move. |

---

## 9. Legal and compliance checklist
- CAC business registration (Business Name or Ltd). An **Ltd** is better for signing DSP and partner contracts.
- Terms of Service, Distribution Agreement (non-exclusive licence from the artist), Privacy Policy.
- **NDPA 2023** (Nigeria Data Protection Act): consent, data protection officer, breach process.
- Copyright and takedown policy. Fraud rules against **artificial streaming**, which partners charge back.
- 7.5% VAT on fees once you pass the threshold. Keep payout records for tax.
- Optional: register with **COSON** / **MCSN** contacts to offer publishing admin later.

---

## 10. Brand
See `brand/BRAND.md` for the logo, colours and type.
