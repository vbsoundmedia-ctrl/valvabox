@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<div class="top"><div><h1>Profile</h1></div></div>
<div class="grid g2">
    <form class="card" method="post" action="{{ route('profile.update') }}">@csrf @method('PUT')
        <h2>Your details</h2>
        <div class="grid mt" style="gap:14px">
            <div><label>Full name</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
            <div><label>Artist / stage name</label><input type="text" name="artist_name" value="{{ old('artist_name', $user->artist_name) }}"></div>
            <div><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
            <div><label>Phone</label><input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"></div>
        </div>
        <button class="btn btn-dark mt" type="submit">Save</button>
    </form>
    <form class="card" method="post" action="{{ route('profile.password') }}">@csrf @method('PUT')
        <h2>Change password</h2>
        <div class="grid mt" style="gap:14px">
            <div><label>Current password</label><input type="password" name="current_password" required></div>
            <div><label>New password</label><input type="password" name="password" required minlength="8"></div>
            <div><label>Confirm new password</label><input type="password" name="password_confirmation" required></div>
        </div>
        <button class="btn btn-dark mt" type="submit">Change password</button>
    </form>
</div>
@endsection
