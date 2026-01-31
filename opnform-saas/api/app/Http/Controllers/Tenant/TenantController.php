<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TenantController extends Controller
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Get current tenant details
     */
    public function current(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        return response()->json([
            'tenant' => $tenant,
            'branding' => $tenant->branding,
            'settings' => $tenant->settings,
            'plan_features' => $tenant->getPlanFeatures(),
            'is_trialing' => $tenant->isTrialing(),
            'subscription' => $tenant->activeSubscription(),
        ]);
    }

    /**
     * Create a new tenant
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                'unique:tenants,subdomain',
                Rule::notIn(config('tenancy.reserved_subdomains')),
            ],
        ]);

        $user = Auth::user();

        $tenant = $this->tenantService->createTenant(
            $request->input('name'),
            $request->input('subdomain'),
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully',
            'tenant' => $tenant,
        ], 201);
    }

    /**
     * Update tenant settings
     */
    public function update(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
        ]);

        if ($request->has('name')) {
            $tenant->name = $request->input('name');
        }

        if ($request->has('settings')) {
            $tenant->updateSettings($request->input('settings'));
        }

        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully',
            'tenant' => $tenant->fresh(),
        ]);
    }

    /**
     * Update tenant branding
     */
    public function updateBranding(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        // Check if tenant has white-label feature
        if (!$tenant->hasFeature('white_label')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'White-label branding is not available on your current plan.',
            ], 403);
        }

        $request->validate([
            'logo' => ['sometimes', 'url'],
            'favicon' => ['sometimes', 'url'],
            'primary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family' => ['sometimes', 'string', 'max:100'],
            'footer_text' => ['sometimes', 'string', 'max:255'],
            'hide_powered_by' => ['sometimes', 'boolean'],
            'custom_css' => ['sometimes', 'string', 'max:10000'],
        ]);

        $tenant->updateBranding($request->only([
            'logo',
            'favicon',
            'primary_color',
            'secondary_color',
            'font_family',
            'footer_text',
            'hide_powered_by',
            'custom_css',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Branding updated successfully',
            'branding' => $tenant->branding,
        ]);
    }

    /**
     * Set custom domain
     */
    public function setCustomDomain(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        if (!$tenant->hasFeature('custom_domain')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Custom domains are not available on your current plan.',
            ], 403);
        }

        $request->validate([
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/',
                'unique:tenants,custom_domain',
            ],
        ]);

        $domain = strtolower($request->input('domain'));

        // Generate verification token
        $verificationToken = $this->tenantService->generateDomainVerificationToken($tenant, $domain);

        return response()->json([
            'success' => true,
            'message' => 'Please add the following DNS record to verify ownership',
            'verification' => [
                'domain' => $domain,
                'record_type' => 'TXT',
                'record_name' => config('tenancy.custom_domains.verification_prefix') . '.' . $domain,
                'record_value' => $verificationToken,
            ],
        ]);
    }

    /**
     * Verify custom domain
     */
    public function verifyCustomDomain(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $request->validate([
            'domain' => ['required', 'string'],
        ]);

        $domain = strtolower($request->input('domain'));

        try {
            $verified = $this->tenantService->verifyCustomDomain($tenant, $domain);

            if ($verified) {
                $tenant->update(['custom_domain' => $domain]);

                return response()->json([
                    'success' => true,
                    'message' => 'Domain verified successfully',
                    'custom_domain' => $domain,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Domain verification failed. Please check your DNS records.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Verification failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove custom domain
     */
    public function removeCustomDomain(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $tenant->update(['custom_domain' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Custom domain removed',
        ]);
    }

    /**
     * Get tenant usage statistics
     */
    public function usage(Request $request): JsonResponse
    {
        $tenant = app('current.tenant');

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $features = $tenant->getPlanFeatures();

        $usage = [
            'forms' => [
                'used' => $tenant->forms()->count(),
                'limit' => $features['forms'] ?? 0,
            ],
            'submissions_this_month' => [
                'used' => $tenant->forms()
                    ->withCount(['submissions' => function ($query) {
                        $query->where('created_at', '>=', Carbon::now()->startOfMonth());
                    }])
                    ->get()
                    ->sum('submissions_count'),
                'limit' => $features['submissions_per_month'] ?? 0,
            ],
            'team_members' => [
                'used' => $tenant->users()->count(),
                'limit' => $features['team_members'] ?? 0,
            ],
        ];

        return response()->json([
            'usage' => $usage,
            'plan' => $tenant->activeSubscription()?->plan ?? 'free',
        ]);
    }
}
