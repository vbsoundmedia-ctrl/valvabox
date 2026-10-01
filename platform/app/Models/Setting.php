<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    /** Keys stored encrypted at rest. */
    public const SECRET = ['paystack_secret', 'flutterwave_secret', 'flutterwave_hash'];

    public const DEFAULTS = [
        'site_name' => 'Valvabox',
        'support_email' => 'support@valvabox.name.ng',
        'whatsapp' => '',
        'payment_gateway' => 'paystack',
        'paystack_public' => '',
        'paystack_secret' => '',
        'flutterwave_public' => '',
        'flutterwave_secret' => '',
        'flutterwave_hash' => '',
        'fx_rate' => '1550',
        'min_withdrawal_kobo' => '500000',
        'withdrawal_fee_kobo' => '5000',
        'auto_transfer' => '0',
        'video_fee_kobo' => '750000',
    ];

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $guarded = [];

    public static function values(): array
    {
        return Cache::rememberForever('vb_settings', function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return static::query()->pluck('value', 'key')->all();
        });
    }

    public static function get(string $key, $default = null)
    {
        $value = static::values()[$key] ?? null;
        if ($value === null || $value === '') {
            return $default ?? (self::DEFAULTS[$key] ?? null);
        }
        if (in_array($key, self::SECRET, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return '';
            }
        }

        return $value;
    }

    public static function put(string $key, $value): void
    {
        if (in_array($key, self::SECRET, true) && $value !== null && $value !== '') {
            $value = Crypt::encryptString($value);
        }
        static::query()->updateOrInsert(['key' => $key], ['value' => $value]);
        Cache::forget('vb_settings');
    }
}
