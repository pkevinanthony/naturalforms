<template>
  <div class="dashboard">
    <!-- Trial Banner -->
    <div
      v-if="tenantStore.isTrialing"
      class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-4 py-3 rounded-lg mb-6"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>
            <strong>{{ tenantStore.trialDaysRemaining }} days</strong> left in your trial.
            Upgrade now to keep all your forms.
          </span>
        </div>
        <NuxtLink
          to="/billing"
          class="bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors"
        >
          Upgrade Now
        </NuxtLink>
      </div>
    </div>

    <!-- Dashboard Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600">Welcome back! Here's what's happening with your forms.</p>
      </div>
      <NuxtLink
        to="/forms/create"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Create Form
      </NuxtLink>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatCard
        title="Total Forms"
        :value="stats.formsCount"
        :limit="tenantStore.getFeatureLimit('forms')"
        icon="document"
        color="blue"
      />
      <StatCard
        title="Submissions This Month"
        :value="stats.submissionsThisMonth"
        :limit="tenantStore.getFeatureLimit('submissions_per_month')"
        icon="inbox"
        color="green"
      />
      <StatCard
        title="Total Views"
        :value="stats.totalViews"
        icon="eye"
        color="purple"
      />
      <StatCard
        title="Conversion Rate"
        :value="`${stats.conversionRate}%`"
        icon="trending-up"
        color="yellow"
      />
    </div>

    <!-- Usage Progress -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Plan Usage</h2>
      <div class="space-y-4">
        <UsageBar
          label="Forms"
          :used="tenantStore.usage?.forms.used || 0"
          :limit="tenantStore.usage?.forms.limit || 0"
        />
        <UsageBar
          label="Submissions"
          :used="tenantStore.usage?.submissions_this_month.used || 0"
          :limit="tenantStore.usage?.submissions_this_month.limit || 0"
        />
        <UsageBar
          label="Team Members"
          :used="tenantStore.usage?.team_members.used || 0"
          :limit="tenantStore.usage?.team_members.limit || 0"
        />
      </div>
      <div class="mt-4 text-sm text-gray-500">
        Current plan: <strong>{{ tenantStore.currentPlan }}</strong>
        <NuxtLink to="/billing" class="text-blue-600 ml-2 hover:underline">
          Upgrade
        </NuxtLink>
      </div>
    </div>

    <!-- Recent Forms -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">Recent Forms</h2>
          <NuxtLink to="/forms" class="text-sm text-blue-600 hover:underline">
            View all
          </NuxtLink>
        </div>
      </div>

      <div v-if="recentForms.length === 0" class="p-12 text-center">
        <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No forms yet</h3>
        <p class="text-gray-500 mb-4">Create your first form to start collecting submissions.</p>
        <NuxtLink
          to="/forms/create"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Create Your First Form
        </NuxtLink>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Form
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Submissions
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Views
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
          <tr v-for="form in recentForms" :key="form.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ form.title }}</div>
                  <div class="text-sm text-gray-500">{{ form.slug }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  form.visibility === 'public' ? 'bg-green-100 text-green-800' :
                  form.visibility === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-gray-100 text-gray-800'
                ]"
              >
                {{ form.visibility }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ form.submissions_count }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ form.views_count }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(form.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <NuxtLink :to="`/forms/${form.id}`" class="text-blue-600 hover:text-blue-900">
                Edit
              </NuxtLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useTenantStore } from '~/stores/tenant';

const tenantStore = useTenantStore();

const stats = ref({
  formsCount: 0,
  submissionsThisMonth: 0,
  totalViews: 0,
  conversionRate: 0,
});

const recentForms = ref<any[]>([]);

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
};

onMounted(async () => {
  await tenantStore.fetchTenant();
  await tenantStore.fetchUsage();

  // Fetch dashboard data
  try {
    const [formsResponse] = await Promise.all([
      $fetch('/api/forms?limit=5'),
    ]);

    recentForms.value = formsResponse.data || [];

    // Calculate stats
    stats.value = {
      formsCount: tenantStore.usage?.forms.used || 0,
      submissionsThisMonth: tenantStore.usage?.submissions_this_month.used || 0,
      totalViews: recentForms.value.reduce((acc: number, form: any) => acc + (form.views_count || 0), 0),
      conversionRate: 0, // Calculate from forms
    };
  } catch (error) {
    console.error('Failed to load dashboard data:', error);
  }
});
</script>
