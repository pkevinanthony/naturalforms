<template>
  <div class="pricing-plans">
    <!-- Billing Cycle Toggle -->
    <div class="flex justify-center mb-8">
      <div class="bg-gray-100 p-1 rounded-lg inline-flex">
        <button
          @click="billingCycle = 'monthly'"
          :class="[
            'px-6 py-2 rounded-md text-sm font-medium transition-colors',
            billingCycle === 'monthly'
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Monthly
        </button>
        <button
          @click="billingCycle = 'yearly'"
          :class="[
            'px-6 py-2 rounded-md text-sm font-medium transition-colors',
            billingCycle === 'yearly'
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Yearly
          <span class="ml-1 text-xs text-green-600 font-bold">Save 17%</span>
        </button>
      </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(plan, key) in plans"
        :key="key"
        :class="[
          'relative rounded-2xl p-6 flex flex-col',
          plan.popular
            ? 'bg-blue-600 text-white ring-4 ring-blue-600 ring-offset-2'
            : 'bg-white border border-gray-200'
        ]"
      >
        <!-- Popular Badge -->
        <div
          v-if="plan.popular"
          class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full"
        >
          MOST POPULAR
        </div>

        <!-- Plan Header -->
        <div class="mb-6">
          <h3
            :class="[
              'text-xl font-bold mb-2',
              plan.popular ? 'text-white' : 'text-gray-900'
            ]"
          >
            {{ plan.name }}
          </h3>
          <p
            :class="[
              'text-sm',
              plan.popular ? 'text-blue-100' : 'text-gray-500'
            ]"
          >
            {{ plan.description }}
          </p>
        </div>

        <!-- Price -->
        <div class="mb-6">
          <div class="flex items-baseline">
            <span
              :class="[
                'text-4xl font-extrabold',
                plan.popular ? 'text-white' : 'text-gray-900'
              ]"
            >
              ${{ getPrice(plan) }}
            </span>
            <span
              :class="[
                'ml-2 text-sm',
                plan.popular ? 'text-blue-100' : 'text-gray-500'
              ]"
            >
              /{{ billingCycle === 'yearly' ? 'year' : 'month' }}
            </span>
          </div>
          <p
            v-if="billingCycle === 'yearly' && plan.price_yearly > 0"
            :class="[
              'text-xs mt-1',
              plan.popular ? 'text-blue-200' : 'text-gray-400'
            ]"
          >
            ${{ Math.round(plan.price_yearly / 12) }}/month billed annually
          </p>
        </div>

        <!-- Features List -->
        <ul class="space-y-3 mb-8 flex-grow">
          <li
            v-for="(value, feature) in plan.features"
            :key="feature"
            class="flex items-start"
          >
            <svg
              :class="[
                'w-5 h-5 mr-2 flex-shrink-0',
                plan.popular ? 'text-blue-200' : 'text-green-500'
              ]"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                v-if="value"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
              <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            <span
              :class="[
                'text-sm',
                plan.popular ? 'text-blue-100' : 'text-gray-600',
                !value && 'line-through opacity-50'
              ]"
            >
              {{ formatFeature(feature, value) }}
            </span>
          </li>
        </ul>

        <!-- CTA Button -->
        <button
          @click="selectPlan(key)"
          :disabled="currentPlan === key"
          :class="[
            'w-full py-3 px-4 rounded-lg font-medium transition-colors',
            plan.popular
              ? 'bg-white text-blue-600 hover:bg-blue-50'
              : currentPlan === key
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-blue-600 text-white hover:bg-blue-700'
          ]"
        >
          {{ currentPlan === key ? 'Current Plan' : getButtonText(key, plan) }}
        </button>
      </div>
    </div>

    <!-- Enterprise CTA -->
    <div class="mt-12 text-center">
      <p class="text-gray-600 mb-4">
        Need more? Contact us for custom enterprise solutions.
      </p>
      <button
        @click="$emit('contact-enterprise')"
        class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
      >
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        Contact Sales
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface PlanFeatures {
  [key: string]: boolean | number;
}

interface Plan {
  name: string;
  description: string;
  price_monthly: number;
  price_yearly: number;
  features: PlanFeatures;
  popular?: boolean;
}

interface Props {
  plans: Record<string, Plan>;
  currentPlan?: string;
}

const props = withDefaults(defineProps<Props>(), {
  currentPlan: '',
});

const emit = defineEmits<{
  (e: 'select', plan: string, billingCycle: 'monthly' | 'yearly'): void;
  (e: 'contact-enterprise'): void;
}>();

const billingCycle = ref<'monthly' | 'yearly'>('monthly');

const getPrice = (plan: Plan): number => {
  return billingCycle.value === 'yearly' ? plan.price_yearly : plan.price_monthly;
};

const formatFeature = (feature: string, value: boolean | number): string => {
  const labels: Record<string, (v: any) => string> = {
    forms: (v) => v === -1 ? 'Unlimited forms' : `${v} forms`,
    submissions_per_month: (v) => v === -1 ? 'Unlimited submissions' : `${v.toLocaleString()} submissions/mo`,
    file_upload_size: (v) => `${v}MB file uploads`,
    team_members: (v) => v === -1 ? 'Unlimited team members' : `${v} team members`,
    custom_domain: (v) => v ? 'Custom domain' : 'No custom domain',
    white_label: (v) => v ? 'White-label branding' : 'No white-label',
    api_access: (v) => v ? 'API access' : 'No API access',
    integrations: (v) => v ? 'Integrations' : 'No integrations',
    priority_support: (v) => v ? 'Priority support' : 'Community support',
    form_logic: (v) => v ? 'Conditional logic' : 'No conditional logic',
    sso: (v) => v ? 'SSO/SAML' : 'No SSO',
    audit_log: (v) => v ? 'Audit log' : 'No audit log',
  };

  const formatter = labels[feature];
  if (formatter) {
    return formatter(value);
  }

  // Default formatting
  if (typeof value === 'boolean') {
    return feature.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  }
  return `${value} ${feature.replace(/_/g, ' ')}`;
};

const getButtonText = (key: string, plan: Plan): string => {
  if (plan.price_monthly === 0) {
    return 'Start Free';
  }
  return 'Get Started';
};

const selectPlan = (planKey: string) => {
  emit('select', planKey, billingCycle.value);
};
</script>
