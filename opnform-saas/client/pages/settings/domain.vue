<template>
  <div class="domain-settings max-w-4xl mx-auto">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Custom Domain</h1>
      <p class="text-gray-600">Connect your own domain to serve forms from your brand</p>
    </div>

    <!-- Feature Gate -->
    <div
      v-if="!tenantStore.hasFeature('custom_domain')"
      class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl p-8 mb-8"
    >
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <svg class="w-12 h-12 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
          </svg>
        </div>
        <div class="ml-4">
          <h3 class="text-xl font-bold mb-2">Unlock Custom Domains</h3>
          <p class="text-white/80 mb-4">
            Upgrade to Professional or Enterprise to connect your own domain
            (e.g., forms.yourcompany.com) for a fully branded experience.
          </p>
          <NuxtLink
            to="/billing"
            class="inline-flex items-center px-4 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition-colors"
          >
            Upgrade Now
          </NuxtLink>
        </div>
      </div>
    </div>

    <template v-else>
      <!-- Current Domain Status -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Configuration</h2>

        <div class="space-y-4">
          <!-- Default Subdomain -->
          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="text-sm text-gray-500">Default Subdomain</p>
              <p class="font-medium text-gray-900">
                {{ tenantStore.tenant?.subdomain }}.{{ centralDomain }}
              </p>
            </div>
            <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
              Active
            </span>
          </div>

          <!-- Custom Domain -->
          <div
            v-if="tenantStore.tenant?.custom_domain"
            class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg"
          >
            <div>
              <p class="text-sm text-green-600">Custom Domain</p>
              <p class="font-medium text-green-800">
                {{ tenantStore.tenant.custom_domain }}
              </p>
            </div>
            <div class="flex items-center space-x-3">
              <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                Verified
              </span>
              <button
                @click="removeDomain"
                class="text-red-600 hover:text-red-800"
                title="Remove domain"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Custom Domain -->
      <div
        v-if="!tenantStore.tenant?.custom_domain"
        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8"
      >
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Custom Domain</h2>

        <!-- Step 1: Enter Domain -->
        <div v-if="step === 1" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Domain Name</label>
            <input
              v-model="newDomain"
              type="text"
              placeholder="forms.yourcompany.com"
              class="w-full border border-gray-300 rounded-lg px-4 py-3"
              @keyup.enter="requestVerification"
            />
            <p class="text-sm text-gray-500 mt-2">
              Enter the subdomain you want to use (e.g., forms.yourcompany.com)
            </p>
          </div>

          <button
            @click="requestVerification"
            :disabled="!newDomain || isLoading"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {{ isLoading ? 'Processing...' : 'Continue' }}
          </button>
        </div>

        <!-- Step 2: DNS Verification -->
        <div v-else-if="step === 2" class="space-y-6">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
              <svg class="w-5 h-5 text-yellow-600 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <div>
                <h3 class="text-sm font-medium text-yellow-800">DNS Configuration Required</h3>
                <p class="text-sm text-yellow-700 mt-1">
                  Add the following DNS records to verify ownership and connect your domain.
                </p>
              </div>
            </div>
          </div>

          <!-- DNS Records -->
          <div class="space-y-4">
            <h3 class="text-sm font-medium text-gray-900">Step 1: Add TXT Record (Verification)</h3>
            <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm">
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <p class="text-gray-500 text-xs mb-1">Type</p>
                  <p>TXT</p>
                </div>
                <div>
                  <p class="text-gray-500 text-xs mb-1">Name/Host</p>
                  <p class="break-all">{{ verificationData?.record_name }}</p>
                </div>
                <div>
                  <p class="text-gray-500 text-xs mb-1">Value</p>
                  <div class="flex items-center">
                    <p class="break-all">{{ verificationData?.record_value }}</p>
                    <button
                      @click="copyToClipboard(verificationData?.record_value)"
                      class="ml-2 text-blue-600 hover:text-blue-800"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <h3 class="text-sm font-medium text-gray-900 mt-6">Step 2: Add CNAME Record (Routing)</h3>
            <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm">
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <p class="text-gray-500 text-xs mb-1">Type</p>
                  <p>CNAME</p>
                </div>
                <div>
                  <p class="text-gray-500 text-xs mb-1">Name/Host</p>
                  <p>{{ newDomain.split('.')[0] }}</p>
                </div>
                <div>
                  <p class="text-gray-500 text-xs mb-1">Value</p>
                  <p>{{ centralDomain }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-4">
            <button
              @click="step = 1"
              class="px-6 py-3 text-gray-600 hover:bg-gray-100 rounded-lg font-medium transition-colors"
            >
              Back
            </button>
            <button
              @click="verifyDomain"
              :disabled="isLoading"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
            >
              {{ isLoading ? 'Verifying...' : 'Verify Domain' }}
            </button>
          </div>

          <p class="text-sm text-gray-500">
            DNS changes can take up to 48 hours to propagate. You can check back later if verification fails.
          </p>
        </div>
      </div>

      <!-- SSL Information -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">SSL Certificate</h2>
        <div class="flex items-start">
          <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="font-medium text-gray-900">Automatic SSL</p>
            <p class="text-sm text-gray-500 mt-1">
              We automatically provision and renew SSL certificates for all domains using Let's Encrypt.
              Your forms will always be served over HTTPS.
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useTenantStore } from '~/stores/tenant';

const tenantStore = useTenantStore();
const centralDomain = 'app.formbuilder.local';

const step = ref(1);
const newDomain = ref('');
const isLoading = ref(false);
const verificationData = ref<any>(null);

onMounted(async () => {
  await tenantStore.fetchTenant();
});

const requestVerification = async () => {
  if (!newDomain.value) return;

  isLoading.value = true;

  try {
    const response = await tenantStore.setCustomDomain(newDomain.value);
    verificationData.value = response.verification;
    step.value = 2;
  } catch (error: any) {
    alert(error.message || 'Failed to process domain');
  } finally {
    isLoading.value = false;
  }
};

const verifyDomain = async () => {
  isLoading.value = true;

  try {
    await tenantStore.verifyCustomDomain(newDomain.value);
    alert('Domain verified successfully!');
    step.value = 1;
    newDomain.value = '';
    verificationData.value = null;
  } catch (error: any) {
    alert(error.message || 'Domain verification failed. Please check your DNS records.');
  } finally {
    isLoading.value = false;
  }
};

const removeDomain = async () => {
  if (!confirm('Are you sure you want to remove your custom domain?')) return;

  try {
    await tenantStore.removeCustomDomain();
    alert('Custom domain removed');
  } catch (error: any) {
    alert(error.message || 'Failed to remove domain');
  }
};

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text);
  alert('Copied to clipboard!');
};
</script>
