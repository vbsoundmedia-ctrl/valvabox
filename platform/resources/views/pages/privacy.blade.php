@extends('layouts.public')
@section('title', 'Privacy Policy · '.setting('site_name'))
@section('body')
<div class="wrap" style="padding:40px 16px">
    <a class="brand" href="{{ route('home') }}" style="color:var(--ink)">@include('partials.logo', ['size' => 32])<span>valva<b style="color:var(--green-d)">box</b></span></a>
    <div class="prose mt2">
        <h1>Privacy Policy</h1>
        <p class="muted">Template written with the Nigeria Data Protection Act 2023 in mind — have it reviewed before launch.</p>
        <h2>What we collect</h2><ul><li>Account details: name, artist name, email, phone.</li><li>Your uploads and their metadata.</li><li>Bank details for payouts, verified through our payment partner.</li><li>Payment records (we never see or store your card number).</li></ul>
        <h2>Why</h2><p>To distribute your content, pay you royalties, process payments, prevent fraud and meet legal obligations.</p>
        <h2>Who we share it with</h2><p>Digital stores and our distribution partner (content and metadata), Paystack/Flutterwave (payments and payouts), and authorities where the law requires it. We do not sell your data.</p>
        <h2>Your rights</h2><p>You can access, correct or delete your data, or object to processing, by emailing <a href="mailto:{{ setting('support_email') }}">{{ setting('support_email') }}</a>. Records needed for tax and accounting are kept as required by law.</p>
        <h2>Security</h2><p>Files are stored privately, connections are encrypted, and payment secrets are encrypted at rest.</p>
    </div>
</div>
@endsection
