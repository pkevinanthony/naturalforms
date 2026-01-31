<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'creator_id',
        'title',
        'slug',
        'description',
        'visibility',
        'properties',
        'theme',
        'settings',
        'notifications',
        'integrations',
        'closes_at',
        'closed_text',
        'submitted_text',
        'redirect_url',
        'use_captcha',
        'password',
        'logo_picture',
        'cover_picture',
        'custom_code',
    ];

    protected $casts = [
        'properties' => 'array',
        'settings' => 'array',
        'notifications' => 'array',
        'integrations' => 'array',
        'closes_at' => 'datetime',
        'use_captcha' => 'boolean',
    ];

    protected $attributes = [
        'visibility' => 'draft',
        'theme' => 'default',
        'properties' => '[]',
        'settings' => '{}',
        'notifications' => '{}',
        'integrations' => '[]',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug
        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = Str::random(12);
            }
        });

        // Global scope for tenant isolation
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenant = app('current.tenant')) {
                $builder->where('forms.tenant_id', $tenant->id);
            }
        });
    }

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get all submissions
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Get form views
     */
    public function views(): HasMany
    {
        return $this->hasMany(FormView::class);
    }

    /**
     * Check if form is public
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Check if form is closed
     */
    public function isClosed(): bool
    {
        if ($this->visibility === 'closed') {
            return true;
        }
        if ($this->closes_at && $this->closes_at->isPast()) {
            return true;
        }
        return false;
    }

    /**
     * Check if form requires password
     */
    public function requiresPassword(): bool
    {
        return !empty($this->password);
    }

    /**
     * Get the public share URL
     */
    public function getShareUrlAttribute(): string
    {
        $tenant = $this->tenant;
        $domain = $tenant->custom_domain ?? $tenant->subdomain . '.' . config('tenancy.central_domain');
        return "https://{$domain}/f/{$this->slug}";
    }

    /**
     * Get submissions count
     */
    public function getSubmissionsCountAttribute(): int
    {
        return $this->submissions()->count();
    }

    /**
     * Get views count
     */
    public function getViewsCountAttribute(): int
    {
        return $this->views()->count();
    }

    /**
     * Get conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        $views = $this->views_count;
        if ($views === 0) {
            return 0;
        }
        return round(($this->submissions_count / $views) * 100, 2);
    }

    /**
     * Get form field by id
     */
    public function getField(string $fieldId): ?array
    {
        foreach ($this->properties as $field) {
            if (($field['id'] ?? null) === $fieldId) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Publish the form
     */
    public function publish(): self
    {
        $this->visibility = 'public';
        $this->save();
        return $this;
    }

    /**
     * Close the form
     */
    public function close(): self
    {
        $this->visibility = 'closed';
        $this->save();
        return $this;
    }

    /**
     * Duplicate the form
     */
    public function duplicate(): self
    {
        $newForm = $this->replicate();
        $newForm->title = $this->title . ' (Copy)';
        $newForm->slug = null; // Will be auto-generated
        $newForm->visibility = 'draft';
        $newForm->save();

        return $newForm;
    }

    /**
     * Get setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Update form settings
     */
    public function updateSettings(array $settings): self
    {
        $this->settings = array_merge($this->settings ?? [], $settings);
        $this->save();
        return $this;
    }

    /**
     * Record a form view
     */
    public function recordView(?string $ip = null): void
    {
        $this->views()->create([
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
        ]);
    }
}
