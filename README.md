# Valvabox

Music, video and lyrics distribution for African artists. Pay in Naira, get paid in Naira.
Live at **valvabox.name.ng** (moving to **valvabox.net**).

## What's in this repo
| Path | Contents |
|---|---|
| `docs/PLATFORM-PLAN.md` | Full plan: how distribution works, stack, features, Naira payments (Paystack/Flutterwave), database, cPanel deployment, domain move, roadmap, legal |
| `brand/` | Logo (SVG + PNG), colours, type: see `brand/BRAND.md` |
| `design/landing.html` | Homepage mockup (open in a browser) + `landing-desktop.png`, `landing-mobile.png` |
| `design/dashboard.html` | Artist dashboard mockup + `dashboard-desktop.png` |
| `design/screens/` | Screenshots of the working app |
| `platform/` | **The Valvabox web app** (Laravel 12, PHP 8.2+, MySQL) |
| `dist/valvabox-1.0.0.zip` | **Ready-to-upload install ZIP** for cPanel (includes all dependencies) |
| `INSTALL.md` | Step-by-step cPanel installation guide |
| `build-release.sh` | Rebuilds the ZIP from `platform/` |

## Install
Upload `dist/valvabox-1.0.0.zip` to `public_html`, extract it, and open `https://your-domain/install`. Full steps are in `INSTALL.md`.

## What the app does
- **Artists:** sign up; create single/EP/album releases (3000×3000 artwork, WAV/FLAC audio, songwriter credits); write lyrics and sync them with a tap-to-sync LRC editor; upload music videos (file or Drive link); pick stores; pay in Naira; track earnings; withdraw to a Nigerian bank.
- **Payments:** Paystack (default) or Flutterwave checkout, with server-side verification and signed webhooks. Yearly plans or per-release fees. All amounts are stored in kobo.
- **Admin:** review queue; UPC/ISRC assignment; one-click delivery pack (artwork + audio + lyrics + metadata CSV/JSON) for your distribution partner; Delivered/Live status with store links; royalty CSV import (USD→NGN, plan share); withdrawal approval or automatic Paystack transfers; plans, stores and settings, all editable from the browser.
