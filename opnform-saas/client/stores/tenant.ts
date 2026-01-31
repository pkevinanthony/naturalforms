import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

interface Branding {
  logo?: string;
  favicon?: string;
  primary_color?: string;
  secondary_color?: string;
  font_family?: string;
  footer_text?: string;
  hide_powered_by?: boolean;
  custom_css?: string;
}

interface TenantSettings {
  timezone?: string;
  date_format?: string;
  notifications_enabled?: boolean;
}

interface Subscription {
  id: number;
  plan: string;
  status: string;
  billing_cycle: 'monthly' | 'yearly';
  amount: number;
  current_period_end: string;
}

interface PlanFeatures {
  forms: number;
  submissions_per_month: number;
  file_upload_size: number;
  team_members: number;
  custom_domain: boolean;
  white_label: boolean;
  api_access: boolean;
  integrations: boolean;
  priority_support: boolean;
  [key: string]: number | boolean;
}

interface Tenant {
  id: number;
  uuid: string;
  name: string;
  slug: string;
  subdomain: string;
  custom_domain: string | null;
  status: 'active' | 'trial' | 'suspended';
  trial_ends_at: string | null;
  settings: TenantSettings;
  branding: Branding;
  created_at: string;
}

interface Usage {
  forms: { used: number; limit: number };
  submissions_this_month: { used: number; limit: number };
  team_members: { used: number; limit: number };
}

export const useTenantStore = defineStore('tenant', () => {
  // State
  const tenant = ref<Tenant | null>(null);
  const subscription = ref<Subscription | null>(null);
  const planFeatures = ref<PlanFeatures | null>(null);
  const usage = ref<Usage | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const isTrialing = computed(() => {
    if (!tenant.value) return false;
    return tenant.value.status === 'trial' && tenant.value.trial_ends_at;
  });

  const trialDaysRemaining = computed(() => {
    if (!isTrialing.value || !tenant.value?.trial_ends_at) return 0;
    const endDate = new Date(tenant.value.trial_ends_at);
    const now = new Date();
    const diff = endDate.getTime() - now.getTime();
    return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
  });

  const isActive = computed(() => {
    return tenant.value?.status === 'active' || isTrialing.value;
  });

  const currentPlan = computed(() => subscription.value?.plan || 'free');

  const hasFeature = (feature: string): boolean => {
    if (!planFeatures.value) return false;
    const value = planFeatures.value[feature];
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value !== 0;
    return false;
  };

  const getFeatureLimit = (feature: string): number => {
    if (!planFeatures.value) return 0;
    const value = planFeatures.value[feature];
    return typeof value === 'number' ? value : 0;
  };

  const isAtLimit = (resource: keyof Usage): boolean => {
    if (!usage.value) return false;
    const { used, limit } = usage.value[resource];
    if (limit === -1) return false; // Unlimited
    return used >= limit;
  };

  const usagePercentage = (resource: keyof Usage): number => {
    if (!usage.value) return 0;
    const { used, limit } = usage.value[resource];
    if (limit === -1) return 0; // Unlimited
    return Math.min(100, Math.round((used / limit) * 100));
  };

  // Actions
  const fetchTenant = async () => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await $fetch('/api/tenant');
      tenant.value = response.tenant;
      subscription.value = response.subscription;
      planFeatures.value = response.plan_features;
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch tenant';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const fetchUsage = async () => {
    try {
      const response = await $fetch('/api/tenant/usage');
      usage.value = response.usage;
    } catch (err: any) {
      console.error('Failed to fetch usage:', err);
    }
  };

  const updateTenant = async (data: Partial<Tenant>) => {
    try {
      const response = await $fetch('/api/tenant', {
        method: 'PUT',
        body: data,
      });
      tenant.value = response.tenant;
      return response;
    } catch (err: any) {
      error.value = err.message || 'Failed to update tenant';
      throw err;
    }
  };

  const updateBranding = async (branding: Partial<Branding>) => {
    try {
      const response = await $fetch('/api/tenant/branding', {
        method: 'PUT',
        body: branding,
      });
      if (tenant.value) {
        tenant.value.branding = response.branding;
      }
      return response;
    } catch (err: any) {
      error.value = err.message || 'Failed to update branding';
      throw err;
    }
  };

  const setCustomDomain = async (domain: string) => {
    try {
      return await $fetch('/api/tenant/domain', {
        method: 'POST',
        body: { domain },
      });
    } catch (err: any) {
      error.value = err.message || 'Failed to set custom domain';
      throw err;
    }
  };

  const verifyCustomDomain = async (domain: string) => {
    try {
      const response = await $fetch('/api/tenant/domain/verify', {
        method: 'POST',
        body: { domain },
      });
      if (response.success && tenant.value) {
        tenant.value.custom_domain = response.custom_domain;
      }
      return response;
    } catch (err: any) {
      error.value = err.message || 'Failed to verify domain';
      throw err;
    }
  };

  const removeCustomDomain = async () => {
    try {
      await $fetch('/api/tenant/domain', {
        method: 'DELETE',
      });
      if (tenant.value) {
        tenant.value.custom_domain = null;
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to remove domain';
      throw err;
    }
  };

  const reset = () => {
    tenant.value = null;
    subscription.value = null;
    planFeatures.value = null;
    usage.value = null;
    error.value = null;
  };

  return {
    // State
    tenant,
    subscription,
    planFeatures,
    usage,
    isLoading,
    error,

    // Getters
    isTrialing,
    trialDaysRemaining,
    isActive,
    currentPlan,
    hasFeature,
    getFeatureLimit,
    isAtLimit,
    usagePercentage,

    // Actions
    fetchTenant,
    fetchUsage,
    updateTenant,
    updateBranding,
    setCustomDomain,
    verifyCustomDomain,
    removeCustomDomain,
    reset,
  };
});
