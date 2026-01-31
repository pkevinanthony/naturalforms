<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * List all tenants
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query()
            ->withCount(['users', 'forms']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subdomain', 'like', "%{$search}%")
                    ->orWhere('custom_domain', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $tenants = $query->paginate($request->input('per_page', 20));

        return response()->json($tenants);
    }

    /**
     * Get tenant details
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['users', 'subscriptions']);
        $stats = $this->tenantService->getTenantStats($tenant);

        return response()->json([
            'tenant' => $tenant,
            'stats' => $stats,
        ]);
    }

    /**
     * Create a new tenant
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63', 'unique:tenants,subdomain'],
            'owner_email' => ['required', 'email'],
            'status' => ['sometimes', 'in:active,trial,suspended'],
        ]);

        $owner = User::where('email', $request->input('owner_email'))->first();

        if (!$owner) {
            return response()->json([
                'error' => 'User not found',
                'message' => 'No user found with the provided email address.',
            ], 404);
        }

        $tenant = $this->tenantService->createTenant(
            $request->input('name'),
            $request->input('subdomain'),
            $owner
        );

        if ($status = $request->input('status')) {
            $tenant->update(['status' => $status]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully',
            'tenant' => $tenant,
        ], 201);
    }

    /**
     * Update a tenant
     */
    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,trial,suspended'],
            'trial_ends_at' => ['sometimes', 'date'],
            'settings' => ['sometimes', 'array'],
            'branding' => ['sometimes', 'array'],
        ]);

        $tenant->fill($request->only(['name', 'status', 'trial_ends_at']));

        if ($request->has('settings')) {
            $tenant->updateSettings($request->input('settings'));
        }

        if ($request->has('branding')) {
            $tenant->updateBranding($request->input('branding'));
        }

        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully',
            'tenant' => $tenant->fresh(),
        ]);
    }

    /**
     * Suspend a tenant
     */
    public function suspend(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'reason' => ['sometimes', 'string', 'max:500'],
        ]);

        $this->tenantService->suspendTenant($tenant, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Tenant suspended',
        ]);
    }

    /**
     * Activate a tenant
     */
    public function activate(Tenant $tenant): JsonResponse
    {
        $this->tenantService->activateTenant($tenant);

        return response()->json([
            'success' => true,
            'message' => 'Tenant activated',
        ]);
    }

    /**
     * Delete a tenant
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantService->deleteTenant($tenant);

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted',
        ]);
    }

    /**
     * Impersonate a tenant user
     */
    public function impersonate(Tenant $tenant, User $user): JsonResponse
    {
        if (!$tenant->users()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'error' => 'User not in tenant',
                'message' => 'The specified user is not a member of this tenant.',
            ], 400);
        }

        // Generate impersonation token
        $token = $user->createToken('impersonation', ['impersonated'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Impersonation token generated',
            'token' => $token,
            'tenant' => $tenant,
            'user' => $user,
            'redirect_url' => "https://{$tenant->full_domain}/dashboard?impersonate_token={$token}",
        ]);
    }

    /**
     * Get all tenants statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'trial_tenants' => Tenant::where('status', 'trial')->count(),
            'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
            'total_users' => User::count(),
            'total_revenue' => 0, // TODO: Calculate from subscriptions
        ];

        return response()->json($stats);
    }
}
