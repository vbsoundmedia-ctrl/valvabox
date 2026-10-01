@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="top"><div><h1>Settings</h1><p>Payments, payouts and site details.</p></div></div>
<form method="post" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')
<div class="grid g2">
    <div class="card">
        <h2>Naira payments</h2>
        <p class="muted small mb">Get your keys from <b>dashboard.paystack.com → Settings → API Keys &amp; Webhooks</b>. Use <code>sk_test_…</code> to test, then switch to <code>sk_live_…</code> once your business is verified.</p>
        <label>Checkout gateway</label>
        <select name="payment_gateway"><option value="paystack" @selected($s['payment_gateway'] === 'paystack')>Paystack (recommended)</option><option value="flutterwave" @selected($s['payment_gateway'] === 'flutterwave')>Flutterwave</option></select>

        <h3 class="mt2">Paystack</h3>
        <label class="mt">Public key</label><input type="text" name="paystack_public" value="{{ $s['paystack_public'] }}" placeholder="pk_live_…">
        <label class="mt">Secret key @if ($secretsSet['paystack_secret'])<span class="badge ok">saved</span>@endif</label>
        <input type="password" name="paystack_secret" placeholder="{{ $secretsSet['paystack_secret'] ? 'Leave blank to keep the saved key' : 'sk_live_…' }}" autocomplete="new-password">
        <div class="help">Webhook URL to paste into Paystack:<br><code>{{ route('webhooks', 'paystack') }}</code><br>Callback URL is set automatically.</div>

        <h3 class="mt2">Flutterwave <span class="hint muted small">(optional backup)</span></h3>
        <label class="mt">Public key</label><input type="text" name="flutterwave_public" value="{{ $s['flutterwave_public'] }}" placeholder="FLWPUBK-…">
        <label class="mt">Secret key @if ($secretsSet['flutterwave_secret'])<span class="badge ok">saved</span>@endif</label><input type="password" name="flutterwave_secret" placeholder="{{ $secretsSet['flutterwave_secret'] ? 'Leave blank to keep' : 'FLWSECK-…' }}" autocomplete="new-password">
        <label class="mt">Webhook secret hash @if ($secretsSet['flutterwave_hash'])<span class="badge ok">saved</span>@endif</label><input type="password" name="flutterwave_hash" placeholder="{{ $secretsSet['flutterwave_hash'] ? 'Leave blank to keep' : 'Any long random text, same as in Flutterwave' }}" autocomplete="new-password">
        <div class="help">Webhook URL: <code>{{ route('webhooks', 'flutterwave') }}</code></div>
    </div>
    <div>
        <div class="card">
            <h2>Payouts &amp; royalties</h2>
            <div class="form-grid mt">
                <div><label>USD → NGN rate</label><input type="number" step="0.0001" name="fx_rate" value="{{ $s['fx_rate'] }}" required><div class="help">Default for royalty imports.</div></div>
                <div><label>Video fee (₦)</label><input type="number" step="0.01" name="video_fee" value="{{ $s['video_fee_kobo'] / 100 }}" required><div class="help">When the plan has no free videos left.</div></div>
                <div><label>Minimum withdrawal (₦)</label><input type="number" step="0.01" name="min_withdrawal" value="{{ $s['min_withdrawal_kobo'] / 100 }}" required></div>
                <div><label>Withdrawal fee (₦)</label><input type="number" step="0.01" name="withdrawal_fee" value="{{ $s['withdrawal_fee_kobo'] / 100 }}" required></div>
            </div>
            <label class="check mt"><input type="checkbox" name="auto_transfer" value="1" @checked($s['auto_transfer'] === '1')> <span>Send withdrawals automatically via Paystack Transfers<br><span class="help">Needs a funded Paystack balance and transfer OTP turned off. Leave unticked to approve each payout yourself.</span></span></label>
        </div>
        <div class="card mt">
            <h2>Site</h2>
            <label class="mt">Site name</label><input type="text" name="site_name" value="{{ $s['site_name'] }}" required>
            <label class="mt">Support email</label><input type="email" name="support_email" value="{{ $s['support_email'] }}" required>
            <label class="mt">WhatsApp number <span class="hint">(optional, international format)</span></label><input type="text" name="whatsapp" value="{{ $s['whatsapp'] }}" placeholder="+2348030000000">
        </div>
    </div>
</div>
<button class="btn btn-green mt">Save settings</button>
</form>
@endsection
