<?php

namespace OpenKOS\Plugins\Razorpay;

use OpenKOS\Core\Contracts\PaymentGateway;
use OpenKOS\Core\Contracts\PaymentGatewayCurrencySupport;
use OpenKOS\Core\Data\Payment\CheckoutInstruction;
use OpenKOS\Core\Data\Payment\CheckoutInstructions;
use OpenKOS\Core\Data\Payment\PaymentCreationResult;
use OpenKOS\Core\Data\Payment\PaymentRequest;
use OpenKOS\Core\Data\Payment\PaymentWebhookRequest;
use OpenKOS\Core\Data\Payment\PaymentWebhookResult;
use OpenKOS\Core\Enums\PaymentStatus;
use Razorpay\Api\Api;
use OpenKOS\Core\Data\Payment\Money;
use OpenKOS\Core\Exceptions\PaymentWebhookPayloadException;
use OpenKOS\Core\Exceptions\PaymentWebhookVerificationException;

class RazorpayGateway implements PaymentGateway, PaymentGatewayCurrencySupport
{
    public function __construct(
        private array $config = [],
    ) {}

    public function key(): string
    {
        return 'razorpay';
    }

    public function displayName(): string
    {
        return 'Razorpay';
    }

    public function supportsCurrency(string $currency): bool
    {
        return strtoupper($currency) === 'INR';
    }

    /**
     * @return list<string>
     */
    public function supportedCurrencies(): array
    {
        return ['INR'];
    }

    public function configurationSchema(): array
    {
        return [
            'key_id' => [
                'label' => 'Razorpay Key ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'rzp_test_...',
                'description' => 'Your Razorpay API Key ID.',
            ],

            'key_secret' => [
                'label' => 'Razorpay Key Secret',
                'type' => 'password',
                'required' => true,
                'description' => 'Your Razorpay API Key Secret. Keep this private.',
            ],

            'webhook_secret' => [
                'label' => 'Razorpay Webhook Secret',
                'type' => 'password',
                'required' => true,
                'description' => 'Secret configured for Razorpay webhook verification.',
            ],
        ];
    }

    public function createPayment(PaymentRequest $request): PaymentCreationResult
    {
        if (! $this->supportsCurrency($request->amount->currency)) {
            throw new \InvalidArgumentException(
                "Razorpay does not support {$request->amount->currency} in this integration."
            );
        }

        $keyId = trim((string) ($this->config['key_id'] ?? ''));
        $keySecret = trim((string) ($this->config['key_secret'] ?? ''));

        if ($keyId === '' || $keySecret === '') {
            throw new \RuntimeException(
                'Razorpay API credentials are not configured.'
            );
        }

        $api = new Api($keyId, $keySecret);

        $notes = [
            'openkos_reference' => $request->reference,
        ];

        if (isset($request->metadata['invoice_id'])) {
            $notes['invoice_id'] = (string) $request->metadata['invoice_id'];
        }

        if (isset($request->metadata['invoice_reference'])) {
            $notes['invoice_reference'] = (string) $request->metadata['invoice_reference'];
        }

        $order = $api->order->create([
            'amount' => $request->amount->minorUnits,
            'currency' => $request->amount->currency,
            'receipt' => $request->reference,
            'notes' => $notes,
        ]);

        $orderId = (string) $order->id;

        if ($orderId === '') {
            throw new \RuntimeException(
                'Razorpay did not return an order ID.'
            );
        }

        return new PaymentCreationResult(
            providerReference: $orderId,
            status: PaymentStatus::Pending,
            amount: $request->amount,
            instructions: new CheckoutInstructions(
                url: null,
                entries: [
                    new CheckoutInstruction(
                        key: 'provider',
                        value: 'razorpay',
                        label: 'Payment Provider',
                    ),
                    new CheckoutInstruction(
                        key: 'key_id',
                        value: $keyId,
                        label: 'Razorpay Key ID',
                    ),
                    new CheckoutInstruction(
                        key: 'order_id',
                        value: $orderId,
                        label: 'Razorpay Order ID',
                    ),
                    new CheckoutInstruction(
                        key: 'amount',
                        value: (string) $request->amount->minorUnits,
                        label: 'Amount',
                    ),
                    new CheckoutInstruction(
                        key: 'currency',
                        value: $request->amount->currency,
                        label: 'Currency',
                    ),
                ],
            ),
            metadata: [
                'razorpay_order_id' => $orderId,
            ],
        );
    }

       public function handleCallback(
    PaymentWebhookRequest $request
): PaymentWebhookResult {
    $webhookSecret = trim((string) ($this->config['webhook_secret'] ?? ''));

    if ($webhookSecret === '') {
        throw new \RuntimeException(
            'Razorpay webhook secret is not configured.'
        );
    }

    $signature = $request->headers['x-razorpay-signature'][0] ?? null;

    if (! is_string($signature) || $signature === '') {
        throw new PaymentWebhookVerificationException(
            'Missing Razorpay webhook signature.'
        );
    }

    $expectedSignature = hash_hmac(
        'sha256',
        $request->rawBody,
        $webhookSecret,
    );

    if (! hash_equals($expectedSignature, $signature)) {
        throw new PaymentWebhookVerificationException(
            'Invalid Razorpay webhook signature.'
        );
    }

    try {
        $payload = json_decode(
            $request->rawBody,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    } catch (\JsonException $exception) {
        throw new PaymentWebhookPayloadException(
            'Invalid Razorpay webhook JSON.',
            previous: $exception,
        );
    }

    $event = $payload['event'] ?? null;

    if (! is_string($event) || $event === '') {
        throw new PaymentWebhookPayloadException(
            'Razorpay webhook event is missing.'
        );
    }

    $payment = $payload['payload']['payment']['entity'] ?? null;

    if (! is_array($payment)) {
        throw new PaymentWebhookPayloadException(
            'Razorpay payment entity is missing.'
        );
    }

    $paymentId = $payment['id'] ?? null;
    $orderId = $payment['order_id'] ?? null;
    $amount = $payment['amount'] ?? null;
    $currency = $payment['currency'] ?? null;

    if (
        ! is_string($paymentId)
        || $paymentId === ''
        || ! is_string($orderId)
        || $orderId === ''
        || ! is_int($amount)
        || ! is_string($currency)
        || $currency === ''
    ) {
        throw new PaymentWebhookPayloadException(
            'Razorpay payment data is incomplete.'
        );
    }

    $status = match ($event) {
        'payment.captured' => PaymentStatus::Settled,
        'payment.failed' => PaymentStatus::Failed,
        default => PaymentStatus::Pending,
    };

    $reference = null;

    if (
        isset($payment['notes'])
        && is_array($payment['notes'])
        && isset($payment['notes']['openkos_reference'])
        && is_string($payment['notes']['openkos_reference'])
    ) {
        $reference = $payment['notes']['openkos_reference'];
    }

    $eventReference = $request->headers['x-razorpay-event-id'][0]
        ?? hash('sha256', $request->rawBody);

    return new PaymentWebhookResult(
        eventReference: (string) $eventReference,
        providerReference: $orderId,
        status: $status,
        reference: $reference,
        amount: new Money(
            minorUnits: $amount,
            currency: strtoupper($currency),
        ),
        occurredAt: isset($payload['created_at'])
            && is_numeric($payload['created_at'])
            ? new \DateTimeImmutable('@'.((int) $payload['created_at']))
            : null,
        metadata: [
            'razorpay_payment_id' => $paymentId,
            'razorpay_event' => $event,
        ],
    );
}
}