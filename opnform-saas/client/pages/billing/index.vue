<template>
  <div class="billing-page max-w-6xl mx-auto">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Billing & Subscription</h1>
      <p class="text-gray-600">Manage your subscription and payment method</p>
    </div>

    <!-- Current Subscription Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
      <div class="flex items-start justify-between">
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-2">Current Plan</h2>
          <div class="flex items-center">
            <span class="text-3xl font-bold text-gray-900">
              {{ currentPlanConfig?.name || 'Free' }}
            </span>
            <span
              v-if="subscription"
              :class="[
                'ml-3 px-3 py-1 rounded-full text-sm font-medium',
                subscription.status === 'active' ? 'bg-green-100 text-green-800' :
                subscription.status === 'trialing' ? 'bg-blue-100 text-blue-800' :
                subscription.status === 'past_due' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800'
              ]"
            >
              {{ subscription.status }}
            </span>
          </div>
          <p v-if="subscription" class="text-gray-500 mt-2">
            ${{ subscription.amount }}/{{ subscription.billing_cycle }}
            <span v-if="subscription.current_period_end">
              &middot; Renews {{ formatDate(subscription.current_period_end) }}
            </span>
          </p>
        </div>
        <div class="flex space-x-3">
          <button
            v-if="subscription?.status === 'active'"
            @click="showCancelModal = true"
            class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
          >
            Cancel Subscription
          </button>
          <button
            @click="showChangePlanModal = true"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Change Plan
          </button>
        </div>
      </div>
    </div>

    <!-- Payment Method Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Payment Method</h2>
        <button
          @click="showUpdatePaymentModal = true"
          class="text-blue-600 hover:text-blue-700 text-sm font-medium"
        >
          Update
        </button>
      </div>

      <div v-if="paymentMethod" class="flex items-center">
        <div class="bg-gray-100 rounded-lg p-3 mr-4">
          <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
          </svg>
        </div>
        <div>
          <p class="font-medium text-gray-900">
            {{ paymentMethod.brand }} ending in {{ paymentMethod.last4 }}
          </p>
          <p class="text-sm text-gray-500">
            Expires {{ paymentMethod.expMonth }}/{{ paymentMethod.expYear }}
          </p>
        </div>
      </div>

      <div v-else class="text-gray-500">
        No payment method on file.
        <button
          @click="showUpdatePaymentModal = true"
          class="text-blue-600 hover:underline ml-1"
        >
          Add one now
        </button>
      </div>
    </div>

    <!-- Billing History -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Billing History</h2>
      </div>

      <div v-if="billingHistory.length === 0" class="p-6 text-center text-gray-500">
        No billing history yet.
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in billingHistory" :key="item.id">
            <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(item.created_at) }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ item.description }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${{ item.amount }}</td>
            <td class="px-6 py-4">
              <span
                :class="[
                  'px-2 py-1 text-xs rounded-full',
                  item.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                ]"
              >
                {{ item.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Change Plan Modal -->
    <Modal v-model="showChangePlanModal" title="Change Plan" size="xl">
      <PricingPlans
        :plans="plans"
        :current-plan="subscription?.plan || 'free'"
        @select="handlePlanSelect"
      />
    </Modal>

    <!-- Update Payment Modal -->
    <Modal v-model="showUpdatePaymentModal" title="Update Payment Method">
      <div class="p-6">
        <PaymentForm
          :tokenization-key="tokenizationKey"
          submit-button-text="Save Payment Method"
          @token="handlePaymentToken"
          @error="handlePaymentError"
        />
      </div>
    </Modal>

    <!-- Cancel Subscription Modal -->
    <Modal v-model="showCancelModal" title="Cancel Subscription">
      <div class="p-6">
        <p class="text-gray-600 mb-4">
          Are you sure you want to cancel your subscription? You'll lose access to premium features
          at the end of your current billing period.
        </p>
        <textarea
          v-model="cancelReason"
          placeholder="Please tell us why you're canceling (optional)"
          class="w-full border border-gray-300 rounded-lg p-3 mb-4"
          rows="3"
        ></textarea>
        <div class="flex justify-end space-x-3">
          <button
            @click="showCancelModal = false"
            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"
          >
            Keep Subscription
          </button>
          <button
            @click="handleCancelSubscription"
            :disabled="isCanceling"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
          >
            {{ isCanceling ? 'Canceling...' : 'Cancel Subscription' }}
          </button>
        </div>
      </div>
    </Modal>

    <!-- Subscribe Modal -->
    <Modal v-model="showSubscribeModal" title="Subscribe" size="lg">
      <div class="p-6">
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900">{{ selectedPlan?.name }} Plan</h3>
          <p class="text-2xl font-bold text-gray-900 mt-2">
            ${{ selectedBillingCycle === 'yearly' ? selectedPlan?.price_yearly : selectedPlan?.price_monthly }}
            <span class="text-sm font-normal text-gray-500">/{{ selectedBillingCycle }}</span>
          </p>
        </div>

        <PaymentForm
          :tokenization-key="tokenizationKey"
          :submit-button-text="`Subscribe - $${selectedBillingCycle === 'yearly' ? selectedPlan?.price_yearly : selectedPlan?.price_monthly}/${selectedBillingCycle}`"
          @token="handleSubscribe"
          @error="handlePaymentError"
        />
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useTenantStore } from '~/stores/tenant';

const tenantStore = useTenantStore();

const subscription = ref<any>(null);
const paymentMethod = ref<any>(null);
const billingHistory = ref<any[]>([]);
const plans = ref<any>({});
const tokenizationKey = ref('');

const showChangePlanModal = ref(false);
const showUpdatePaymentModal = ref(false);
const showCancelModal = ref(false);
const showSubscribeModal = ref(false);

const selectedPlan = ref<any>(null);
const selectedPlanKey = ref('');
const selectedBillingCycle = ref<'monthly' | 'yearly'>('monthly');
const cancelReason = ref('');
const isCanceling = ref(false);

const currentPlanConfig = computed(() => {
  if (!subscription.value?.plan) return null;
  return plans.value[subscription.value.plan];
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
};

onMounted(async () => {
  // Fetch subscription data
  try {
    const [currentSub, plansData, tokenKey] = await Promise.all([
      $fetch('/api/billing/current'),
      $fetch('/api/billing/plans'),
      $fetch('/api/billing/tokenization-key'),
    ]);

    subscription.value = currentSub.subscription;
    plans.value = plansData.plans;
    tokenizationKey.value = tokenKey.tokenization_key;

    // Fetch billing history
    const history = await $fetch('/api/billing/history');
    billingHistory.value = history.data || [];
  } catch (error) {
    console.error('Failed to load billing data:', error);
  }
});

const handlePlanSelect = (planKey: string, billingCycle: 'monthly' | 'yearly') => {
  selectedPlanKey.value = planKey;
  selectedPlan.value = plans.value[planKey];
  selectedBillingCycle.value = billingCycle;
  showChangePlanModal.value = false;
  showSubscribeModal.value = true;
};

const handleSubscribe = async (token: string, cardInfo: any) => {
  try {
    await $fetch('/api/billing/subscribe', {
      method: 'POST',
      body: {
        plan: selectedPlanKey.value,
        payment_token: token,
        billing_cycle: selectedBillingCycle.value,
      },
    });

    showSubscribeModal.value = false;
    // Refresh subscription data
    window.location.reload();
  } catch (error: any) {
    console.error('Subscription failed:', error);
    alert(error.message || 'Failed to subscribe');
  }
};

const handlePaymentToken = async (token: string) => {
  try {
    await $fetch('/api/billing/payment-method', {
      method: 'PUT',
      body: { payment_token: token },
    });

    showUpdatePaymentModal.value = false;
    // Refresh payment method
    window.location.reload();
  } catch (error: any) {
    console.error('Failed to update payment method:', error);
    alert(error.message || 'Failed to update payment method');
  }
};

const handlePaymentError = (message: string) => {
  alert(message);
};

const handleCancelSubscription = async () => {
  isCanceling.value = true;
  try {
    await $fetch('/api/billing/cancel', {
      method: 'POST',
      body: { reason: cancelReason.value },
    });

    showCancelModal.value = false;
    window.location.reload();
  } catch (error: any) {
    console.error('Failed to cancel subscription:', error);
    alert(error.message || 'Failed to cancel subscription');
  } finally {
    isCanceling.value = false;
  }
};
</script>
