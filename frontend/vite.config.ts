import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import legacy from '@vitejs/plugin-legacy'
import { VitePWA } from 'vite-plugin-pwa'
import viteCompression from 'vite-plugin-compression'
import { visualizer } from 'rollup-plugin-visualizer'
// import eslint from 'vite-plugin-eslint'
import checker from 'vite-plugin-checker'
// import { generateFullSitemap } from './src/utils/sitemap'

/// <reference types="vitest" />

// https://vite.dev/config/
export default defineConfig(({ command, mode }) => {
  // const env = loadEnv(mode, process.cwd(), '')
  const isProduction = command === 'build'
  const isDevelopment = command === 'serve'
  
  // Configuration HTTPS pour le développement (désactivé par défaut, activé uniquement avec Let's Encrypt)
  const useHTTPS = process.env.VITE_USE_HTTPS === 'true'

  return {
    plugins: [
      vue({
        script: {
          defineModel: true,
          propsDestructure: true
        }
      }),
      
      // DevTools seulement en développement
      ...(isDevelopment ? [vueDevTools()] : []),
      
      // Support des navigateurs legacy (seulement en production)
      ...(isProduction ? [
        legacy({
          targets: ['defaults', 'not IE 11'],
          additionalLegacyPolyfills: ['regenerator-runtime/runtime'],
          renderLegacyChunks: true,
          polyfills: [
            'es.symbol',
            'es.array.filter',
            'es.promise',
            'es.promise.finally',
            'es/map',
            'es/set',
            'es.array.for-each',
            'es.object.define-properties',
            'es.object.get-own-property-descriptor',
            'es.object.get-own-property-descriptors',
            'es.object.keys',
            'es.object.to-string',
            'web.dom-collections.for-each',
            'esnext.global-this',
            'esnext.string.match-all'
          ]
        })
      ] : []),

      // PWA simplifiée pour éviter les erreurs
      ...(isProduction ? [
        VitePWA({
          registerType: 'autoUpdate',
          includeAssets: ['favicon.ico', 'robots.txt'],
          manifest: {
            name: 'TaskForce - Gestion de Projets',
            short_name: 'TaskForce',
            description: 'Application de gestion de projets et tâches',
            theme_color: '#4f46e5',
            background_color: '#ffffff',
            display: 'standalone',
            scope: '/',
            start_url: '/'
          }
        })
      ] : []),

      // Compression Gzip et Brotli
      ...(isProduction ? [
        viteCompression({
          algorithm: 'gzip',
          ext: '.gz',
          threshold: 1024
        }),
        viteCompression({
          algorithm: 'brotliCompress',
          ext: '.br',
          threshold: 1024
        })
      ] : []),

      // Analyse du bundle en production
      ...(isProduction ? [
        visualizer({
          filename: 'dist/stats.html',
          open: false,
          gzipSize: true,
          brotliSize: true
        })
      ] : []),

      // Vérification TypeScript (seulement en développement)
      ...(isDevelopment ? [
        checker({
          vueTsc: true
        })
      ] : [])
    ],

    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    },

    // Optimisations de build
    build: {
      target: 'es2015',
      minify: 'terser',
      terserOptions: {
        compress: {
          drop_console: isProduction,
          drop_debugger: isProduction,
          pure_funcs: isProduction ? ['console.log', 'console.info'] : []
        },
        mangle: {
          safari10: true
        }
      },
      
      // Code splitting optimisé
      rollupOptions: {
        output: {
          manualChunks: {
            // Vendor chunks séparés pour un meilleur caching
            'vue-vendor': ['vue', 'vue-router'],
            'ui-vendor': ['pinia'],
            'utils-vendor': ['axios'],
            'stripe-vendor': ['@stripe/stripe-js']
          },
          chunkFileNames: (chunkInfo) => {
            const facadeModuleId = chunkInfo.facadeModuleId
            if (facadeModuleId) {
              const fileName = facadeModuleId.split('/').pop()?.replace('.vue', '') || 'chunk'
              return `js/${fileName}-[hash].js`
            }
            return 'js/[name]-[hash].js'
          },
          entryFileNames: 'js/[name]-[hash].js',
          assetFileNames: (assetInfo) => {
            const info = assetInfo.name?.split('.') || []
            const ext = info[info.length - 1]
            if (/\.(css)$/.test(assetInfo.name || '')) {
              return `css/[name]-[hash].${ext}`
            }
            if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name || '')) {
              return `images/[name]-[hash].${ext}`
            }
            if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name || '')) {
              return `fonts/[name]-[hash].${ext}`
            }
            return `assets/[name]-[hash].${ext}`
          }
        }
      },

      // Optimisations de taille
      chunkSizeWarningLimit: 1000,
      cssCodeSplit: true,
      sourcemap: !isProduction,
      
      // Optimisations de performance
      reportCompressedSize: true,
      assetsInlineLimit: 4096 // 4kb
    },

    // Optimisations de développement
    server: {
      port: 5173,
      strictPort: true,
      ...(useHTTPS && {
        https: {
          key: './ssl/live/letsencrypt/privkey.pem',
          cert: './ssl/live/letsencrypt/fullchain.pem'
        }
      }),
      hmr: {
        overlay: true
      },
      proxy: {
        '/api': {
          target: 'http://127.0.0.1:8000', // HTTP pour le backend en développement
          changeOrigin: true,
          secure: false, // Autoriser les certificats auto-signés en dev
        }
      }
    },

    // Optimisations CSS
    css: {
      devSourcemap: isDevelopment,
      preprocessorOptions: {
        scss: {
          additionalData: `@import "@/assets/styles/variables.scss";`
        }
      }
    },

    // Optimisations d'optimisation
    optimizeDeps: {
      include: [
        'vue',
        'vue-router',
        'pinia',
        'axios',
        '@stripe/stripe-js'
      ],
      exclude: ['@vueuse/core']
    },

    // Configuration des environnements
    define: {
      __VUE_OPTIONS_API__: true,
      __VUE_PROD_DEVTOOLS__: false,
      __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false
    },

    // Configuration Vitest
    test: {
      globals: true,
      environment: 'jsdom',
      setupFiles: ['./src/test/setup.ts'],
      coverage: {
        provider: 'v8',
        reporter: ['text', 'text-summary', 'json', 'html', 'lcov'],
        reportsDirectory: './coverage',
        exclude: [
          'node_modules/',
          'src/test/',
          '**/*.d.ts',
          '**/*.config.*',
          'dist/',
          'coverage/',
          '**/*.test.ts',
          '**/*.spec.ts',
          '**/types/',
          'src/main.ts'
        ],
        include: [
          'src/**/*.{js,ts,vue}',
          '!src/**/*.d.ts'
        ],
        all: true,
        thresholds: {
          global: {
            branches: 80,
            functions: 80,
            lines: 80,
            statements: 80
          }
        }
      }
    }
  }
})
