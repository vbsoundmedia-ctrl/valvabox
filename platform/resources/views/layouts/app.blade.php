@php($u = auth()->user())
@php($isAdminArea = request()->routeIs('admin.*'))
<!doctype html>
<html lang="en">
<head>
    @include('partials.head')
    <title>@yield('title') · {{ setting('site_name') }}</title>
</head>
<body>
<div class="mobile-bar">
    <a class="brand" href="{{ route('home') }}">@include('partials.logo', ['size' => 30])<span>valva<b>box</b></span></a>
    <button type="button" data-toggle-side aria-label="Menu">☰</button>
</div>
<div class="app">
    <aside class="side">
        <a class="brand" href="{{ route('home') }}">@include('partials.logo', ['size' => 34])<span>valva<b>box</b></span></a>
        @if ($isAdminArea)
            <a class="nav {{ request()->routeIs('admin.dashboard') ? 'on' : '' }}" href="{{ route('admin.dashboard') }}"><span class="ico">▣</span> Overview</a>
            <a class="nav {{ request()->routeIs('admin.releases.*') ? 'on' : '' }}" href="{{ route('admin.releases.index') }}"><span class="ico">♪</span> Releases
                @php($q = \App\Models\Release::where('status', 'in_review')->count())@if ($q)<span class="count">{{ $q }}</span>@endif</a>
            <a class="nav {{ request()->routeIs('admin.videos.*') ? 'on' : '' }}" href="{{ route('admin.videos.index') }}"><span class="ico">▶</span> Videos
                @php($q = \App\Models\Video::where('status', 'in_review')->count())@if ($q)<span class="count">{{ $q }}</span>@endif</a>
            <a class="nav {{ request()->routeIs('admin.users.*') ? 'on' : '' }}" href="{{ route('admin.users.index') }}"><span class="ico">☺</span> Artists &amp; users</a>
            <a class="nav {{ request()->routeIs('admin.payments') ? 'on' : '' }}" href="{{ route('admin.payments') }}"><span class="ico">₦</span> Payments</a>
            <a class="nav {{ request()->routeIs('admin.payouts') ? 'on' : '' }}" href="{{ route('admin.payouts') }}"><span class="ico">⇣</span> Withdrawals
                @php($q = \App\Models\Payout::where('status', 'pending')->count())@if ($q)<span class="count">{{ $q }}</span>@endif</a>
            <a class="nav {{ request()->routeIs('admin.royalties') ? 'on' : '' }}" href="{{ route('admin.royalties') }}"><span class="ico">↥</span> Royalty import</a>
            <a class="nav {{ request()->routeIs('admin.catalog') ? 'on' : '' }}" href="{{ route('admin.catalog') }}"><span class="ico">☰</span> Plans &amp; stores</a>
            <a class="nav {{ request()->routeIs('admin.settings') ? 'on' : '' }}" href="{{ route('admin.settings') }}"><span class="ico">⚙</span> Settings</a>
            <div class="sect">Switch</div>
            <a class="nav" href="{{ route('dashboard', ['artist' => 1]) }}"><span class="ico">↺</span> Artist view</a>
        @else
            <a class="nav {{ request()->routeIs('dashboard') ? 'on' : '' }}" href="{{ route('dashboard', $u->isAdmin() ? ['artist' => 1] : []) }}"><span class="ico">▣</span> Dashboard</a>
            <a class="nav {{ request()->routeIs('releases.*', 'tracks.*') ? 'on' : '' }}" href="{{ route('releases.index') }}"><span class="ico">♪</span> Music releases</a>
            <a class="nav {{ request()->routeIs('videos.*') ? 'on' : '' }}" href="{{ route('videos.index') }}"><span class="ico">▶</span> Music videos</a>
            <a class="nav {{ request()->routeIs('wallet') ? 'on' : '' }}" href="{{ route('wallet') }}"><span class="ico">₦</span> Earnings &amp; wallet</a>
            <a class="nav {{ request()->routeIs('plans') ? 'on' : '' }}" href="{{ route('plans') }}"><span class="ico">★</span> Plans</a>
            <a class="nav {{ request()->routeIs('payments.*') ? 'on' : '' }}" href="{{ route('payments.index') }}"><span class="ico">⎘</span> Payments</a>
            <a class="nav {{ request()->routeIs('profile') ? 'on' : '' }}" href="{{ route('profile') }}"><span class="ico">⚙</span> Profile</a>
            @if ($u->isAdmin())
                <div class="sect">Admin</div>
                <a class="nav" href="{{ route('admin.dashboard') }}"><span class="ico">⛭</span> Admin panel</a>
            @endif
        @endif
        <div class="foot">
            @unless ($isAdminArea)
                @php($plan = $u->currentPlan())
                <div class="plan-box"><strong>{{ $plan->name }} plan</strong>
                    @if ($u->hasActivePlan()) Renews {{ $u->plan_expires_at->format('j M Y') }} @else <a href="{{ route('plans') }}" style="color:var(--green)">Upgrade to keep 100%</a> @endif
                </div>
            @endunless
            <div class="small" style="padding:0 12px 8px">{{ $u->displayName() }}<br><span style="color:#6F6994">{{ $u->email }}</span></div>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="nav btn-sm btn-ghost btn btn-block" type="submit">Log out</button></form>
        </div>
    </aside>
    <main class="main">
        @include('partials.flash')
        @yield('content')
    </main>
</div>
</body>
</html>
