<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\Wallet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()->withCount('releases');
        if ($s = $request->query('q')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('artist_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
        }

        return view('admin.users.index', ['users' => $q->latest()->paginate(30)->withQueryString()]);
    }

    public function show(User $user)
    {
        return view('admin.users.show', [
            'user' => $user->load('bankAccount', 'plan'),
            'balance' => $user->balanceKobo(),
            'releases' => $user->releases()->latest()->get(),
            'payments' => $user->payments()->latest()->limit(20)->get(),
            'ledger' => $user->ledger()->latest()->limit(30)->get(),
            'plans' => Plan::orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['artist', 'admin'])],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'plan_expires_at' => ['nullable', 'date'],
        ]);
        if ($user->id === $request->user()->id && ($data['role'] !== 'admin' || $data['status'] !== 'active')) {
            return back()->withErrors(['role' => 'You can’t remove your own admin access.']);
        }
        $user->forceFill($data)->save();

        return back()->with('success', 'User updated.');
    }

    public function adjust(Request $request, User $user, Wallet $wallet)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'not_in:0'], 'description' => ['required', 'string', 'max:200']]);
        $wallet->credit($user, to_kobo($data['amount']), 'adjustment', $data['description']);

        return back()->with('success', 'Wallet adjusted by '.naira(to_kobo($data['amount']), true).'.');
    }
}
