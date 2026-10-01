<?php

use App\Models\Setting;

if (! function_exists('naira')) {
    /** Format an amount in kobo as Naira, e.g. 1800000 → "₦18,000". */
    function naira(int|string|null $kobo, bool $decimals = false): string
    {
        $value = ((int) $kobo) / 100;
        $sign = $value < 0 ? '-' : '';

        return $sign.'₦'.number_format(abs($value), $decimals ? 2 : 0);
    }
}

if (! function_exists('to_kobo')) {
    function to_kobo(string|int|float|null $naira): int
    {
        return (int) round(((float) str_replace(',', '', (string) $naira)) * 100);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('status_class')) {
    function status_class(string $status): string
    {
        return match ($status) {
            'live', 'paid', 'success', 'delivered', 'active' => 'ok',
            'in_review', 'pending', 'processing', 'pending_payment', 'approved' => 'warn',
            'rejected', 'failed', 'takedown', 'suspended' => 'bad',
            default => 'muted',
        };
    }
}
