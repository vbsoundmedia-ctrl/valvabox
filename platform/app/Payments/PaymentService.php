<?php

namespace App\Payments;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Release;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentService
{
    public function gateway(?string $name = null): PaymentGateway
    {
        return match ($name ?? setting('payment_gateway', 'paystack')) {
            'flutterwave' => app(FlutterwaveGateway::class),
            'paystack' => app(PaystackGateway::class),
            default => throw new InvalidArgumentException('Unknown payment gateway.'),
        };
    }

    public function createPayment(User $user, string $purpose, int $purposeId, int $amountKobo, string $description): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'reference' => 'vbx_'.strtolower(Str::random(20)),
            'gateway' => $this->gateway()->name(),
            'purpose' => $purpose,
            'purpose_id' => $purposeId,
            'amount_kobo' => $amountKobo,
            'description' => $description,
            'status' => 'pending',
        ]);
    }

    /**
     * Mark a payment as paid and apply what was bought. Safe to call many times
     * (callback + webhook both call it); only the first call has an effect.
     */
    public function markPaid(Payment $payment, int $paidKobo, array $response = []): bool
    {
        return DB::transaction(function () use ($payment, $paidKobo, $response) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->status === 'success') {
                return true;
            }
            if ($paidKobo < $payment->amount_kobo) {
                $payment->update(['status' => 'failed', 'gateway_response' => json_encode(['reason' => 'amount mismatch', 'paid' => $paidKobo] + $response)]);

                return false;
            }

            $payment->update(['status' => 'success', 'paid_at' => now(), 'gateway_response' => json_encode($response)]);
            $this->fulfil($payment);

            return true;
        });
    }

    private function fulfil(Payment $payment): void
    {
        $user = $payment->user;

        switch ($payment->purpose) {
            case 'plan':
                $plan = Plan::findOrFail($payment->purpose_id);
                $start = ($user->hasActivePlan() && $user->plan_id === $plan->id) ? $user->plan_expires_at : now();
                $user->forceFill(['plan_id' => $plan->id, 'plan_expires_at' => $start->copy()->addYear()])->save();
                break;

            case 'release':
                $release = Release::findOrFail($payment->purpose_id);
                $release->forceFill(['paid_at' => now(), 'status' => 'in_review', 'submitted_at' => now(), 'rejection_reason' => null])->save();
                break;

            case 'video':
                $video = Video::findOrFail($payment->purpose_id);
                $video->forceFill(['paid_at' => now(), 'status' => 'in_review', 'submitted_at' => now(), 'rejection_reason' => null])->save();
                break;
        }
    }

    /** Fee for submitting this release under the user's current plan (0 when covered). */
    public function releaseFee(User $user, Release $release): int
    {
        if ($release->paid_at) {
            return 0; // already paid once — resubmissions after changes are free
        }
        $plan = $user->currentPlan();
        if ($plan->unlimited_releases) {
            return 0;
        }

        return (int) ($release->type === 'single' ? $plan->single_fee_kobo : $plan->album_fee_kobo);
    }

    public function videoFee(User $user, Video $video): int
    {
        if ($video->paid_at) {
            return 0;
        }
        $plan = $user->currentPlan();
        if ($plan->videos_per_year > 0 && $user->hasActivePlan()) {
            $used = $user->videos()->whereNotNull('submitted_at')->where('submitted_at', '>=', now()->subYear())
                ->where('id', '!=', $video->id)->count();
            if ($used < $plan->videos_per_year) {
                return 0;
            }
        }

        return (int) setting('video_fee_kobo');
    }
}
