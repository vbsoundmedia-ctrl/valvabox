@extends('layouts.auth')
@section('title', 'Reset password')
@section('content')
<h1 class="center" style="font-size:22px">Forgot your password?</h1>
<p class="muted center small mb">We’ll email you a link to choose a new one.</p>
<form method="post" action="{{ route('password.email') }}" class="stack">@csrf
    <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
    <button class="btn btn-dark btn-block" type="submit">Send reset link</button>
    <p class="center small"><a href="{{ route('login') }}">Back to log in</a></p>
</form>
@endsection
