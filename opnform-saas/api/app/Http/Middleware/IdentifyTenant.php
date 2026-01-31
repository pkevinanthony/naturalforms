<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\Tenant\TenantResolver;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    protected TenantResolver $resolver;

    public function __construct(TenantResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolver->resolve($request);

        if (!$tenant) {
            // Check if this is a central domain request
            if ($this->isCentralDomain($request)) {
                return $next($request);
            }

            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'The requested tenant could not be identified.',
            ], 404);
        }

        // Check if tenant is active
        if ($tenant->isSuspended()) {
            return response()->json([
                'error' => 'Tenant suspended',
                'message' => 'This account has been suspended. Please contact support.',
            ], 403);
        }

        // Bind tenant to container
        app()->instance('current.tenant', $tenant);
        app()->instance(Tenant::class, $tenant);

        // Set tenant ID in request for easy access
        $request->merge(['tenant_id' => $tenant->id]);

        // Add tenant to shared view data
        view()->share('currentTenant', $tenant);

        return $next($request);
    }

    /**
     * Check if request is for a central domain
     */
    protected function isCentralDomain(Request $request): bool
    {
        $centralDomains = config('tenancy.central_domains', []);
        $host = $request->getHost();

        return in_array($host, $centralDomains);
    }
}
