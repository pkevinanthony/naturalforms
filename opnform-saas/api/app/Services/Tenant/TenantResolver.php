<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TenantResolver
{
    /**
     * Resolve the tenant from the request
     */
    public function resolve(Request $request): ?Tenant
    {
        // Try to resolve from custom domain first
        $tenant = $this->resolveByCustomDomain($request);

        if ($tenant) {
            return $tenant;
        }

        // Try to resolve from subdomain
        $tenant = $this->resolveBySubdomain($request);

        if ($tenant) {
            return $tenant;
        }

        // Try to resolve from header (for API requests)
        $tenant = $this->resolveByHeader($request);

        return $tenant;
    }

    /**
     * Resolve tenant by custom domain
     */
    protected function resolveByCustomDomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $centralDomain = config('tenancy.central_domain');

        // Skip if this is a subdomain of the central domain
        if (str_ends_with($host, '.' . $centralDomain) || $host === $centralDomain) {
            return null;
        }

        return $this->findTenantCached('custom_domain', $host);
    }

    /**
     * Resolve tenant by subdomain
     */
    protected function resolveBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $centralDomain = config('tenancy.central_domain');

        // Check if this is a subdomain of the central domain
        if (!str_ends_with($host, '.' . $centralDomain)) {
            return null;
        }

        // Extract subdomain
        $subdomain = str_replace('.' . $centralDomain, '', $host);

        // Skip reserved subdomains
        $reserved = config('tenancy.reserved_subdomains', ['www', 'api', 'admin', 'app']);
        if (in_array($subdomain, $reserved)) {
            return null;
        }

        return $this->findTenantCached('subdomain', $subdomain);
    }

    /**
     * Resolve tenant by header
     */
    protected function resolveByHeader(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return null;
        }

        return $this->findTenantCached('uuid', $tenantId);
    }

    /**
     * Find tenant with caching
     */
    protected function findTenantCached(string $field, string $value): ?Tenant
    {
        $cacheKey = "tenant:{$field}:{$value}";

        return Cache::remember($cacheKey, 300, function () use ($field, $value) {
            return Tenant::where($field, $value)->first();
        });
    }

    /**
     * Clear tenant cache
     */
    public function clearCache(Tenant $tenant): void
    {
        Cache::forget("tenant:uuid:{$tenant->uuid}");
        Cache::forget("tenant:subdomain:{$tenant->subdomain}");

        if ($tenant->custom_domain) {
            Cache::forget("tenant:custom_domain:{$tenant->custom_domain}");
        }
    }
}
