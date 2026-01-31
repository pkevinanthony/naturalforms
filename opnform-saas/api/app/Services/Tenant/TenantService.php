<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Events\TenantCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TenantService
{
    protected TenantResolver $resolver;

    public function __construct(TenantResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Create a new tenant
     */
    public function createTenant(string $name, string $subdomain, User $owner): Tenant
    {
        return DB::transaction(function () use ($name, $subdomain, $owner) {
            $tenant = Tenant::create([
                'name' => $name,
                'subdomain' => strtolower($subdomain),
                'status' => 'trial',
                'trial_ends_at' => Carbon::now()->addDays(config('tenancy.trial_days', 14)),
                'branding' => config('tenancy.default_branding'),
                'settings' => [
                    'timezone' => 'UTC',
                    'date_format' => 'Y-m-d',
                    'notifications_enabled' => true,
                ],
            ]);

            // Attach owner
            $tenant->users()->attach($owner->id, [
                'role' => 'owner',
                'permissions' => null,
            ]);

            event(new TenantCreated($tenant, $owner));

            return $tenant;
        });
    }

    /**
     * Add user to tenant
     */
    public function addUserToTenant(Tenant $tenant, User $user, string $role = 'member'): void
    {
        // Check team member limit
        $features = $tenant->getPlanFeatures();
        $limit = $features['team_members'] ?? 1;

        if ($limit > 0 && $tenant->users()->count() >= $limit) {
            throw new \Exception('Team member limit reached for your current plan');
        }

        $tenant->users()->attach($user->id, [
            'role' => $role,
            'permissions' => null,
        ]);
    }

    /**
     * Remove user from tenant
     */
    public function removeUserFromTenant(Tenant $tenant, User $user): void
    {
        // Cannot remove the last owner
        if ($tenant->isOwner($user) && $tenant->owners()->count() === 1) {
            throw new \Exception('Cannot remove the last owner from the tenant');
        }

        $tenant->users()->detach($user->id);
    }

    /**
     * Transfer tenant ownership
     */
    public function transferOwnership(Tenant $tenant, User $currentOwner, User $newOwner): void
    {
        DB::transaction(function () use ($tenant, $currentOwner, $newOwner) {
            // Ensure new owner is a member
            if (!$tenant->users()->where('users.id', $newOwner->id)->exists()) {
                $this->addUserToTenant($tenant, $newOwner, 'owner');
            } else {
                // Update to owner
                $tenant->users()->updateExistingPivot($newOwner->id, ['role' => 'owner']);
            }

            // Demote current owner to admin
            $tenant->users()->updateExistingPivot($currentOwner->id, ['role' => 'admin']);
        });
    }

    /**
     * Generate domain verification token
     */
    public function generateDomainVerificationToken(Tenant $tenant, string $domain): string
    {
        $token = 'formbuilder-verify=' . Str::random(32);

        // Store pending verification
        Cache::put(
            "domain_verification:{$tenant->id}:{$domain}",
            $token,
            Carbon::now()->addHours(24)
        );

        return $token;
    }

    /**
     * Verify custom domain
     */
    public function verifyCustomDomain(Tenant $tenant, string $domain): bool
    {
        $expectedToken = Cache::get("domain_verification:{$tenant->id}:{$domain}");

        if (!$expectedToken) {
            throw new \Exception('No pending verification found for this domain');
        }

        // Check DNS TXT record
        $prefix = config('tenancy.custom_domains.verification_prefix');
        $records = dns_get_record("{$prefix}.{$domain}", DNS_TXT);

        if (!$records) {
            return false;
        }

        foreach ($records as $record) {
            if (isset($record['txt']) && $record['txt'] === $expectedToken) {
                // Verification successful, clear cache
                Cache::forget("domain_verification:{$tenant->id}:{$domain}");
                return true;
            }
        }

        return false;
    }

    /**
     * Check if subdomain is available
     */
    public function isSubdomainAvailable(string $subdomain): bool
    {
        $subdomain = strtolower($subdomain);

        // Check reserved
        if (in_array($subdomain, config('tenancy.reserved_subdomains', []))) {
            return false;
        }

        // Check database
        return !Tenant::where('subdomain', $subdomain)->exists();
    }

    /**
     * Suspend a tenant
     */
    public function suspendTenant(Tenant $tenant, string $reason = null): void
    {
        $tenant->suspend($reason);
        $this->resolver->clearCache($tenant);
    }

    /**
     * Activate a tenant
     */
    public function activateTenant(Tenant $tenant): void
    {
        $tenant->activate();
        $this->resolver->clearCache($tenant);
    }

    /**
     * Delete a tenant and all associated data
     */
    public function deleteTenant(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            // Delete all forms and submissions
            $tenant->forms()->each(function ($form) {
                $form->submissions()->delete();
                $form->views()->delete();
                $form->delete();
            });

            // Detach all users
            $tenant->users()->detach();

            // Delete subscriptions
            $tenant->subscriptions()->delete();

            // Delete tenant
            $tenant->delete();
        });

        $this->resolver->clearCache($tenant);
    }

    /**
     * Get tenant statistics
     */
    public function getTenantStats(Tenant $tenant): array
    {
        return [
            'forms_count' => $tenant->forms()->count(),
            'submissions_count' => $tenant->forms()->withCount('submissions')->get()->sum('submissions_count'),
            'team_members_count' => $tenant->users()->count(),
            'views_this_month' => $tenant->forms()
                ->withCount(['views' => function ($query) {
                    $query->where('created_at', '>=', Carbon::now()->startOfMonth());
                }])
                ->get()
                ->sum('views_count'),
        ];
    }
}
