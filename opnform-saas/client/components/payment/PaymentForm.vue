<template>
  <div class="payment-form">
    <!-- Card Payment Form -->
    <div class="space-y-4">
      <!-- Card Number -->
      <div>
        <label for="cc-number" class="block text-sm font-medium text-gray-700 mb-1">
          Card Number
        </label>
        <div id="cc-number" class="collect-js-field"></div>
        <p v-if="!fieldValidation.ccnumber && fieldTouched.ccnumber" class="mt-1 text-sm text-red-500">
          Please enter a valid card number
        </p>
      </div>

      <!-- Expiry and CVV Row -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="cc-exp" class="block text-sm font-medium text-gray-700 mb-1">
            Expiry Date
          </label>
          <div id="cc-exp" class="collect-js-field"></div>
          <p v-if="!fieldValidation.ccexp && fieldTouched.ccexp" class="mt-1 text-sm text-red-500">
            Invalid expiry
          </p>
        </div>
        <div>
          <label for="cc-cvv" class="block text-sm font-medium text-gray-700 mb-1">
            CVV
          </label>
          <div id="cc-cvv" class="collect-js-field"></div>
          <p v-if="!fieldValidation.cvv && fieldTouched.cvv" class="mt-1 text-sm text-red-500">
            Invalid CVV
          </p>
        </div>
      </div>

      <!-- Card Type Indicator -->
      <div v-if="detectedCardType" class="flex items-center text-sm text-gray-600">
        <component :is="cardIcon" class="w-6 h-6 mr-2" />
        <span>{{ cardTypeName }}</span>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- Loading State -->
      <div v-if="!isConfigured" class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-gray-600">Loading payment form...</span>
      </div>

      <!-- Submit Button -->
      <button
        v-if="showSubmitButton"
        @click="handleSubmit"
        :disabled="!isFormValid || isProcessing"
        class="w-full py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
      >
        <span v-if="isProcessing" class="flex items-center justify-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Processing...
        </span>
        <span v-else>{{ submitButtonText }}</span>
      </button>

      <!-- Security Notice -->
      <div class="flex items-center justify-center text-xs text-gray-500 mt-4">
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
        </svg>
        Secured by NMI. Your payment info is encrypted.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useCollectJs } from '~/composables/useCollectJs';

interface Props {
  tokenizationKey: string;
  showSubmitButton?: boolean;
  submitButtonText?: string;
  amount?: number;
}

const props = withDefaults(defineProps<Props>(), {
  showSubmitButton: true,
  submitButtonText: 'Pay Now',
});

const emit = defineEmits<{
  (e: 'token', token: string, cardInfo: any): void;
  (e: 'error', message: string): void;
  (e: 'ready'): void;
  (e: 'validation', isValid: boolean): void;
}>();

const {
  isLoaded,
  isConfigured,
  isProcessing,
  error,
  fieldValidation,
  initialize,
  startPayment,
  isFormValid: checkFormValid,
} = useCollectJs();

const detectedCardType = ref<string | null>(null);
const fieldTouched = ref({
  ccnumber: false,
  ccexp: false,
  cvv: false,
});

const isFormValid = computed(() => checkFormValid());

const cardTypeName = computed(() => {
  const types: Record<string, string> = {
    visa: 'Visa',
    mastercard: 'Mastercard',
    amex: 'American Express',
    discover: 'Discover',
    diners: 'Diners Club',
    jcb: 'JCB',
  };
  return types[detectedCardType.value || ''] || 'Credit Card';
});

const cardIcon = computed(() => {
  // Return appropriate card icon component based on type
  return 'div'; // Placeholder - would use actual icon components
});

// Watch validation changes
watch(fieldValidation, (newVal) => {
  emit('validation', isFormValid.value);
}, { deep: true });

onMounted(async () => {
  try {
    await initialize({
      tokenizationKey: props.tokenizationKey,
      variant: 'inline',
      fields: {
        ccnumber: {
          selector: '#cc-number',
          placeholder: '4111 1111 1111 1111',
        },
        ccexp: {
          selector: '#cc-exp',
          placeholder: 'MM/YY',
        },
        cvv: {
          selector: '#cc-cvv',
          placeholder: 'CVV',
        },
      },
      callback: (response) => {
        if (response.token) {
          emit('token', response.token, response.card);
        }
      },
      validationCallback: (field, valid, message) => {
        fieldTouched.value[field as keyof typeof fieldTouched.value] = true;

        // Detect card type from ccnumber field
        if (field === 'ccnumber' && valid) {
          // Card type detection would be based on response
        }
      },
      timeoutCallback: () => {
        emit('error', 'Payment request timed out. Please try again.');
      },
      fieldsAvailableCallback: () => {
        emit('ready');
      },
    });
  } catch (err: any) {
    emit('error', err.message || 'Failed to load payment form');
  }
});

const handleSubmit = () => {
  if (!isFormValid.value) {
    // Mark all fields as touched to show validation errors
    Object.keys(fieldTouched.value).forEach((key) => {
      fieldTouched.value[key as keyof typeof fieldTouched.value] = true;
    });
    return;
  }

  startPayment();
};

// Expose method for parent component to trigger payment
defineExpose({
  startPayment: handleSubmit,
  isValid: isFormValid,
  isProcessing,
});
</script>

<style scoped>
.collect-js-field {
  min-height: 48px;
  background-color: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.collect-js-field:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.collect-js-field iframe {
  width: 100%;
  height: 48px;
}
</style>
