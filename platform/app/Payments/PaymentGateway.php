<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function name(): string;

    public function isConfigured(): bool;

    /** Start a checkout and return the URL to redirect the payer to. */
    public function initialize(Payment $payment, string $callbackUrl): string;

    /** Ask the gateway whether this reference was paid. Returns the amount paid in kobo, or null if not paid. */
    public function verify(string $reference): ?int;

    public function verifyWebhookSignature(Request $request): bool;

    /** Extract [event, reference] from a webhook payload. */
    public function parseWebhook(array $payload): array;
}
