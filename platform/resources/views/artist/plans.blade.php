@extends('layouts.app')
@section('title', 'Plans')
@section('content')
<div class="top"><div><h1>Plans</h1><p>Pay once a year in Naira. Card, bank transfer or USSD.</p></div></div>
@if ($user->hasActivePlan())
    <div class="alert alert-ok">You’re on the <b>{{ $user->plan->name }}</b> plan until {{ $user->plan_expires_at->format('j M Y') }}. Paying again adds another year.</div>
@endif
<div class="grid g3" style="align-items:stretch">
    @foreach ($plans as $plan)
        <div class="plan {{ $plan->is_featured ? 'hot' : '' }}">
            @if ($plan->is_featured)<span class="ribbon">Most popular</span>@endif
            <h3>{{ $plan->name }}</h3>
            <div class="price">{{ naira($plan->price_kobo) }} <small>/ year</small></div>
            <p class="muted small">{{ $plan->tagline }}</p>
            <ul>@foreach ($plan->featureList() as $f)<li>{{ $f }}</li>@endforeach</ul>
            @if ($plan->price_kobo > 0)
                <form method="post" action="{{ route('plans.buy', $plan) }}">@csrf
                    <button class="btn {{ $plan->is_featured ? 'btn-green' : 'btn-dark' }} btn-block" type="submit">
                        {{ $user->hasActivePlan() && $user->plan_id === $plan->id ? 'Renew' : 'Pay' }} {{ naira($plan->price_kobo) }}</button></form>
            @else
                <span class="btn btn-light btn-block" style="cursor:default">{{ $user->hasActivePlan() ? 'Free plan' : 'Your current plan' }}</span>
            @endif
        </div>
    @endforeach
</div>
@endsection
