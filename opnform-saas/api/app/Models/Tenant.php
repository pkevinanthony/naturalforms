<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'subdomain',
        'custom_domain',
        'settings',
        'branding',
        'status',
        'trial_ends_at',
        'nmi_customer_vault_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'branding' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'trial',
        'settings' => '{}',
        'branding' => '{}',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
            if (empty($tenant->subdomain)) {
                $tenant->subdomain = $tenant->slug;
            }
        });
    }

    /**
     * Get all users belonging to this tenant
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    /**
     * Get all forms for this tenant
     */
    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    /**
     * Get all subscriptions for this tenant
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    /**
     * Get the active subscription
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->latest()
            ->first();
    }

    /**
     * Get owners of the tenant
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'owner');
    }

    /**
     * Get admins of the tenant
     */
    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivotIn('role', ['owner', 'admin']);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(User $user): bool
    {
        return $this->admins()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if user is owner
     */
    public function isOwner(User $user): bool
    {
        return $this->owners()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if tenant is on trial
     */
    public function isTrialing(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant is active (paid or valid trial)
     */
    public function isActive(): bool
    {
        if ($this->status === 'active') {
            return true;
        }
        return $this->isTrialing();
    }

    /**
     * Check if tenant is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Get the full domain for this tenant
     */
    public function getFullDomainAttribute(): string
    {
        if ($this->custom_domain) {
            return $this->custom_domain;
        }
        return $this->subdomain . '.' . config('tenancy.central_domain');
    }

    /**
     * Get branding setting with fallback
     */
    public function getBrandingSetting(string $key, $default = null)
    {
        return data_get($this->branding, $key, $default);
    }

    /**
     * Update branding settings
     */
    public function updateBranding(array $settings): self
    {
        $this->branding = array_merge($this->branding ?? [], $settings);
        $this->save();
        return $this;
    }

    /**
     * Get tenant setting with fallback
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Update tenant settings
     */
    public function updateSettings(array $settings): self
    {
        $this->settings = array_merge($this->settings ?? [], $settings);
        $this->save();
        return $this;
    }

    /**
     * Get plan features
     */
    public function getPlanFeatures(): array
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {
            return config('pricing.plans.free.features', []);
        }
        return config("pricing.plans.{$subscription->plan}.features", []);
    }

    /**
     * Check if tenant has feature
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->getPlanFeatures();
        return isset($features[$feature]) && $features[$feature];
    }

    /**
     * Get feature limit
     */
    public function getFeatureLimit(string $feature): int
    {
        $features = $this->getPlanFeatures();
        return $features[$feature] ?? 0;
    }

    /**
     * Suspend the tenant
     */
    public function suspend(string $reason = null): self
    {
        $this->status = 'suspended';
        $this->updateSettings(['suspension_reason' => $reason]);
        return $this;
    }

    /**
     * Activate the tenant
     */
    public function activate(): self
    {
        $this->status = 'active';
        $this->save();
        return $this;
    }
}
