<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Store;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'plans' => Plan::where('is_active', true)->orderBy('sort')->get(),
            'stores' => Store::where('is_active', true)->orderBy('sort')->get(),
        ]);
    }

    public function page(string $page)
    {
        abort_unless(in_array($page, ['terms', 'privacy'], true), 404);

        return view('pages.'.$page);
    }
}
