<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoyaltyImport;
use App\Services\RoyaltyImporter;
use Illuminate\Http\Request;
use RuntimeException;

class RoyaltyController extends Controller
{
    public function index()
    {
        return view('admin.royalties', ['imports' => RoyaltyImport::latest()->paginate(20), 'fx' => setting('fx_rate')]);
    }

    public function store(Request $request, RoyaltyImporter $importer)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt', 'max:51200'],
            'period' => ['required', 'date_format:Y-m'],
            'fx_rate' => ['required', 'numeric', 'min:1'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);
        @set_time_limit(600);
        try {
            $import = $importer->import($request->file('file')->getRealPath(), $request->file('file')->getClientOriginalName(),
                $data['period'], (float) $data['fx_rate'], $data['source'] ?? null, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return back()->with('success', "Imported {$import->rows} rows. Credited ".naira($import->credited_kobo, true)." to artist wallets. {$import->unmatched_rows} rows had no matching ISRC.");
    }

    public function template()
    {
        return response("isrc,store,country,units,amount_usd\nNGA0D2600001,Spotify,NG,15230,45.69\nNGA0D2600001,Audiomack,GH,8800,7.04\n", 200, [
            'Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="royalty-template.csv"',
        ]);
    }
}
