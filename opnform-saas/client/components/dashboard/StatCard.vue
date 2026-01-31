<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">{{ title }}</p>
        <div class="mt-2 flex items-baseline">
          <span class="text-3xl font-bold" :class="textColorClass">
            {{ formattedValue }}
          </span>
          <span v-if="limit && limit > 0" class="ml-2 text-sm text-gray-500">
            / {{ limit === -1 ? '∞' : limit }}
          </span>
        </div>
        <div v-if="change" class="mt-2 flex items-center text-sm">
          <component
            :is="change > 0 ? 'ArrowUpIcon' : 'ArrowDownIcon'"
            :class="[
              'w-4 h-4 mr-1',
              change > 0 ? 'text-green-500' : 'text-red-500'
            ]"
          />
          <span :class="change > 0 ? 'text-green-600' : 'text-red-600'">
            {{ Math.abs(change) }}%
          </span>
          <span class="text-gray-500 ml-1">vs last period</span>
        </div>
      </div>
      <div
        :class="[
          'flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center',
          bgColorClass
        ]"
      >
        <component :is="iconComponent" :class="['w-6 h-6', iconColorClass]" />
      </div>
    </div>

    <!-- Progress bar if limit is set -->
    <div v-if="limit && limit > 0" class="mt-4">
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          :class="['h-2 rounded-full transition-all', progressColorClass]"
          :style="{ width: `${progressPercentage}%` }"
        ></div>
      </div>
      <p class="text-xs text-gray-500 mt-1">
        {{ progressPercentage }}% used
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';

interface Props {
  title: string;
  value: number | string;
  limit?: number;
  icon?: string;
  color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple';
  change?: number;
}

const props = withDefaults(defineProps<Props>(), {
  color: 'blue',
  icon: 'document',
});

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString();
  }
  return props.value;
});

const progressPercentage = computed(() => {
  if (!props.limit || props.limit <= 0) return 0;
  const numValue = typeof props.value === 'number' ? props.value : parseInt(props.value) || 0;
  return Math.min(100, Math.round((numValue / props.limit) * 100));
});

const colorClasses = {
  blue: {
    bg: 'bg-blue-100',
    icon: 'text-blue-600',
    text: 'text-gray-900',
    progress: 'bg-blue-600',
  },
  green: {
    bg: 'bg-green-100',
    icon: 'text-green-600',
    text: 'text-gray-900',
    progress: 'bg-green-600',
  },
  yellow: {
    bg: 'bg-yellow-100',
    icon: 'text-yellow-600',
    text: 'text-gray-900',
    progress: 'bg-yellow-600',
  },
  red: {
    bg: 'bg-red-100',
    icon: 'text-red-600',
    text: 'text-gray-900',
    progress: 'bg-red-600',
  },
  purple: {
    bg: 'bg-purple-100',
    icon: 'text-purple-600',
    text: 'text-gray-900',
    progress: 'bg-purple-600',
  },
};

const bgColorClass = computed(() => colorClasses[props.color].bg);
const iconColorClass = computed(() => colorClasses[props.color].icon);
const textColorClass = computed(() => colorClasses[props.color].text);
const progressColorClass = computed(() => colorClasses[props.color].progress);

// Icon components
const icons = {
  document: {
    render() {
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        }),
      ]);
    },
  },
  inbox: {
    render() {
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
        }),
      ]);
    },
  },
  eye: {
    render() {
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        }),
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        }),
      ]);
    },
  },
  'trending-up': {
    render() {
      return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        }),
      ]);
    },
  },
};

const iconComponent = computed(() => icons[props.icon as keyof typeof icons] || icons.document);
</script>
