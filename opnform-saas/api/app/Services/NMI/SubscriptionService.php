<?php

namespace App\Services\NMI;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionUpdated;
use App\Events\SubscriptionCanceled;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    protected NMIGatewayService $gateway;

    public function __construct(NMIGatewayService $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Create a new subscription for a tenant
     */
    public function createSubscription(
        Tenant $tenant,
        string $plan,
        string $paymentToken,
        string $billingCycle = 'monthly',
        array $customerData = []
    ): TenantSubscription {
        $planConfig = config("pricing.plans.{$plan}");

        if (!$planConfig) {
            throw new \InvalidArgumentException("Invalid plan: {$plan}");
        }

        $priceKey = $billingCycle === 'yearly' ? 'price_yearly' : 'price_monthly';
        $amount = $planConfig[$priceKey] ?? 0;

        return DB::transaction(function () use ($tenant, $plan, $paymentToken, $billingCycle, $amount, $customerData) {
            // Add customer to vault
            $vaultResponse = $this->gateway->addToVault($paymentToken, $customerData);
            $vaultId = $vaultResponse['customer_vault_id'];

            // Store vault ID on tenant
            $tenant->update(['nmi_customer_vault_id' => $vaultId]);

            // Create NMI subscription if amount > 0
            $nmiSubscriptionId = null;
            if ($amount > 0) {
                $subscriptionParams = [
                    'customer_vault_id' => $vaultId,
                    'plan_amount' => number_format($amount, 2, '.', ''),
                    'plan_payments' => 0, // 0 = unlimited recurring
                ];

                if ($billingCycle === 'yearly') {
                    $subscriptionParams['month_frequency'] = 12;
                    $subscriptionParams['day_of_month'] = Carbon::now()->day;
                } else {
                    $subscriptionParams['month_frequency'] = 1;
                    $subscriptionParams['day_of_month'] = Carbon::now()->day;
                }

                $subscriptionResponse = $this->gateway->createSubscription($subscriptionParams);
                $nmiSubscriptionId = $subscriptionResponse['subscription_id'] ?? null;
            }

            // Cancel any existing subscriptions
            $tenant->subscriptions()
                ->where('status', '!=', TenantSubscription::STATUS_CANCELED)
                ->update(['status' => TenantSubscription::STATUS_CANCELED]);

            // Create subscription record
            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan' => $plan,
                'status' => TenantSubscription::STATUS_ACTIVE,
                'nmi_subscription_id' => $nmiSubscriptionId,
                'nmi_customer_vault_id' => $vaultId,
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'currency' => 'USD',
                'current_period_start' => Carbon::now(),
                'current_period_end' => $billingCycle === 'yearly'
                    ? Carbon::now()->addYear()
                    : Carbon::now()->addMonth(),
            ]);

            // Activate tenant
            $tenant->activate();

            event(new SubscriptionCreated($subscription));

            return $subscription;
        });
    }

    /**
     * Update payment method for a subscription
     */
    public function updatePaymentMethod(TenantSubscription $subscription, string $paymentToken): void
    {
        $tenant = $subscription->tenant;

        // Add new payment method to vault
        if ($tenant->nmi_customer_vault_id) {
            // Update existing vault record with new payment token
            $this->gateway->updateVault($tenant->nmi_customer_vault_id, [
                'payment_token' => $paymentToken,
            ]);
        } else {
            // Create new vault record
            $vaultResponse = $this->gateway->addToVault($paymentToken);
            $tenant->update(['nmi_customer_vault_id' => $vaultResponse['customer_vault_id']]);
            $subscription->update(['nmi_customer_vault_id' => $vaultResponse['customer_vault_id']]);
        }

        Log::info('Payment method updated', [
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Change subscription plan
     */
    public function changePlan(TenantSubscription $subscription, string $newPlan): TenantSubscription
    {
        $planConfig = config("pricing.plans.{$newPlan}");

        if (!$planConfig) {
            throw new \InvalidArgumentException("Invalid plan: {$newPlan}");
        }

        $priceKey = $subscription->billing_cycle === 'yearly' ? 'price_yearly' : 'price_monthly';
        $newAmount = $planConfig[$priceKey] ?? 0;

        // Update NMI subscription if exists
        if ($subscription->nmi_subscription_id) {
            $this->gateway->updateSubscription($subscription->nmi_subscription_id, [
                'plan_amount' => number_format($newAmount, 2, '.', ''),
            ]);
        }

        $subscription->update([
            'plan' => $newPlan,
            'amount' => $newAmount,
        ]);

        event(new SubscriptionUpdated($subscription));

        return $subscription->fresh();
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(TenantSubscription $subscription, string $reason = null): TenantSubscription
    {
        // Cancel NMI subscription
        if ($subscription->nmi_subscription_id) {
            try {
                $this->gateway->cancelSubscription($subscription->nmi_subscription_id);
            } catch (\Exception $e) {
                Log::error('Failed to cancel NMI subscription', [
                    'subscription_id' => $subscription->id,
                    'nmi_subscription_id' => $subscription->nmi_subscription_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $subscription->cancel($reason);

        event(new SubscriptionCanceled($subscription));

        return $subscription;
    }

    /**
     * Resume a canceled subscription
     */
    public function resumeSubscription(TenantSubscription $subscription): TenantSubscription
    {
        if (!$subscription->isCanceled()) {
            throw new \InvalidArgumentException('Can only resume canceled subscriptions');
        }

        $tenant = $subscription->tenant;

        // Check if we have a payment method
        if (!$tenant->nmi_customer_vault_id) {
            throw new \InvalidArgumentException('No payment method on file');
        }

        // Create new NMI subscription
        $amount = $subscription->amount;
        if ($amount > 0) {
            $subscriptionParams = [
                'customer_vault_id' => $tenant->nmi_customer_vault_id,
                'plan_amount' => number_format($amount, 2, '.', ''),
                'plan_payments' => 0,
            ];

            if ($subscription->billing_cycle === 'yearly') {
                $subscriptionParams['month_frequency'] = 12;
                $subscriptionParams['day_of_month'] = Carbon::now()->day;
            } else {
                $subscriptionParams['month_frequency'] = 1;
                $subscriptionParams['day_of_month'] = Carbon::now()->day;
            }

            $response = $this->gateway->createSubscription($subscriptionParams);
            $subscription->nmi_subscription_id = $response['subscription_id'] ?? null;
        }

        $subscription->reactivate();
        $subscription->renew();

        event(new SubscriptionUpdated($subscription));

        return $subscription;
    }

    /**
     * Process subscription renewal (called by webhook)
     */
    public function processRenewal(TenantSubscription $subscription): void
    {
        $subscription->renew();
        event(new SubscriptionUpdated($subscription));

        Log::info('Subscription renewed', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }

    /**
     * Handle payment failure
     */
    public function handlePaymentFailure(TenantSubscription $subscription): void
    {
        $subscription->markPastDue();

        Log::warning('Subscription payment failed', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);

        // TODO: Send notification to tenant owner
    }

    /**
     * Process one-time payment
     */
    public function processOneTimePayment(
        Tenant $tenant,
        string $paymentToken,
        float $amount,
        string $description = 'One-time payment'
    ): array {
        $options = [
            'orderid' => 'tenant-' . $tenant->id . '-' . time(),
            'orderdescription' => $description,
        ];

        return $this->gateway->sale($paymentToken, $amount, $options);
    }
}
