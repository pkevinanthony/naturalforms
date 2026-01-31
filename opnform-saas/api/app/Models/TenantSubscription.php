<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TenantSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan',
        'status',
        'nmi_subscription_id',
        'nmi_customer_vault_id',
        'billing_cycle',
        'amount',
        'currency',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'cancel_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_TRIALING = 'trialing';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_CANCELED = 'canceled';
    const STATUS_UNPAID = 'unpaid';

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if subscription is trialing
     */
    public function isTrialing(): bool
    {
        return $this->status === self::STATUS_TRIALING;
    }

    /**
     * Check if subscription is past due
     */
    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Check if subscription is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Check if subscription is valid (active or trialing)
     */
    public function isValid(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING]);
    }

    /**
     * Check if current period has ended
     */
    public function hasEnded(): bool
    {
        return $this->current_period_end && Carbon::now()->isAfter($this->current_period_end);
    }

    /**
     * Get days until renewal
     */
    public function daysUntilRenewal(): int
    {
        if (!$this->current_period_end) {
            return 0;
        }
        return max(0, Carbon::now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Get plan configuration
     */
    public function getPlanConfig(): array
    {
        return config("pricing.plans.{$this->plan}", []);
    }

    /**
     * Get plan features
     */
    public function getFeatures(): array
    {
        return $this->getPlanConfig()['features'] ?? [];
    }

    /**
     * Cancel the subscription
     */
    public function cancel(string $reason = null): self
    {
        $this->status = self::STATUS_CANCELED;
        $this->canceled_at = Carbon::now();
        $this->cancel_reason = $reason;
        $this->save();

        return $this;
    }

    /**
     * Reactivate the subscription
     */
    public function reactivate(): self
    {
        $this->status = self::STATUS_ACTIVE;
        $this->canceled_at = null;
        $this->cancel_reason = null;
        $this->save();

        return $this;
    }

    /**
     * Mark as past due
     */
    public function markPastDue(): self
    {
        $this->status = self::STATUS_PAST_DUE;
        $this->save();

        return $this;
    }

    /**
     * Extend the current period
     */
    public function extendPeriod(int $days): self
    {
        $this->current_period_end = $this->current_period_end->addDays($days);
        $this->save();

        return $this;
    }

    /**
     * Renew the subscription
     */
    public function renew(): self
    {
        $this->current_period_start = Carbon::now();

        if ($this->billing_cycle === 'yearly') {
            $this->current_period_end = Carbon::now()->addYear();
        } else {
            $this->current_period_end = Carbon::now()->addMonth();
        }

        $this->status = self::STATUS_ACTIVE;
        $this->save();

        return $this;
    }

    /**
     * Upgrade or downgrade plan
     */
    public function changePlan(string $newPlan): self
    {
        $this->plan = $newPlan;
        $planConfig = config("pricing.plans.{$newPlan}");

        if ($planConfig) {
            $priceKey = $this->billing_cycle === 'yearly' ? 'price_yearly' : 'price_monthly';
            $this->amount = $planConfig[$priceKey] ?? 0;
        }

        $this->save();

        return $this;
    }
}
