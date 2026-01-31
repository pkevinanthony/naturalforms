<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantSubscription;
use App\Models\Tenant;
use App\Services\NMI\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle NMI webhook events
     */
    public function handleNMI(Request $request): Response
    {
        // Log incoming webhook for debugging
        Log::info('NMI Webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        // Verify webhook signature if configured
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('NMI Webhook signature verification failed');
            return response('Unauthorized', 401);
        }

        $eventType = $request->input('event_type') ?? $request->input('action');
        $data = $request->all();

        try {
            switch ($eventType) {
                case 'recurring.success':
                case 'subscription.charge.success':
                    $this->handleSubscriptionChargeSuccess($data);
                    break;

                case 'recurring.failure':
                case 'subscription.charge.failure':
                    $this->handleSubscriptionChargeFailure($data);
                    break;

                case 'recurring.cancelled':
                case 'subscription.cancelled':
                    $this->handleSubscriptionCancelled($data);
                    break;

                case 'transaction.sale.success':
                    $this->handleTransactionSuccess($data);
                    break;

                case 'transaction.sale.failure':
                    $this->handleTransactionFailure($data);
                    break;

                case 'customer_vault.create.success':
                    $this->handleVaultCreated($data);
                    break;

                case 'customer_vault.update.success':
                    $this->handleVaultUpdated($data);
                    break;

                default:
                    Log::info('Unhandled NMI webhook event', ['type' => $eventType]);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('NMI Webhook processing error', [
                'event' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Error processing webhook', 500);
        }
    }

    /**
     * Verify webhook signature
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('nmi.webhook_secret');

        // If no secret configured, skip verification (not recommended for production)
        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-NMI-Signature');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle successful subscription charge
     */
    protected function handleSubscriptionChargeSuccess(array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? null;

        if (!$subscriptionId) {
            Log::warning('Subscription charge success without subscription_id', $data);
            return;
        }

        $subscription = TenantSubscription::where('nmi_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for charge success', ['subscription_id' => $subscriptionId]);
            return;
        }

        // Renew the subscription period
        $this->subscriptionService->processRenewal($subscription);

        Log::info('Subscription renewed successfully', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'amount' => $data['amount'] ?? null,
        ]);
    }

    /**
     * Handle failed subscription charge
     */
    protected function handleSubscriptionChargeFailure(array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = TenantSubscription::where('nmi_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            return;
        }

        // Mark subscription as past due
        $this->subscriptionService->handlePaymentFailure($subscription);

        // TODO: Send notification email to tenant owner
        Log::warning('Subscription payment failed', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'reason' => $data['response_text'] ?? 'Unknown',
        ]);
    }

    /**
     * Handle subscription cancellation
     */
    protected function handleSubscriptionCancelled(array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = TenantSubscription::where('nmi_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            return;
        }

        $subscription->cancel('Cancelled via NMI');

        Log::info('Subscription cancelled via webhook', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }

    /**
     * Handle successful transaction
     */
    protected function handleTransactionSuccess(array $data): void
    {
        Log::info('Transaction successful', [
            'transaction_id' => $data['transaction_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'order_id' => $data['order_id'] ?? null,
        ]);

        // Extract tenant ID from order_id if present (format: tenant-{id}-{timestamp})
        $orderId = $data['order_id'] ?? '';
        if (preg_match('/^tenant-(\d+)-/', $orderId, $matches)) {
            $tenantId = $matches[1];
            // Log or process tenant-specific transaction
        }
    }

    /**
     * Handle failed transaction
     */
    protected function handleTransactionFailure(array $data): void
    {
        Log::warning('Transaction failed', [
            'transaction_id' => $data['transaction_id'] ?? null,
            'response_code' => $data['response_code'] ?? null,
            'response_text' => $data['response_text'] ?? null,
        ]);
    }

    /**
     * Handle customer vault creation
     */
    protected function handleVaultCreated(array $data): void
    {
        $vaultId = $data['customer_vault_id'] ?? null;

        Log::info('Customer vault created', [
            'vault_id' => $vaultId,
        ]);
    }

    /**
     * Handle customer vault update
     */
    protected function handleVaultUpdated(array $data): void
    {
        $vaultId = $data['customer_vault_id'] ?? null;

        Log::info('Customer vault updated', [
            'vault_id' => $vaultId,
        ]);
    }
}
