@extends('layouts.public')
@section('title', 'Terms of Service · '.setting('site_name'))
@section('body')
<div class="wrap" style="padding:40px 16px">
    <a class="brand" href="{{ route('home') }}" style="color:var(--ink)">@include('partials.logo', ['size' => 32])<span>valva<b style="color:var(--green-d)">box</b></span></a>
    <div class="prose mt2">
        <h1>Terms of Service</h1>
        <p class="muted">Template — have a Nigerian lawyer review before launch.</p>
        <h2>1. The service</h2><p>{{ setting('site_name') }} distributes music, music videos and lyrics that you upload to digital stores and platforms, and pays you the royalties we receive for them.</p>
        <h2>2. Your rights and licence</h2><p>You confirm that you own or control all rights in everything you upload (recordings, compositions, artwork, video, lyrics) and that it does not infringe anyone else's rights. You grant {{ setting('site_name') }} a non-exclusive licence to reproduce and deliver that content to the stores you choose, for as long as it is distributed.</p>
        <h2>3. Fees and payment</h2><p>Plan and release fees are shown in Naira and are paid in advance through our payment partners. Fees are non-refundable once a release has been delivered to stores.</p>
        <h2>4. Royalties</h2><p>Stores pay us in arrears, usually 2–3 months after streams happen. We credit your wallet in Naira at the exchange rate shown on the statement, keeping the share stated in your plan. You can withdraw once you reach the minimum.</p>
        <h2>5. Prohibited content and fraud</h2><p>No artificial streaming, bots, copied or unlicensed samples, misleading metadata or hateful content. We may remove content, withhold royalties linked to fraud and close accounts that break these rules.</p>
        <h2>6. Takedowns</h2><p>You can ask us to remove a release at any time. Stores may take a few weeks to remove it.</p>
        <h2>7. Liability</h2><p>Stores decide whether and how to list content. We are not responsible for their decisions or downtime. Our total liability is limited to the fees you paid us in the previous 12 months.</p>
        <h2>8. Governing law</h2><p>These terms are governed by the laws of the Federal Republic of Nigeria.</p>
        <p>Questions: <a href="mailto:{{ setting('support_email') }}">{{ setting('support_email') }}</a></p>
    </div>
</div>
@endsection
