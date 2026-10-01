@extends('layouts.public')
@section('body')
<div class="auth-wrap">
    <div class="auth-card">
        <a class="brand" href="{{ route('home') }}">@include('partials.logo', ['size' => 38])<span>valva<b>box</b></span></a>
        @include('partials.flash')
        @yield('content')
    </div>
</div>
@endsection
