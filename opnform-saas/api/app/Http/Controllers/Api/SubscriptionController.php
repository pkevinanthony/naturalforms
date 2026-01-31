<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\NMI\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get available plans
     */
    public function plans(): JsonResponse
    {
        $plans = config('pricing.plans');

        return response()->json([
            'plans' => $plans,
            'trial' => config('pricing.trial'),
            'currency' => config('pricing.currency'),
        ]);
    }

    /**
     * Get current subscription
     */
    public function current(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $subscription = $tenant->activeSubscription();

        return response()->json([
            'subscription' => $subscription,
            'plan_features' => $tenant->getPlanFeatures(),
            'is_trialing' => $tenant->isTrialing(),
            'trial_ends_at' => $tenant->trial_ends_at,
        ]);
    }

    /**
     * Create a new subscription
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => ['required', 'string', Rule::in(array_keys(config('pricing.plans')))],
            'payment_token' => ['required', 'string'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'customer' => ['sometimes', 'array'],
            'customer.first_name' => ['sometimes', 'string'],
            'customer.last_name' => ['sometimes', 'string'],
            'customer.email' => ['sometimes', 'email'],
            'customer.address1' => ['sometimes', 'string'],
            'customer.city' => ['sometimes', 'string'],
            'customer.state' => ['sometimes', 'string'],
            'customer.zip' => ['sometimes', 'string'],
            'customer.country' => ['sometimes', 'string'],
        ]);

        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        try {
            $subscription = $this->subscriptionService->createSubscription(
                $tenant,
                $request->input('plan'),
                $request->input('payment_token'),
                $request->input('billing_cycle'),
                $request->input('customer', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription creation failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Subscription failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update payment method
     */
    public function updatePaymentMethod(Request $request): JsonResponse
    {
        $request->validate([
            'payment_token' => ['required', 'string'],
        ]);

        $tenant = app('current.tenant');
        $subscription = $tenant?->activeSubscription();

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription'], 404);
        }

        try {
            $this->subscriptionService->updatePaymentMethod(
                $subscription,
                $request->input('payment_token')
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update payment method',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Change subscription plan
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => ['required', 'string', Rule::in(array_keys(config('pricing.plans')))],
        ]);

        $tenant = app('current.tenant');
        $subscription = $tenant?->activeSubscription();

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription'], 404);
        }

        try {
            $subscription = $this->subscriptionService->changePlan(
                $subscription,
                $request->input('plan')
            );

            return response()->json([
                'success' => true,
                'message' => 'Plan changed successfully',
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to change plan',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['sometimes', 'string', 'max:500'],
        ]);

        $tenant = app('current.tenant');
        $subscription = $tenant?->activeSubscription();

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription'], 404);
        }

        try {
            $subscription = $this->subscriptionService->cancelSubscription(
                $subscription,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled successfully',
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel subscription',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resume canceled subscription
     */
    public function resume(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');
        $subscription = $tenant?->subscriptions()
            ->where('status', TenantSubscription::STATUS_CANCELED)
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json(['error' => 'No canceled subscription to resume'], 404);
        }

        try {
            $subscription = $this->subscriptionService->resumeSubscription($subscription);

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to resume subscription',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get billing history
     */
    public function history(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $subscriptions = $tenant->subscriptions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($subscriptions);
    }

    /**
     * Get NMI tokenization key for frontend
     */
    public function getTokenizationKey(): JsonResponse
    {
        return response()->json([
            'tokenization_key' => config('nmi.tokenization_key'),
            'variant' => config('nmi.collectjs.variant'),
        ]);
    }

    /**
     * Process one-time payment
     */
    public function oneTimePayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_token' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.50'],
            'description' => ['sometimes', 'string', 'max:255'],
        ]);

        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        try {
            $result = $this->subscriptionService->processOneTimePayment(
                $tenant,
                $request->input('payment_token'),
                $request->input('amount'),
                $request->input('description', 'One-time payment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'transaction_id' => $result['transactionid'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
