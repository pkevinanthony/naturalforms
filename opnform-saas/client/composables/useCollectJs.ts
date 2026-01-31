/**
 * Composable for NMI Collect.js integration
 * Provides secure payment tokenization for PCI compliance
 */

import { ref, onMounted, onUnmounted } from 'vue';

interface CollectJsConfig {
  tokenizationKey: string;
  variant?: 'inline' | 'lightbox';
  fields?: {
    ccnumber?: { selector: string; placeholder?: string };
    ccexp?: { selector: string; placeholder?: string };
    cvv?: { selector: string; placeholder?: string };
  };
  customCss?: Record<string, string>;
  callback?: (response: CollectJsResponse) => void;
  validationCallback?: (field: string, valid: boolean, message: string) => void;
  timeoutCallback?: () => void;
  fieldsAvailableCallback?: () => void;
}

interface CollectJsResponse {
  token: string;
  tokenType: string;
  card?: {
    number: string;
    bin: string;
    exp: string;
    type: string;
    category: string;
    hash: string;
  };
  check?: {
    name: string;
    account: string;
    hash: string;
  };
}

interface CardInfo {
  type: string;
  valid: boolean;
  number: string;
}

declare global {
  interface Window {
    CollectJS: {
      configure: (config: any) => void;
      startPaymentRequest: () => void;
      clearInputs: () => void;
    };
  }
}

export function useCollectJs() {
  const isLoaded = ref(false);
  const isConfigured = ref(false);
  const isProcessing = ref(false);
  const error = ref<string | null>(null);
  const cardInfo = ref<CardInfo | null>(null);
  const fieldValidation = ref<Record<string, boolean>>({
    ccnumber: false,
    ccexp: false,
    cvv: false,
  });

  let scriptElement: HTMLScriptElement | null = null;

  /**
   * Load Collect.js script
   */
  const loadScript = (tokenizationKey: string): Promise<void> => {
    return new Promise((resolve, reject) => {
      if (window.CollectJS) {
        isLoaded.value = true;
        resolve();
        return;
      }

      scriptElement = document.createElement('script');
      scriptElement.src = 'https://secure.nmi.com/token/Collect.js';
      scriptElement.setAttribute('data-tokenization-key', tokenizationKey);
      scriptElement.async = true;

      scriptElement.onload = () => {
        isLoaded.value = true;
        resolve();
      };

      scriptElement.onerror = () => {
        error.value = 'Failed to load payment system';
        reject(new Error('Failed to load Collect.js'));
      };

      document.head.appendChild(scriptElement);
    });
  };

  /**
   * Configure Collect.js with options
   */
  const configure = (config: CollectJsConfig): Promise<void> => {
    return new Promise((resolve, reject) => {
      if (!window.CollectJS) {
        reject(new Error('Collect.js not loaded'));
        return;
      }

      const defaultCss = {
        'font-family': 'Inter, system-ui, sans-serif',
        'font-size': '16px',
        'color': '#1f2937',
        'padding': '12px',
        'border': '1px solid #d1d5db',
        'border-radius': '8px',
        'background-color': '#ffffff',
        'height': '48px',
      };

      const collectConfig: any = {
        paymentType: 'cc',
        variant: config.variant || 'inline',

        fields: config.fields || {
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

        styleSniffer: false,
        customCss: { ...defaultCss, ...config.customCss },

        callback: (response: CollectJsResponse) => {
          isProcessing.value = false;
          if (config.callback) {
            config.callback(response);
          }
        },

        validationCallback: (field: string, valid: boolean, message: string) => {
          fieldValidation.value[field] = valid;
          if (config.validationCallback) {
            config.validationCallback(field, valid, message);
          }
        },

        timeoutCallback: () => {
          isProcessing.value = false;
          error.value = 'Payment request timed out';
          if (config.timeoutCallback) {
            config.timeoutCallback();
          }
        },

        fieldsAvailableCallback: () => {
          isConfigured.value = true;
          if (config.fieldsAvailableCallback) {
            config.fieldsAvailableCallback();
          }
          resolve();
        },
      };

      try {
        window.CollectJS.configure(collectConfig);
      } catch (err) {
        reject(err);
      }
    });
  };

  /**
   * Initialize Collect.js
   */
  const initialize = async (config: CollectJsConfig): Promise<void> => {
    try {
      error.value = null;
      await loadScript(config.tokenizationKey);

      // Wait for DOM elements to be ready
      await new Promise(resolve => setTimeout(resolve, 100));

      await configure(config);
    } catch (err: any) {
      error.value = err.message || 'Failed to initialize payment system';
      throw err;
    }
  };

  /**
   * Start payment tokenization
   */
  const startPayment = (): void => {
    if (!window.CollectJS || !isConfigured.value) {
      error.value = 'Payment system not ready';
      return;
    }

    isProcessing.value = true;
    error.value = null;

    try {
      window.CollectJS.startPaymentRequest();
    } catch (err: any) {
      isProcessing.value = false;
      error.value = err.message || 'Failed to process payment';
    }
  };

  /**
   * Clear payment inputs
   */
  const clearInputs = (): void => {
    if (window.CollectJS) {
      window.CollectJS.clearInputs();
      fieldValidation.value = {
        ccnumber: false,
        ccexp: false,
        cvv: false,
      };
      cardInfo.value = null;
    }
  };

  /**
   * Check if all fields are valid
   */
  const isFormValid = (): boolean => {
    return Object.values(fieldValidation.value).every(valid => valid);
  };

  // Cleanup on unmount
  onUnmounted(() => {
    if (scriptElement && scriptElement.parentNode) {
      scriptElement.parentNode.removeChild(scriptElement);
    }
  });

  return {
    isLoaded,
    isConfigured,
    isProcessing,
    error,
    cardInfo,
    fieldValidation,
    initialize,
    startPayment,
    clearInputs,
    isFormValid,
  };
}
