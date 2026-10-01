@extends('layouts.auth')
@section('title', 'Log in')
@section('content')
<h1 class="center" style="font-size:24px">Welcome back</h1>
<p class="muted center small mb">Log in to manage your releases and earnings.</p>
<form method="post" action="{{ route('login') }}" class="stack">@csrf
    <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus></div>
    <div><label>Password</label><input type="password" name="password" required></div>
    <div class="between"><label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label><a class="small" href="{{ route('password.request') }}">Forgot password?</a></div>
    <button class="btn btn-dark btn-block" type="submit">Log in</button>
    <p class="center small muted">New here? <a href="{{ route('register') }}">Create a free account</a></p>
</form>
@endsection
