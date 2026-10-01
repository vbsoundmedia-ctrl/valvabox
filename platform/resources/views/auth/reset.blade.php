@extends('layouts.auth')
@section('title', 'Choose a new password')
@section('content')
<h1 class="center" style="font-size:22px">Choose a new password</h1>
<form method="post" action="{{ route('password.update') }}" class="stack mt">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div><label>Email</label><input type="email" name="email" value="{{ old('email', $email) }}" required></div>
    <div><label>New password</label><input type="password" name="password" required minlength="8"></div>
    <div><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
    <button class="btn btn-dark btn-block" type="submit">Save password</button>
</form>
@endsection
