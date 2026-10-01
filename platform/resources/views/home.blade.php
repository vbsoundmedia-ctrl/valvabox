@extends('layouts.public')
@section('body')
<header class="hero">
    <div class="wrap">
        <nav class="pnav">
            <a class="brand" href="{{ route('home') }}">@include('partials.logo', ['size' => 40])<span>valva<b>box</b></span></a>
            <div class="links">
                <a href="#features">Music</a><a href="#features">Video</a><a href="#features">Lyrics</a><a href="#pricing">Pricing</a>
                @auth
                    <a class="btn btn-green" href="{{ route('dashboard') }}">My dashboard</a>
                @else
                    <a href="{{ route('login') }}">Log in</a><a class="btn btn-green" href="{{ route('register') }}">Start releasing</a>
                @endauth
            </div>
        </nav>
        <div class="hero-grid">
            <div>
                <span class="pill">🇳🇬 Built in Nigeria · Pay in <b>₦ Naira</b></span>
                <h1>Your music. Every store. <span class="grad">Paid in Naira.</span></h1>
                <p class="lead">Distribute songs, music videos and lyrics to Spotify, Apple Music, Audiomack, Boomplay, YouTube, TikTok and more. Keep up to 100% of your royalties and withdraw straight to your Nigerian bank account.</p>
                <div class="cta">
                    <a class="btn btn-green" href="{{ route('register') }}">Upload your first release</a>
                    <a class="btn btn-ghost" href="#pricing">See ₦ pricing</a>
                </div>
                <div class="trust"><span>✓ Pay with card, bank transfer or USSD</span><span>✓ Free ISRC &amp; UPC codes</span><span>✓ Lyrics on Spotify &amp; Apple Music</span></div>
            </div>
            <div class="hero-card">
                <div class="row">
                    <span class="cover" style="width:88px;height:88px;border-radius:14px;background:conic-gradient(from 200deg,#8B5CF6,#19D27F,#FFC233,#8B5CF6)"></span>
                    <div><h3 style="font-size:19px">Your next hit</h3><div class="muted small">Single · Afrobeats</div><span class="badge ok" style="margin-top:6px">● Live in {{ $stores->count() }} stores</span></div>
                </div>
                <div class="stores">
                    @foreach ($stores->take(9) as $s)<div class="store">{{ \Illuminate\Support\Str::before($s->name, ' (') }} <i>✓</i></div>@endforeach
                </div>
                <div class="earn">
                    <div><small>Withdraw anytime to</small><strong>Any Nigerian bank</strong></div>
                    <a class="btn btn-green btn-sm" href="{{ route('register') }}">Get started</a>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="block" id="features">
    <div class="wrap">
        <div class="eyebrow">One upload, three formats</div>
        <h2>Music, video and lyrics, distributed together.</h2>
        <p class="sub">Most distributors stop at audio. {{ setting('site_name') }} ships the whole release: your track, the official video and time-synced lyrics.</p>
        <div class="grid g3 mt2">
            <div class="feat"><div class="ico" style="background:#EEE8FF">🎵</div><h3>Music distribution</h3>
                <p class="muted">Singles, EPs and albums to the major DSPs and the African stores that matter.</p>
                <ul><li>Spotify, Apple, Audiomack, Boomplay</li><li>Free ISRC &amp; UPC</li><li>Release on your chosen date</li><li>Monthly earnings in Naira</li></ul></div>
            <div class="feat"><div class="ico" style="background:#E6FBF1">🎬</div><h3>Video distribution</h3>
                <p class="muted">Get your music video onto YouTube, Apple Music, TIDAL and more.</p>
                <ul><li>YouTube delivery &amp; Content ID</li><li>Apple Music &amp; TIDAL video</li><li>Video ISRC included</li><li>Large files via Drive/Dropbox link</li></ul></div>
            <div class="feat"><div class="ico" style="background:#FFF6DB">📝</div><h3>Lyrics distribution</h3>
                <p class="muted">Paste your lyrics, sync them line-by-line in our editor, and we deliver them.</p>
                <ul><li>Spotify &amp; Instagram lyrics</li><li>Apple Music synced lyrics</li><li>Built-in tap-to-sync editor</li><li>Pidgin, Yoruba, Igbo, Hausa supported</li></ul></div>
        </div>
    </div>
</section>

<section class="block" id="pricing" style="background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
    <div class="wrap">
        <div class="eyebrow">Simple Naira pricing</div>
        <h2>Pay in Naira. Keep your royalties.</h2>
        <p class="sub">No dollar card needed. Pay with card, bank transfer or USSD.</p>
        <div class="grid g3 mt2" style="align-items:stretch">
            @foreach ($plans as $plan)
                <div class="plan {{ $plan->is_featured ? 'hot' : '' }}">
                    @if ($plan->is_featured)<span class="ribbon">Most popular</span>@endif
                    <h3>{{ $plan->name }}</h3>
                    <div class="price">{{ naira($plan->price_kobo) }} <small>/ year</small></div>
                    <p class="muted small">{{ $plan->tagline }}</p>
                    <ul>@foreach ($plan->featureList() as $f)<li>{{ $f }}</li>@endforeach</ul>
                    <a class="btn {{ $plan->is_featured ? 'btn-green' : 'btn-dark' }} btn-block" href="{{ route('register') }}">{{ $plan->price_kobo ? 'Choose '.$plan->name : 'Get started free' }}</a>
                </div>
            @endforeach
        </div>
        <div class="paybadges"><span>Paystack</span><span>Flutterwave</span><span>Visa · Mastercard · Verve</span><span>Bank transfer</span><span>USSD</span></div>
    </div>
</section>

<section class="block">
    <div class="wrap">
        <div class="eyebrow">How it works</div>
        <h2>Live in stores in four steps.</h2>
        <div class="grid g4 mt2 howto">
            <div class="card"><h3 class="mt">Create your account</h3><p class="muted small">Sign up free and set up your artist profile.</p></div>
            <div class="card"><h3 class="mt">Upload your release</h3><p class="muted small">Add audio, artwork, video and lyrics. We check everything before delivery.</p></div>
            <div class="card"><h3 class="mt">Pay in Naira</h3><p class="muted small">Check out with Paystack or Flutterwave in seconds.</p></div>
            <div class="card"><h3 class="mt">Get paid</h3><p class="muted small">Track earnings and withdraw to any Nigerian bank.</p></div>
        </div>
    </div>
</section>

<footer class="site">
    <div class="wrap between">
        <a class="brand" href="{{ route('home') }}" style="font-size:20px">@include('partials.logo', ['size' => 28])<span>valva<b>box</b></span></a>
        <span>© {{ date('Y') }} {{ setting('site_name') }} · <a href="{{ route('page', 'terms') }}">Terms</a><a href="{{ route('page', 'privacy') }}">Privacy</a><a href="mailto:{{ setting('support_email') }}">Contact</a>
            @if (setting('whatsapp'))<a href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp')) }}">WhatsApp</a>@endif</span>
    </div>
</footer>
@endsection
