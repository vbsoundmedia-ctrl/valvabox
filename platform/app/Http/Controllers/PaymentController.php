<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\WebhookEvent;
use App\Payments\PaymentService;
use App\Services\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function plans(Request $request)
    {
        return view('artist.plans', [
            'plans' => Plan::where('is_active', true)->orderBy('sort')->get(),
            'user' => $request->user(),
        ]);
    }

    public function buyPlan(Request $request, Plan $plan)
    {
        abort_unless($plan->is_active && $plan->price_kobo > 0, 404);
        $payment = $this->payments->createPayment($request->user(), 'plan', $plan->id, (int) $plan->price_kobo, $plan->name.' plan (1 year)');

        return $this->redirectToGateway($payment);
    }

    public function redirectToGateway(Payment $payment)
    {
        $gateway = $this->payments->gateway($payment->gateway);
        if (! $gateway->isConfigured()) {
            return redirect()->route('payments.index')->withErrors(['payment' => 'Online payment is not set up yet. Please contact support.']);
        }
        try {
            $url = $gateway->initialize($payment, route('payments.callback', $gateway->name()));
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('payments.index')->withErrors(['payment' => 'Could not start the payment: '.$e->getMessage()]);
        }

        return redirect()->away($url);
    }

    /** The payer lands here after checkout. We never trust the query string — we ask the gateway. */
    public function callback(Request $request, string $gateway)
    {
        $reference = $request->query('reference') ?? $request->query('trxref') ?? $request->query('tx_ref');
        $payment = Payment::where('reference', (string) $reference)->where('gateway', $gateway)->first();
        if (! $payment) {
            return redirect()->route('payments.index')->withErrors(['payment' => 'Payment not found.']);
        }

        try {
            $paid = $this->payments->gateway($gateway)->verify($payment->reference);
        } catch (Throwable $e) {
            report($e);
            $paid = null;
        }

        if ($paid !== null && $this->payments->markPaid($payment, $paid, ['source' => 'callback'])) {
            return redirect($this->afterPaymentUrl($payment))->with('success', 'Payment of '.naira($payment->amount_kobo).' received. Thank you!');
        }

        return redirect($this->afterPaymentUrl($payment))->withErrors(['payment' => 'We could not confirm this payment yet. If you were debited, it will update automatically within a few minutes.']);
    }

    private function afterPaymentUrl(Payment $payment): string
    {
        return match ($payment->purpose) {
            'release' => route('releases.show', $payment->purpose_id),
            'video' => route('videos.show', $payment->purpose_id),
            default => route('payments.index'),
        };
    }

    public function index(Request $request)
    {
        return view('artist.payments', ['payments' => $request->user()->payments()->latest()->paginate(25), 'user' => $request->user()]);
    }

    public function retry(Request $request, Payment $payment)
    {
        $this->authorizeOwner($payment);
        abort_unless($payment->status === 'pending', 404);

        return $this->redirectToGateway($payment);
    }

    // ---- Webhooks (server-to-server; CSRF exempt) ----

    public function webhook(Request $request, string $gateway)
    {
        $driver = $this->payments->gateway($gateway);
        $payload = json_decode($request->getContent(), true) ?: [];
        $ok = $driver->verifyWebhookSignature($request);
        [$event, $reference] = $driver->parseWebhook($payload);

        WebhookEvent::create(['gateway' => $gateway, 'event' => $event, 'reference' => $reference, 'signature_ok' => $ok, 'payload' => $request->getContent()]);
        if (! $ok) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        // Card/bank payments
        if (in_array($event, ['charge.success', 'charge.completed'], true) && $reference) {
            $payment = Payment::where('reference', $reference)->first();
            if ($payment && $payment->status !== 'success') {
                try {
                    $paid = $driver->verify($reference); // re-check with the API, never trust the body alone
                    if ($paid !== null) {
                        $this->payments->markPaid($payment, $paid, ['source' => 'webhook']);
                    }
                } catch (Throwable $e) {
                    report($e);

                    return response()->json(['message' => 'retry'], 500);
                }
            }
        }

        // Paystack transfer results for artist withdrawals
        if ($gateway === 'paystack' && str_starts_with((string) $event, 'transfer.') && $reference) {
            $payout = Payout::where('reference', $reference)->first();
            if ($payout) {
                $wallet = app(Wallet::class);
                match ($event) {
                    'transfer.success' => $wallet->markPaid($payout),
                    'transfer.failed', 'transfer.reversed' => $wallet->refund($payout, 'failed', 'Paystack reported '.$event),
                    default => null,
                };
            }
        }

        return response()->json(['ok' => true]);
    }
}
