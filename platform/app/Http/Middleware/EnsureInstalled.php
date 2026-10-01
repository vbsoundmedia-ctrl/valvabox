<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InstallController;
use Closure;
use Illuminate\Http\Request;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $installing = $request->is('install') || $request->is('install/*');

        if (! InstallController::isInstalled() && ! $installing) {
            return redirect('/install');
        }
        if (InstallController::isInstalled() && $installing) {
            abort(404);
        }

        return $next($request);
    }
}
