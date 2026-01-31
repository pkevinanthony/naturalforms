<template>
  <div class="admin-tenants">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Tenants</h1>
        <p class="text-gray-600">Manage all tenants in the system</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Tenant
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-sm font-medium text-gray-500">Total Tenants</div>
        <div class="text-3xl font-bold text-gray-900 mt-1">{{ stats.total_tenants }}</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-sm font-medium text-gray-500">Active</div>
        <div class="text-3xl font-bold text-green-600 mt-1">{{ stats.active_tenants }}</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-sm font-medium text-gray-500">Trial</div>
        <div class="text-3xl font-bold text-blue-600 mt-1">{{ stats.trial_tenants }}</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-sm font-medium text-gray-500">Suspended</div>
        <div class="text-3xl font-bold text-red-600 mt-1">{{ stats.suspended_tenants }}</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search tenants..."
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @input="debouncedSearch"
          />
        </div>
        <select
          v-model="filters.status"
          class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
          @change="fetchTenants"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="trial">Trial</option>
          <option value="suspended">Suspended</option>
        </select>
        <select
          v-model="filters.sortBy"
          class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
          @change="fetchTenants"
        >
          <option value="created_at">Date Created</option>
          <option value="name">Name</option>
          <option value="users_count">Team Size</option>
          <option value="forms_count">Forms</option>
        </select>
      </div>
    </div>

    <!-- Tenants Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Tenant
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Plan
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Users
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Forms
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Created
            </th>
            <th class="relative px-6 py-3">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="tenant in tenants" :key="tenant.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div
                  class="h-10 w-10 rounded-lg flex items-center justify-center text-white font-bold"
                  :style="{ backgroundColor: getAvatarColor(tenant.name) }"
                >
                  {{ tenant.name.charAt(0).toUpperCase() }}
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ tenant.name }}</div>
                  <div class="text-sm text-gray-500">
                    {{ tenant.subdomain }}.{{ centralDomain }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  tenant.status === 'active' ? 'bg-green-100 text-green-800' :
                  tenant.status === 'trial' ? 'bg-blue-100 text-blue-800' :
                  'bg-red-100 text-red-800'
                ]"
              >
                {{ tenant.status }}
              </span>
              <div v-if="tenant.status === 'trial' && tenant.trial_ends_at" class="text-xs text-gray-500 mt-1">
                {{ getDaysRemaining(tenant.trial_ends_at) }} days left
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ tenant.subscription?.plan || 'Free' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ tenant.users_count }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ tenant.forms_count }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(tenant.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end space-x-2">
                <NuxtLink
                  :to="`/admin/tenants/${tenant.id}`"
                  class="text-blue-600 hover:text-blue-900"
                >
                  View
                </NuxtLink>
                <button
                  v-if="tenant.status !== 'suspended'"
                  @click="suspendTenant(tenant)"
                  class="text-red-600 hover:text-red-900"
                >
                  Suspend
                </button>
                <button
                  v-else
                  @click="activateTenant(tenant)"
                  class="text-green-600 hover:text-green-900"
                >
                  Activate
                </button>
                <button
                  @click="impersonateTenant(tenant)"
                  class="text-purple-600 hover:text-purple-900"
                  title="Login as tenant admin"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="bg-gray-50 px-6 py-3 flex items-center justify-between">
        <div class="text-sm text-gray-500">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} tenants
        </div>
        <div class="flex space-x-2">
          <button
            @click="changePage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Previous
          </button>
          <button
            @click="changePage(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Create Tenant Modal -->
    <Modal v-model="showCreateModal" title="Create Tenant">
      <form @submit.prevent="createTenant" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input
            v-model="newTenant.name"
            type="text"
            required
            class="w-full border border-gray-300 rounded-lg px-4 py-2"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Subdomain</label>
          <div class="flex">
            <input
              v-model="newTenant.subdomain"
              type="text"
              required
              pattern="[a-z0-9-]+"
              class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2"
            />
            <span class="bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg px-4 py-2 text-gray-500">
              .{{ centralDomain }}
            </span>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Owner Email</label>
          <input
            v-model="newTenant.owner_email"
            type="email"
            required
            class="w-full border border-gray-300 rounded-lg px-4 py-2"
          />
        </div>
        <div class="flex justify-end space-x-3 pt-4">
          <button
            type="button"
            @click="showCreateModal = false"
            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isCreating"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ isCreating ? 'Creating...' : 'Create Tenant' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';

const centralDomain = 'app.formbuilder.local';

const tenants = ref<any[]>([]);
const stats = ref({
  total_tenants: 0,
  active_tenants: 0,
  trial_tenants: 0,
  suspended_tenants: 0,
});

const filters = reactive({
  search: '',
  status: '',
  sortBy: 'created_at',
});

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  from: 0,
  to: 0,
  total: 0,
});

const showCreateModal = ref(false);
const isCreating = ref(false);
const newTenant = reactive({
  name: '',
  subdomain: '',
  owner_email: '',
});

const fetchTenants = async () => {
  try {
    const params = new URLSearchParams({
      page: String(pagination.value.currentPage),
      search: filters.search,
      status: filters.status,
      sort_by: filters.sortBy,
    });

    const response = await $fetch(`/api/admin/tenants?${params}`);
    tenants.value = response.data;
    pagination.value = {
      currentPage: response.current_page,
      lastPage: response.last_page,
      from: response.from,
      to: response.to,
      total: response.total,
    };
  } catch (error) {
    console.error('Failed to fetch tenants:', error);
  }
};

const fetchStats = async () => {
  try {
    const response = await $fetch('/api/admin/statistics');
    stats.value = response;
  } catch (error) {
    console.error('Failed to fetch stats:', error);
  }
};

const debouncedSearch = useDebounceFn(() => {
  pagination.value.currentPage = 1;
  fetchTenants();
}, 300);

const changePage = (page: number) => {
  pagination.value.currentPage = page;
  fetchTenants();
};

const createTenant = async () => {
  isCreating.value = true;
  try {
    await $fetch('/api/admin/tenants', {
      method: 'POST',
      body: newTenant,
    });

    showCreateModal.value = false;
    Object.assign(newTenant, { name: '', subdomain: '', owner_email: '' });
    await fetchTenants();
    await fetchStats();
  } catch (error: any) {
    alert(error.message || 'Failed to create tenant');
  } finally {
    isCreating.value = false;
  }
};

const suspendTenant = async (tenant: any) => {
  if (!confirm(`Are you sure you want to suspend "${tenant.name}"?`)) return;

  try {
    await $fetch(`/api/admin/tenants/${tenant.id}/suspend`, { method: 'POST' });
    await fetchTenants();
    await fetchStats();
  } catch (error: any) {
    alert(error.message || 'Failed to suspend tenant');
  }
};

const activateTenant = async (tenant: any) => {
  try {
    await $fetch(`/api/admin/tenants/${tenant.id}/activate`, { method: 'POST' });
    await fetchTenants();
    await fetchStats();
  } catch (error: any) {
    alert(error.message || 'Failed to activate tenant');
  }
};

const impersonateTenant = async (tenant: any) => {
  try {
    // Get first owner
    const response = await $fetch(`/api/admin/tenants/${tenant.id}`);
    const owner = response.tenant.users?.find((u: any) => u.pivot?.role === 'owner');

    if (!owner) {
      alert('No owner found for this tenant');
      return;
    }

    const impersonation = await $fetch(`/api/admin/tenants/${tenant.id}/impersonate/${owner.id}`, {
      method: 'POST',
    });

    // Open in new tab
    window.open(impersonation.redirect_url, '_blank');
  } catch (error: any) {
    alert(error.message || 'Failed to impersonate');
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
};

const getDaysRemaining = (dateString: string) => {
  const endDate = new Date(dateString);
  const now = new Date();
  const diff = endDate.getTime() - now.getTime();
  return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
};

const getAvatarColor = (name: string) => {
  const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];
  const index = name.charCodeAt(0) % colors.length;
  return colors[index];
};

onMounted(async () => {
  await Promise.all([fetchTenants(), fetchStats()]);
});
</script>
