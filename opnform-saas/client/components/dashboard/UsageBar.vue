<template>
  <div class="usage-bar">
    <div class="flex items-center justify-between mb-1">
      <span class="text-sm font-medium text-gray-700">{{ label }}</span>
      <span class="text-sm text-gray-500">
        <span class="font-medium text-gray-900">{{ used.toLocaleString() }}</span>
        <span v-if="limit > 0"> / {{ limit.toLocaleString() }}</span>
        <span v-else> / Unlimited</span>
      </span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2.5">
      <div
        :class="[
          'h-2.5 rounded-full transition-all duration-500',
          progressColor
        ]"
        :style="{ width: `${progressWidth}%` }"
      ></div>
    </div>
    <p v-if="showWarning" class="text-xs text-amber-600 mt-1 flex items-center">
      <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
      </svg>
      {{ warningMessage }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  label: string;
  used: number;
  limit: number;
  warningThreshold?: number;
}

const props = withDefaults(defineProps<Props>(), {
  warningThreshold: 80,
});

const percentage = computed(() => {
  if (props.limit <= 0) return 0; // Unlimited
  return Math.min(100, Math.round((props.used / props.limit) * 100));
});

const progressWidth = computed(() => {
  if (props.limit <= 0) return 30; // Show small bar for unlimited
  return percentage.value;
});

const progressColor = computed(() => {
  if (props.limit <= 0) return 'bg-green-500'; // Unlimited
  if (percentage.value >= 100) return 'bg-red-500';
  if (percentage.value >= props.warningThreshold) return 'bg-amber-500';
  return 'bg-blue-500';
});

const showWarning = computed(() => {
  if (props.limit <= 0) return false;
  return percentage.value >= props.warningThreshold;
});

const warningMessage = computed(() => {
  if (percentage.value >= 100) {
    return 'Limit reached! Upgrade your plan for more.';
  }
  if (percentage.value >= 90) {
    return 'Almost at limit. Consider upgrading soon.';
  }
  return `${percentage.value}% of your limit used.`;
});
</script>
