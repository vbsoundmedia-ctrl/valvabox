<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Store;
use Illuminate\Http\Request;

/** Plans (pricing) and stores. */
class CatalogController extends Controller
{
    public function index()
    {
        return view('admin.catalog', ['plans' => Plan::orderBy('sort')->get(), 'stores' => Store::orderBy('sort')->get()]);
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'single_fee' => ['required', 'numeric', 'min:0'],
            'album_fee' => ['required', 'numeric', 'min:0'],
            'videos_per_year' => ['required', 'integer', 'min:0'],
            'royalty_share' => ['required', 'integer', 'between:1,100'],
            'features' => ['nullable', 'string', 'max:2000'],
        ]);
        $plan->update([
            'name' => $data['name'], 'tagline' => $data['tagline'], 'price_kobo' => to_kobo($data['price']),
            'single_fee_kobo' => to_kobo($data['single_fee']), 'album_fee_kobo' => to_kobo($data['album_fee']),
            'unlimited_releases' => $request->boolean('unlimited_releases'), 'videos_per_year' => $data['videos_per_year'],
            'royalty_share' => $data['royalty_share'], 'features' => $data['features'],
            'is_active' => $request->boolean('is_active'), 'is_featured' => $request->boolean('is_featured'),
        ]);

        return back()->with('success', "Plan {$plan->name} saved.");
    }

    public function storeStore(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'code' => ['required', 'alpha_dash', 'max:40', 'unique:stores,code']]);
        Store::create($data + ['supports_video' => $request->boolean('supports_video'), 'sort' => (int) Store::max('sort') + 1]);

        return back()->with('success', 'Store added.');
    }

    public function toggleStore(Store $store)
    {
        $store->update(['is_active' => ! $store->is_active]);

        return back()->with('success', $store->name.($store->is_active ? ' enabled.' : ' disabled.'));
    }
}
