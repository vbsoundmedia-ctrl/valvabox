<?php

namespace App\Services;

use App\Models\RoyaltyImport;
use App\Models\RoyaltyLine;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Imports a distributor royalty report (CSV) and credits artists' wallets.
 *
 * Required columns (header names are matched loosely, case-insensitive):
 *   isrc, amount_usd   (also accepted: revenue, earnings, net, amount, royalty)
 * Optional: store (dsp, platform, service), country (territory), units (streams, quantity, plays)
 */
class RoyaltyImporter
{
    private const ALIASES = [
        'isrc' => ['isrc', 'track isrc', 'isrc code'],
        'amount' => ['amount_usd', 'amount usd', 'usd', 'revenue', 'earnings', 'net', 'net revenue', 'amount', 'royalty', 'net amount', 'total'],
        'store' => ['store', 'dsp', 'platform', 'service', 'retailer', 'shop'],
        'country' => ['country', 'territory', 'country code'],
        'units' => ['units', 'streams', 'quantity', 'plays', 'qty', 'downloads'],
    ];

    public function import(string $path, string $fileName, string $period, float $fxRate, ?string $source, ?int $adminId): RoyaltyImport
    {
        $fh = fopen($path, 'r');
        if (! $fh) {
            throw new RuntimeException('Could not read the file.');
        }
        $first = fgets($fh);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : (substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',');
        rewind($fh);

        $header = fgetcsv($fh, 0, $delimiter);
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header ?: []);
        $cols = [];
        foreach (self::ALIASES as $key => $names) {
            foreach ($names as $name) {
                $i = array_search($name, $header, true);
                if ($i !== false) {
                    $cols[$key] = $i;
                    break;
                }
            }
        }
        if (! isset($cols['isrc'], $cols['amount'])) {
            throw new RuntimeException('The CSV must have an "isrc" column and an amount column (e.g. "amount_usd" or "revenue"). Found: '.implode(', ', $header));
        }

        return DB::transaction(function () use ($fh, $delimiter, $cols, $fileName, $period, $fxRate, $source, $adminId) {
            $import = RoyaltyImport::create([
                'period' => $period, 'source' => $source, 'file_name' => $fileName, 'fx_rate' => $fxRate, 'created_by' => $adminId,
            ]);

            $tracks = [];
            $perUser = [];
            $rows = $unmatched = 0;
            $totalUsd = 0.0;
            $buffer = [];

            while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $rows++;
                $isrc = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($row[$cols['isrc']] ?? '')));
                $usd = (float) str_replace([',', '$', ' '], '', (string) ($row[$cols['amount']] ?? 0));
                $totalUsd += $usd;

                if (! array_key_exists($isrc, $tracks)) {
                    $tracks[$isrc] = $isrc === '' ? null : Track::with('release')->where('isrc', $isrc)->first();
                }
                $track = $tracks[$isrc];
                $userId = $track?->release?->user_id;
                $kobo = 0;
                if ($userId) {
                    $perUser[$userId] = ($perUser[$userId] ?? 0) + $usd;
                    $kobo = (int) round($usd * $fxRate * 100);
                } else {
                    $unmatched++;
                }

                $buffer[] = [
                    'royalty_import_id' => $import->id, 'track_id' => $track?->id, 'user_id' => $userId, 'isrc' => $isrc ?: null,
                    'store' => isset($cols['store']) ? mb_substr(trim((string) ($row[$cols['store']] ?? '')), 0, 60) : null,
                    'country' => isset($cols['country']) ? mb_substr(trim((string) ($row[$cols['country']] ?? '')), 0, 10) : null,
                    'units' => isset($cols['units']) ? (int) str_replace(',', '', (string) ($row[$cols['units']] ?? 0)) : 0,
                    'amount_usd' => $usd, 'amount_kobo' => $kobo, 'created_at' => now(), 'updated_at' => now(),
                ];
                if (count($buffer) >= 500) {
                    RoyaltyLine::insert($buffer);
                    $buffer = [];
                }
            }
            if ($buffer) {
                RoyaltyLine::insert($buffer);
            }
            fclose($fh);

            $wallet = app(Wallet::class);
            $credited = 0;
            foreach ($perUser as $userId => $usd) {
                $user = User::find($userId);
                $share = (int) $user->currentPlan()->royalty_share;
                $kobo = (int) floor($usd * $fxRate * 100 * $share / 100);
                if ($kobo > 0) {
                    $wallet->credit($user, $kobo, 'royalty', "Royalties for {$period} (".number_format($usd, 2)." USD @ ₦{$fxRate}, {$share}% share)", $import);
                    $credited += $kobo;
                }
            }

            $import->update(['rows' => $rows, 'unmatched_rows' => $unmatched, 'total_usd' => $totalUsd, 'credited_kobo' => $credited]);

            return $import;
        });
    }
}
