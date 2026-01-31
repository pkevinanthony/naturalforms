<template>
  <div class="branding-settings max-w-4xl mx-auto">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">White-Label Branding</h1>
      <p class="text-gray-600">Customize the look and feel of your forms and dashboard</p>
    </div>

    <!-- Feature Gate -->
    <div
      v-if="!tenantStore.hasFeature('white_label')"
      class="bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl p-8 mb-8"
    >
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <svg class="w-12 h-12 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <div class="ml-4">
          <h3 class="text-xl font-bold mb-2">Unlock White-Label Branding</h3>
          <p class="text-white/80 mb-4">
            Upgrade to Professional or Enterprise to customize your branding, remove "Powered by" badges,
            and create a fully branded experience for your users.
          </p>
          <NuxtLink
            to="/billing"
            class="inline-flex items-center px-4 py-2 bg-white text-purple-600 rounded-lg font-medium hover:bg-purple-50 transition-colors"
          >
            Upgrade Now
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Branding Form -->
    <form v-else @submit.prevent="saveBranding" class="space-y-8">
      <!-- Logo Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Logo & Favicon</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Logo Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
            <div class="flex items-center space-x-4">
              <div class="w-24 h-24 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden">
                <img
                  v-if="branding.logo"
                  :src="branding.logo"
                  alt="Logo"
                  class="max-w-full max-h-full object-contain"
                />
                <svg v-else class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <input
                  v-model="branding.logo"
                  type="url"
                  placeholder="https://example.com/logo.png"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm"
                />
                <p class="text-xs text-gray-500 mt-1">Recommended: 200x50px PNG or SVG</p>
              </div>
            </div>
          </div>

          <!-- Favicon Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
            <div class="flex items-center space-x-4">
              <div class="w-16 h-16 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden">
                <img
                  v-if="branding.favicon"
                  :src="branding.favicon"
                  alt="Favicon"
                  class="w-8 h-8 object-contain"
                />
                <svg v-else class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <input
                  v-model="branding.favicon"
                  type="url"
                  placeholder="https://example.com/favicon.ico"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm"
                />
                <p class="text-xs text-gray-500 mt-1">32x32px ICO, PNG, or SVG</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Colors Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Brand Colors</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Primary Color -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Primary Color</label>
            <div class="flex items-center space-x-3">
              <input
                v-model="branding.primary_color"
                type="color"
                class="w-12 h-12 rounded-lg border border-gray-300 cursor-pointer"
              />
              <input
                v-model="branding.primary_color"
                type="text"
                pattern="^#[0-9A-Fa-f]{6}$"
                class="flex-1 border border-gray-300 rounded-lg px-4 py-2 font-mono"
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">Used for buttons, links, and accents</p>
          </div>

          <!-- Secondary Color -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Color</label>
            <div class="flex items-center space-x-3">
              <input
                v-model="branding.secondary_color"
                type="color"
                class="w-12 h-12 rounded-lg border border-gray-300 cursor-pointer"
              />
              <input
                v-model="branding.secondary_color"
                type="text"
                pattern="^#[0-9A-Fa-f]{6}$"
                class="flex-1 border border-gray-300 rounded-lg px-4 py-2 font-mono"
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">Used for secondary elements and hover states</p>
          </div>
        </div>

        <!-- Color Preview -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
          <p class="text-sm font-medium text-gray-700 mb-3">Preview</p>
          <div class="flex items-center space-x-4">
            <button
              type="button"
              class="px-4 py-2 rounded-lg text-white font-medium"
              :style="{ backgroundColor: branding.primary_color }"
            >
              Primary Button
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-lg text-white font-medium"
              :style="{ backgroundColor: branding.secondary_color }"
            >
              Secondary Button
            </button>
            <a
              href="#"
              class="font-medium"
              :style="{ color: branding.primary_color }"
            >
              Link Text
            </a>
          </div>
        </div>
      </div>

      <!-- Typography Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Typography</h2>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Font Family</label>
          <select
            v-model="branding.font_family"
            class="w-full md:w-1/2 border border-gray-300 rounded-lg px-4 py-2"
          >
            <option value="Inter">Inter (Default)</option>
            <option value="Roboto">Roboto</option>
            <option value="Open Sans">Open Sans</option>
            <option value="Lato">Lato</option>
            <option value="Poppins">Poppins</option>
            <option value="Montserrat">Montserrat</option>
            <option value="Source Sans Pro">Source Sans Pro</option>
            <option value="Nunito">Nunito</option>
          </select>
        </div>

        <!-- Font Preview -->
        <div class="mt-4 p-4 bg-gray-50 rounded-lg" :style="{ fontFamily: branding.font_family }">
          <p class="text-2xl font-bold text-gray-900 mb-2">The quick brown fox jumps over the lazy dog</p>
          <p class="text-gray-600">
            Pack my box with five dozen liquor jugs. How vexingly quick daft zebras jump!
          </p>
        </div>
      </div>

      <!-- Footer & Branding Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Footer & Attribution</h2>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Footer Text</label>
            <input
              v-model="branding.footer_text"
              type="text"
              placeholder="© 2024 Your Company. All rights reserved."
              class="w-full border border-gray-300 rounded-lg px-4 py-2"
            />
          </div>

          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900">Hide "Powered by" Badge</p>
              <p class="text-sm text-gray-500">Remove the FormBuilder branding from your forms</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="branding.hide_powered_by"
                type="checkbox"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- Custom CSS Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Custom CSS</h2>
        <p class="text-sm text-gray-500 mb-4">
          Add custom CSS to further customize the appearance of your forms. Use with caution.
        </p>

        <textarea
          v-model="branding.custom_css"
          rows="8"
          placeholder="/* Add your custom CSS here */
.form-container {
  /* Your styles */
}"
          class="w-full border border-gray-300 rounded-lg px-4 py-3 font-mono text-sm"
        ></textarea>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end space-x-4">
        <button
          type="button"
          @click="resetBranding"
          class="px-6 py-3 text-gray-600 hover:bg-gray-100 rounded-lg font-medium transition-colors"
        >
          Reset to Defaults
        </button>
        <button
          type="submit"
          :disabled="isSaving"
          class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
        >
          {{ isSaving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useTenantStore } from '~/stores/tenant';

const tenantStore = useTenantStore();

const branding = reactive({
  logo: '',
  favicon: '',
  primary_color: '#3B82F6',
  secondary_color: '#10B981',
  font_family: 'Inter',
  footer_text: '',
  hide_powered_by: false,
  custom_css: '',
});

const isSaving = ref(false);

const defaultBranding = {
  logo: '',
  favicon: '',
  primary_color: '#3B82F6',
  secondary_color: '#10B981',
  font_family: 'Inter',
  footer_text: '',
  hide_powered_by: false,
  custom_css: '',
};

onMounted(async () => {
  await tenantStore.fetchTenant();

  if (tenantStore.tenant?.branding) {
    Object.assign(branding, tenantStore.tenant.branding);
  }
});

const saveBranding = async () => {
  isSaving.value = true;

  try {
    await tenantStore.updateBranding({ ...branding });
    alert('Branding saved successfully!');
  } catch (error: any) {
    alert(error.message || 'Failed to save branding');
  } finally {
    isSaving.value = false;
  }
};

const resetBranding = () => {
  if (confirm('Are you sure you want to reset branding to defaults?')) {
    Object.assign(branding, defaultBranding);
  }
};
</script>
