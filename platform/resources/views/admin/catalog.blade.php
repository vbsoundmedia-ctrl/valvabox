@extends('layouts.app')
@section('title', 'Plans & stores')
@section('content')
<div class="top"><div><h1>Plans &amp; stores</h1><p>Prices are in Naira and update on the website immediately.</p></div></div>
<div class="grid g3">
    @foreach ($plans as $plan)
        <form class="card" method="post" action="{{ route('admin.plans.update', $plan) }}">@csrf @method('PUT')
            <div class="between"><h2>{{ $plan->name }}</h2><span class="badge muted">{{ $plan->slug }}</span></div>
            <label class="mt">Name</label><input type="text" name="name" value="{{ $plan->name }}" required>
            <label class="mt">Tagline</label><input type="text" name="tagline" value="{{ $plan->tagline }}">
            <label class="mt">Yearly price (₦) <span class="hint">0 = free plan</span></label><input type="number" step="0.01" name="price" value="{{ $plan->price_kobo / 100 }}" required>
            <label class="check mt"><input type="checkbox" name="unlimited_releases" value="1" @checked($plan->unlimited_releases)> Unlimited releases (no per-release fee)</label>
            <div class="form-grid mt"><div><label>Single fee (₦)</label><input type="number" step="0.01" name="single_fee" value="{{ $plan->single_fee_kobo / 100 }}"></div>
                <div><label>EP/Album fee (₦)</label><input type="number" step="0.01" name="album_fee" value="{{ $plan->album_fee_kobo / 100 }}"></div>
                <div><label>Videos / year</label><input type="number" name="videos_per_year" value="{{ $plan->videos_per_year }}"></div>
                <div><label>Artist keeps %</label><input type="number" name="royalty_share" min="1" max="100" value="{{ $plan->royalty_share }}"></div></div>
            <label class="mt">Features <span class="hint">(one per line)</span></label><textarea name="features" style="min-height:110px">{{ $plan->features }}</textarea>
            <label class="check mt"><input type="checkbox" name="is_featured" value="1" @checked($plan->is_featured)> Highlight as “Most popular”</label>
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)> Active</label>
            <button class="btn btn-dark mt btn-block">Save {{ $plan->name }}</button>
        </form>
    @endforeach
</div>
<div class="grid g2 mt">
    <div class="card"><h2>Stores</h2><p class="muted small">Disabled stores are hidden from artists.</p>
        @foreach ($stores as $s)
            <div class="list-row"><span>{{ $s->name }} @if ($s->supports_video)<span class="chip">video</span>@endif</span>
                <form method="post" action="{{ route('admin.stores.toggle', $s) }}">@csrf<button class="btn btn-sm {{ $s->is_active ? 'btn-light' : 'btn-dark' }}">{{ $s->is_active ? 'Disable' : 'Enable' }}</button></form></div>
        @endforeach
    </div>
    <form class="card" method="post" action="{{ route('admin.stores.store') }}" style="align-self:start">@csrf
        <h2>Add a store</h2>
        <label class="mt">Name</label><input type="text" name="name" required>
        <label class="mt">Code <span class="hint">(lowercase, no spaces)</span></label><input type="text" name="code" required>
        <label class="check mt"><input type="checkbox" name="supports_video" value="1"> Accepts music videos</label>
        <button class="btn btn-dark mt">Add store</button>
    </form>
</div>
@endsection
