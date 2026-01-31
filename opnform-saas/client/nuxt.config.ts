// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },

  // Runtime config
  runtimeConfig: {
    public: {
      apiUrl: process.env.NUXT_PUBLIC_API_URL || 'http://localhost:8000',
      appUrl: process.env.NUXT_PUBLIC_APP_URL || 'http://localhost:3000',
      nmiTokenizationKey: process.env.NUXT_PUBLIC_NMI_TOKENIZATION_KEY || '',
    },
  },

  // App configuration
  app: {
    head: {
      title: 'FormBuilder SaaS',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Multi-tenant form builder SaaS' },
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
        },
      ],
    },
  },

  // CSS
  css: ['~/assets/css/main.css'],

  // Modules
  modules: [
    '@pinia/nuxt',
    '@vueuse/nuxt',
    '@nuxtjs/tailwindcss',
  ],

  // Tailwind CSS
  tailwindcss: {
    cssPath: '~/assets/css/main.css',
    configPath: 'tailwind.config.js',
  },

  // Pinia state management
  pinia: {
    storesDirs: ['./stores/**'],
  },

  // TypeScript
  typescript: {
    strict: true,
  },

  // Build configuration
  build: {
    transpile: [],
  },

  // Vite configuration
  vite: {
    define: {
      'process.env.DEBUG': false,
    },
  },

  // Route rules
  routeRules: {
    // API routes
    '/api/**': {
      proxy: {
        to: process.env.NUXT_PUBLIC_API_URL || 'http://localhost:8000',
      },
    },
  },

  // Experimental features
  experimental: {
    payloadExtraction: false,
  },

  // Nitro server configuration
  nitro: {
    devProxy: {
      '/api': {
        target: process.env.NUXT_PUBLIC_API_URL || 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },

  compatibilityDate: '2024-01-01',
});
