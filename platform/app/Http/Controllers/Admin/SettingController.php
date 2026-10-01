<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $values = [];
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $values[$key] = in_array($key, Setting::SECRET, true) ? '' : setting($key);
        }
        $secretsSet = collect(Setting::SECRET)->mapWithKeys(fn ($k) => [$k => setting($k, '') !== '']);

        return view('admin.settings', ['s' => $values, 'secretsSet' => $secretsSet]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'support_email' => ['required', 'email'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'payment_gateway' => ['required', 'in:paystack,flutterwave'],
            'paystack_public' => ['nullable', 'string', 'max:200'],
            'paystack_secret' => ['nullable', 'string', 'max:200'],
            'flutterwave_public' => ['nullable', 'string', 'max:200'],
            'flutterwave_secret' => ['nullable', 'string', 'max:200'],
            'flutterwave_hash' => ['nullable', 'string', 'max:200'],
            'fx_rate' => ['required', 'numeric', 'min:1'],
            'min_withdrawal' => ['required', 'numeric', 'min:0'],
            'withdrawal_fee' => ['required', 'numeric', 'min:0'],
            'video_fee' => ['required', 'numeric', 'min:0'],
        ]);

        foreach (['site_name', 'support_email', 'whatsapp', 'payment_gateway', 'paystack_public', 'flutterwave_public', 'fx_rate'] as $k) {
            Setting::put($k, $data[$k] ?? '');
        }
        foreach (Setting::SECRET as $k) {
            if (! empty($data[$k])) {          // blank = keep the saved secret
                Setting::put($k, trim($data[$k]));
            }
        }
        Setting::put('min_withdrawal_kobo', (string) to_kobo($data['min_withdrawal']));
        Setting::put('withdrawal_fee_kobo', (string) to_kobo($data['withdrawal_fee']));
        Setting::put('video_fee_kobo', (string) to_kobo($data['video_fee']));
        Setting::put('auto_transfer', $request->boolean('auto_transfer') ? '1' : '0');

        return back()->with('success', 'Settings saved.');
    }
}
