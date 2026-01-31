<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'settings',
        'email_verified_at',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'settings' => 'array',
        'is_super_admin' => 'boolean',
    ];

    /**
     * Get all tenants for this user
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    /**
     * Get tenants where user is owner
     */
    public function ownedTenants(): BelongsToMany
    {
        return $this->tenants()->wherePivot('role', 'owner');
    }

    /**
     * Get tenants where user is admin
     */
    public function adminTenants(): BelongsToMany
    {
        return $this->tenants()->wherePivotIn('role', ['owner', 'admin']);
    }

    /**
     * Get all forms created by this user
     */
    public function forms(): HasMany
    {
        return $this->hasMany(Form::class, 'creator_id');
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * Check if user belongs to tenant
     */
    public function belongsToTenant(Tenant $tenant): bool
    {
        return $this->tenants()->where('tenants.id', $tenant->id)->exists();
    }

    /**
     * Get user's role in tenant
     */
    public function roleInTenant(Tenant $tenant): ?string
    {
        $pivot = $this->tenants()->where('tenants.id', $tenant->id)->first();
        return $pivot ? $pivot->pivot->role : null;
    }

    /**
     * Check if user is admin in tenant
     */
    public function isAdminInTenant(Tenant $tenant): bool
    {
        return in_array($this->roleInTenant($tenant), ['owner', 'admin']);
    }

    /**
     * Check if user is owner of tenant
     */
    public function isOwnerOfTenant(Tenant $tenant): bool
    {
        return $this->roleInTenant($tenant) === 'owner';
    }

    /**
     * Get the current active tenant from session/context
     */
    public function currentTenant(): ?Tenant
    {
        return app('current.tenant');
    }

    /**
     * Get user's setting
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Update user settings
     */
    public function updateSettings(array $settings): self
    {
        $this->settings = array_merge($this->settings ?? [], $settings);
        $this->save();
        return $this;
    }

    /**
     * Get the avatar URL with fallback to Gravatar
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return $this->avatar;
        }

        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }
}
