<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->status === 'suspended') {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'Your account is suspended. Contact support.']);
        }

        return $next($request);
    }
}
