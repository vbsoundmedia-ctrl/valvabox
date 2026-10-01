@extends('layouts.auth')
@section('title', 'Create account')
@section('content')
<h1 class="center" style="font-size:24px">Start releasing</h1>
<p class="muted center small mb">Free to join. Pay only when you release.</p>
<form method="post" action="{{ route('register') }}" class="stack">@csrf
    <div><label>Your full name</label><input type="text" name="name" value="{{ old('name') }}" required></div>
    <div><label>Artist / stage name</label><input type="text" name="artist_name" value="{{ old('artist_name') }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
    <div><label>Phone <span class="hint">(WhatsApp, optional)</span></label><input type="tel" name="phone" value="{{ old('phone') }}" placeholder="0803 000 0000"></div>
    <div><label>Password</label><input type="password" name="password" required minlength="8"></div>
    <div><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
    <label class="check"><input type="checkbox" name="terms" value="1" required> <span>I agree to the <a href="{{ route('page', 'terms') }}" target="_blank">Terms</a> and <a href="{{ route('page', 'privacy') }}" target="_blank">Privacy Policy</a></span></label>
    <button class="btn btn-green btn-block" type="submit">Create my account</button>
    <p class="center small muted">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</form>
@endsection
