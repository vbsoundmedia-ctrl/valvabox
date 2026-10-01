<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('artist.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'artist_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $user->update($data);

        return back()->with('success', 'Profile saved.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        if (! Hash::check($request->current_password, $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Your current password is wrong.']);
        }
        $request->user()->update(['password' => $request->password]);

        return back()->with('success', 'Password changed.');
    }
}
